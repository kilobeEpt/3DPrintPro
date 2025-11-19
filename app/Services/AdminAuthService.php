<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\AdminSession;
use App\Models\AdminLoginAttempt;
use App\Models\AdminActionLog;

class AdminAuthService
{
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_DURATION_MINUTES = 15;
    const SESSION_LIFETIME_MINUTES = 30;
    const REMEMBER_TOKEN_LIFETIME_DAYS = 30;
    
    public function authenticate($email, $password, $ipAddress, $userAgent = null, $rememberMe = false)
    {
        $email = strtolower(trim($email));
        
        $lockoutCheck = $this->checkLockout($email, $ipAddress);
        if ($lockoutCheck['locked']) {
            AdminLoginAttempt::logFailure(
                $email,
                $ipAddress,
                AdminLoginAttempt::REASON_LOCKOUT,
                $userAgent
            );
            
            return [
                'success' => false,
                'error' => $lockoutCheck['message'],
                'locked_until' => $lockoutCheck['locked_until'],
            ];
        }
        
        $user = AdminUser::byEmail($email)->first();
        
        if (!$user) {
            AdminLoginAttempt::logFailure(
                $email,
                $ipAddress,
                AdminLoginAttempt::REASON_INVALID_CREDENTIALS,
                $userAgent
            );
            
            return [
                'success' => false,
                'error' => 'Неверный email или пароль.',
            ];
        }
        
        if (!$user->isActive()) {
            AdminLoginAttempt::logFailure(
                $email,
                $ipAddress,
                AdminLoginAttempt::REASON_ACCOUNT_INACTIVE,
                $userAgent
            );
            
            return [
                'success' => false,
                'error' => 'Аккаунт неактивен. Обратитесь к администратору.',
            ];
        }
        
        if ($user->isLocked()) {
            AdminLoginAttempt::logFailure(
                $email,
                $ipAddress,
                AdminLoginAttempt::REASON_ACCOUNT_LOCKED,
                $userAgent
            );
            
            $remainingTime = $user->locked_until ? $user->locked_until->diffInMinutes(now()) : 0;
            
            return [
                'success' => false,
                'error' => $remainingTime > 0 
                    ? "Аккаунт временно заблокирован. Попробуйте через {$remainingTime} минут."
                    : 'Аккаунт заблокирован. Обратитесь к администратору.',
            ];
        }
        
        if (!$user->verifyPassword($password)) {
            $user->incrementFailedAttempts();
            
            if ($user->failed_login_attempts >= self::MAX_LOGIN_ATTEMPTS) {
                $user->lockAccount(self::LOCKOUT_DURATION_MINUTES);
            }
            
            AdminLoginAttempt::logFailure(
                $email,
                $ipAddress,
                AdminLoginAttempt::REASON_INVALID_CREDENTIALS,
                $userAgent
            );
            
            return [
                'success' => false,
                'error' => 'Неверный email или пароль.',
            ];
        }
        
        $user->resetFailedAttempts();
        $user->updateLastLogin($ipAddress);
        
        AdminLoginAttempt::logSuccess($email, $ipAddress, $userAgent);
        
        $sessionId = session_id();
        if (empty($sessionId)) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $sessionId = session_id();
        }
        
        $session = $this->createSession($user, $sessionId, $ipAddress, $userAgent, $rememberMe);
        
        AdminActionLog::log(
            $user->id,
            AdminActionLog::ACTION_LOGIN,
            null,
            null,
            ['remember_me' => $rememberMe],
            $ipAddress,
            $userAgent
        );
        
        return [
            'success' => true,
            'user' => $user,
            'session' => $session,
            'csrf_token' => $session->csrf_token,
        ];
    }
    
    public function createSession(AdminUser $user, $sessionId, $ipAddress, $userAgent = null, $rememberMe = false)
    {
        $this->cleanupExpiredSessions();
        
        $this->destroyExistingSessionById($sessionId);
        
        $expiresAt = $rememberMe 
            ? now()->addDays(self::REMEMBER_TOKEN_LIFETIME_DAYS)
            : now()->addMinutes(self::SESSION_LIFETIME_MINUTES);
        
        $csrfToken = bin2hex(random_bytes(32));
        
        $session = AdminSession::create([
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'csrf_token' => $csrfToken,
            'expires_at' => $expiresAt,
            'last_activity_at' => now(),
        ]);
        
        if ($rememberMe) {
            $rememberToken = bin2hex(random_bytes(32));
            $user->remember_token = $rememberToken;
            $user->save();
        }
        
        return $session;
    }
    
    public function validateSession($sessionId)
    {
        if (empty($sessionId)) {
            return [
                'valid' => false,
                'error' => 'No session ID provided.',
            ];
        }
        
        $session = AdminSession::bySessionId($sessionId)->with('user')->first();
        
        if (!$session) {
            return [
                'valid' => false,
                'error' => 'Session not found.',
            ];
        }
        
        if ($session->isExpired()) {
            $this->destroySession($sessionId);
            
            return [
                'valid' => false,
                'error' => 'Session expired.',
            ];
        }
        
        $user = $session->user;
        
        if (!$user || !$user->isActive()) {
            $this->destroySession($sessionId);
            
            return [
                'valid' => false,
                'error' => 'User not found or inactive.',
            ];
        }
        
        $session->updateActivity();
        
        $inactivityTimeout = self::SESSION_LIFETIME_MINUTES;
        if ($session->last_activity_at->diffInMinutes(now()) > $inactivityTimeout) {
            $this->destroySession($sessionId);
            
            return [
                'valid' => false,
                'error' => 'Session expired due to inactivity.',
            ];
        }
        
        return [
            'valid' => true,
            'user' => $user,
            'session' => $session,
        ];
    }
    
    public function destroySession($sessionId)
    {
        $session = AdminSession::bySessionId($sessionId)->first();
        
        if ($session) {
            AdminActionLog::log(
                $session->user_id,
                AdminActionLog::ACTION_LOGOUT,
                null,
                null,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );
            
            $session->delete();
        }
        
        return true;
    }
    
    public function destroyExistingSessionById($sessionId)
    {
        AdminSession::bySessionId($sessionId)->delete();
    }
    
    public function destroyAllUserSessions($userId)
    {
        AdminSession::byUser($userId)->delete();
    }
    
    public function checkLockout($email, $ipAddress)
    {
        $recentAttempts = AdminLoginAttempt::byEmail($email)
            ->recent(self::LOCKOUT_DURATION_MINUTES)
            ->failed()
            ->count();
        
        if ($recentAttempts >= self::MAX_LOGIN_ATTEMPTS) {
            $oldestAttempt = AdminLoginAttempt::byEmail($email)
                ->recent(self::LOCKOUT_DURATION_MINUTES)
                ->failed()
                ->orderBy('created_at', 'asc')
                ->first();
            
            if ($oldestAttempt) {
                $lockedUntil = $oldestAttempt->created_at->addMinutes(self::LOCKOUT_DURATION_MINUTES);
                $remainingMinutes = max(0, $lockedUntil->diffInMinutes(now()));
                
                if ($remainingMinutes > 0) {
                    return [
                        'locked' => true,
                        'locked_until' => $lockedUntil,
                        'message' => "Слишком много попыток входа. Попробуйте снова через {$remainingMinutes} минут.",
                    ];
                }
            }
        }
        
        return [
            'locked' => false,
        ];
    }
    
    public function validateCsrfToken($sessionId, $token)
    {
        if (empty($token)) {
            return false;
        }
        
        $session = AdminSession::bySessionId($sessionId)->first();
        
        if (!$session || empty($session->csrf_token)) {
            return false;
        }
        
        return hash_equals($session->csrf_token, $token);
    }
    
    public function regenerateCsrfToken($sessionId)
    {
        $session = AdminSession::bySessionId($sessionId)->first();
        
        if ($session) {
            return $session->regenerateCsrfToken();
        }
        
        return null;
    }
    
    public function cleanupExpiredSessions()
    {
        AdminSession::expired()->delete();
    }
    
    public function logAction($userId, $action, $entityType = null, $entityId = null, $payload = null)
    {
        return AdminActionLog::log(
            $userId,
            $action,
            $entityType,
            $entityId,
            $payload,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );
    }
}
