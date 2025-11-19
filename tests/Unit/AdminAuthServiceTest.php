<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\AdminAuthService;
use App\Models\AdminUser;
use App\Models\AdminSession;
use App\Models\AdminLoginAttempt;
use App\Models\AdminActionLog;
use Illuminate\Database\Capsule\Manager as Capsule;

class AdminAuthServiceTest extends TestCase
{
    private $authService;
    
    protected function setUp(): void
    {
        parent::setUp();
        cleanTestData();
        $this->authService = new AdminAuthService();
    }
    
    protected function tearDown(): void
    {
        cleanTestData();
        parent::tearDown();
    }
    
    private function createTestUser($email = 'test@example.com', $status = 'active')
    {
        $user = new AdminUser();
        $user->email = $email;
        $user->name = 'Test User';
        $user->setPassword('password123');
        $user->role = AdminUser::ROLE_ADMIN;
        $user->status = $status;
        $user->save();
        
        return $user;
    }
    
    public function testAuthenticateWithValidCredentials()
    {
        $user = $this->createTestUser();
        
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        session_id('test-session-id');
        
        $result = $this->authService->authenticate(
            'test@example.com',
            'password123',
            '127.0.0.1',
            'PHPUnit Test'
        );
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('session', $result);
        $this->assertArrayHasKey('csrf_token', $result);
        $this->assertEquals('test@example.com', $result['user']->email);
        
        $loginAttempt = AdminLoginAttempt::byEmail('test@example.com')->first();
        $this->assertNotNull($loginAttempt);
        $this->assertTrue($loginAttempt->success);
    }
    
    public function testAuthenticateWithInvalidPassword()
    {
        $user = $this->createTestUser();
        
        $result = $this->authService->authenticate(
            'test@example.com',
            'wrongpassword',
            '127.0.0.1',
            'PHPUnit Test'
        );
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        
        $user->refresh();
        $this->assertEquals(1, $user->failed_login_attempts);
        
        $loginAttempt = AdminLoginAttempt::byEmail('test@example.com')->failed()->first();
        $this->assertNotNull($loginAttempt);
        $this->assertEquals(AdminLoginAttempt::REASON_INVALID_CREDENTIALS, $loginAttempt->failure_reason);
    }
    
    public function testAuthenticateWithNonExistentUser()
    {
        $result = $this->authService->authenticate(
            'nonexistent@example.com',
            'password123',
            '127.0.0.1',
            'PHPUnit Test'
        );
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        
        $loginAttempt = AdminLoginAttempt::byEmail('nonexistent@example.com')->failed()->first();
        $this->assertNotNull($loginAttempt);
    }
    
    public function testAuthenticateWithInactiveUser()
    {
        $user = $this->createTestUser('inactive@example.com', 'inactive');
        
        $result = $this->authService->authenticate(
            'inactive@example.com',
            'password123',
            '127.0.0.1',
            'PHPUnit Test'
        );
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('неактивен', $result['error']);
        
        $loginAttempt = AdminLoginAttempt::byEmail('inactive@example.com')->failed()->first();
        $this->assertNotNull($loginAttempt);
        $this->assertEquals(AdminLoginAttempt::REASON_ACCOUNT_INACTIVE, $loginAttempt->failure_reason);
    }
    
    public function testAccountLockoutAfterMaxAttempts()
    {
        $user = $this->createTestUser();
        
        for ($i = 0; $i < 5; $i++) {
            $this->authService->authenticate(
                'test@example.com',
                'wrongpassword',
                '127.0.0.1',
                'PHPUnit Test'
            );
        }
        
        $user->refresh();
        $this->assertEquals(5, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->isLocked());
    }
    
    public function testRateLimitingByEmail()
    {
        $user = $this->createTestUser();
        
        for ($i = 0; $i < 5; $i++) {
            AdminLoginAttempt::logFailure(
                'test@example.com',
                '127.0.0.1',
                AdminLoginAttempt::REASON_INVALID_CREDENTIALS
            );
        }
        
        $lockout = $this->authService->checkLockout('test@example.com', '127.0.0.1');
        
        $this->assertTrue($lockout['locked']);
        $this->assertArrayHasKey('message', $lockout);
        $this->assertArrayHasKey('locked_until', $lockout);
    }
    
    public function testCreateSession()
    {
        $user = $this->createTestUser();
        
        session_id('test-session-123');
        $session = $this->authService->createSession(
            $user,
            'test-session-123',
            '127.0.0.1',
            'PHPUnit Test'
        );
        
        $this->assertNotNull($session);
        $this->assertEquals('test-session-123', $session->session_id);
        $this->assertEquals($user->id, $session->user_id);
        $this->assertNotNull($session->csrf_token);
        $this->assertNotNull($session->expires_at);
    }
    
    public function testValidateValidSession()
    {
        $user = $this->createTestUser();
        
        $session = AdminSession::create([
            'session_id' => 'valid-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
            'csrf_token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addMinutes(30),
            'last_activity_at' => now(),
        ]);
        
        $validation = $this->authService->validateSession('valid-session');
        
        $this->assertTrue($validation['valid']);
        $this->assertArrayHasKey('user', $validation);
        $this->assertArrayHasKey('session', $validation);
    }
    
    public function testValidateExpiredSession()
    {
        $user = $this->createTestUser();
        
        $session = AdminSession::create([
            'session_id' => 'expired-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
            'csrf_token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->subMinutes(1),
            'last_activity_at' => now()->subMinutes(1),
        ]);
        
        $validation = $this->authService->validateSession('expired-session');
        
        $this->assertFalse($validation['valid']);
        $this->assertArrayHasKey('error', $validation);
        
        $deletedSession = AdminSession::bySessionId('expired-session')->first();
        $this->assertNull($deletedSession);
    }
    
    public function testValidateNonExistentSession()
    {
        $validation = $this->authService->validateSession('non-existent');
        
        $this->assertFalse($validation['valid']);
        $this->assertEquals('Session not found.', $validation['error']);
    }
    
    public function testDestroySession()
    {
        $user = $this->createTestUser();
        
        $session = AdminSession::create([
            'session_id' => 'session-to-delete',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
            'csrf_token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addMinutes(30),
            'last_activity_at' => now(),
        ]);
        
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';
        
        $this->authService->destroySession('session-to-delete');
        
        $deletedSession = AdminSession::bySessionId('session-to-delete')->first();
        $this->assertNull($deletedSession);
        
        $logoutLog = AdminActionLog::byUser($user->id)
            ->byAction(AdminActionLog::ACTION_LOGOUT)
            ->first();
        $this->assertNotNull($logoutLog);
    }
    
    public function testValidateCsrfToken()
    {
        $user = $this->createTestUser();
        $csrfToken = bin2hex(random_bytes(32));
        
        $session = AdminSession::create([
            'session_id' => 'csrf-test',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
            'csrf_token' => $csrfToken,
            'expires_at' => now()->addMinutes(30),
            'last_activity_at' => now(),
        ]);
        
        $valid = $this->authService->validateCsrfToken('csrf-test', $csrfToken);
        $this->assertTrue($valid);
        
        $invalid = $this->authService->validateCsrfToken('csrf-test', 'wrong-token');
        $this->assertFalse($invalid);
    }
    
    public function testRegenerateCsrfToken()
    {
        $user = $this->createTestUser();
        
        $session = AdminSession::create([
            'session_id' => 'regen-csrf',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
            'csrf_token' => 'old-token',
            'expires_at' => now()->addMinutes(30),
            'last_activity_at' => now(),
        ]);
        
        $newToken = $this->authService->regenerateCsrfToken('regen-csrf');
        
        $this->assertNotNull($newToken);
        $this->assertNotEquals('old-token', $newToken);
        
        $session->refresh();
        $this->assertEquals($newToken, $session->csrf_token);
    }
    
    public function testLogAction()
    {
        $user = $this->createTestUser();
        
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';
        
        $this->authService->logAction(
            $user->id,
            AdminActionLog::ACTION_CREATE,
            'service',
            123,
            ['name' => 'Test Service']
        );
        
        $log = AdminActionLog::byUser($user->id)->first();
        
        $this->assertNotNull($log);
        $this->assertEquals(AdminActionLog::ACTION_CREATE, $log->action);
        $this->assertEquals('service', $log->entity_type);
        $this->assertEquals(123, $log->entity_id);
        $this->assertEquals(['name' => 'Test Service'], $log->payload);
    }
    
    public function testCleanupExpiredSessions()
    {
        $user = $this->createTestUser();
        
        AdminSession::create([
            'session_id' => 'active-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
            'csrf_token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addMinutes(30),
            'last_activity_at' => now(),
        ]);
        
        AdminSession::create([
            'session_id' => 'expired-session-1',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
            'csrf_token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->subMinutes(10),
            'last_activity_at' => now()->subMinutes(10),
        ]);
        
        $this->authService->cleanupExpiredSessions();
        
        $activeSessions = AdminSession::active()->count();
        $this->assertEquals(1, $activeSessions);
        
        $expiredSessions = AdminSession::expired()->count();
        $this->assertEquals(0, $expiredSessions);
    }
    
    public function testResetFailedAttempts()
    {
        $user = $this->createTestUser();
        $user->failed_login_attempts = 3;
        $user->locked_until = now()->addMinutes(15);
        $user->save();
        
        $user->resetFailedAttempts();
        $user->refresh();
        
        $this->assertEquals(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }
}
