<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Services\AdminAuthService;
use App\Models\AdminUser;
use App\Models\AdminSession;
use App\Models\AdminLoginAttempt;
use App\Models\AdminActionLog;
use Illuminate\Database\Capsule\Manager as Capsule;

class AdminAuthIntegrationTest extends TestCase
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
    
    public function testCompleteLoginLogoutFlow()
    {
        $user = new AdminUser();
        $user->email = 'admin@example.com';
        $user->name = 'Admin User';
        $user->setPassword('SecurePassword123');
        $user->role = AdminUser::ROLE_ADMIN;
        $user->status = AdminUser::STATUS_ACTIVE;
        $user->save();
        
        session_id('test-session-flow');
        
        $loginResult = $this->authService->authenticate(
            'admin@example.com',
            'SecurePassword123',
            '192.168.1.100',
            'Mozilla/5.0'
        );
        
        $this->assertTrue($loginResult['success']);
        $this->assertNotNull($loginResult['user']);
        $this->assertNotNull($loginResult['session']);
        $this->assertNotNull($loginResult['csrf_token']);
        
        $session = AdminSession::bySessionId('test-session-flow')->first();
        $this->assertNotNull($session);
        $this->assertEquals($user->id, $session->user_id);
        
        $loginLog = AdminActionLog::byUser($user->id)
            ->byAction(AdminActionLog::ACTION_LOGIN)
            ->first();
        $this->assertNotNull($loginLog);
        
        $loginAttempt = AdminLoginAttempt::byEmail('admin@example.com')
            ->successful()
            ->first();
        $this->assertNotNull($loginAttempt);
        
        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertEquals('192.168.1.100', $user->last_login_ip);
        
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        
        $this->authService->destroySession('test-session-flow');
        
        $deletedSession = AdminSession::bySessionId('test-session-flow')->first();
        $this->assertNull($deletedSession);
        
        $logoutLog = AdminActionLog::byUser($user->id)
            ->byAction(AdminActionLog::ACTION_LOGOUT)
            ->first();
        $this->assertNotNull($logoutLog);
    }
    
    public function testMultipleFailedLoginAttemptsLeadToLockout()
    {
        $user = new AdminUser();
        $user->email = 'bruteforce@example.com';
        $user->name = 'Brute Force Target';
        $user->setPassword('RealPassword123');
        $user->role = AdminUser::ROLE_ADMIN;
        $user->status = AdminUser::STATUS_ACTIVE;
        $user->save();
        
        for ($i = 1; $i <= 5; $i++) {
            session_id("attempt-{$i}");
            
            $result = $this->authService->authenticate(
                'bruteforce@example.com',
                'WrongPassword',
                '10.0.0.1',
                'Attack Bot'
            );
            
            $this->assertFalse($result['success']);
        }
        
        $user->refresh();
        $this->assertEquals(5, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->isLocked());
        
        $failedAttempts = AdminLoginAttempt::byEmail('bruteforce@example.com')
            ->failed()
            ->count();
        $this->assertEquals(5, $failedAttempts);
        
        session_id('attempt-after-lock');
        
        $lockedResult = $this->authService->authenticate(
            'bruteforce@example.com',
            'RealPassword123',
            '10.0.0.1',
            'Attack Bot'
        );
        
        $this->assertFalse($lockedResult['success']);
        $this->assertStringContainsString('заблокирован', $lockedResult['error']);
    }
    
    public function testSessionValidationAndActivityTracking()
    {
        $user = new AdminUser();
        $user->email = 'session@example.com';
        $user->name = 'Session User';
        $user->setPassword('Password123');
        $user->role = AdminUser::ROLE_ADMIN;
        $user->status = AdminUser::STATUS_ACTIVE;
        $user->save();
        
        $session = AdminSession::create([
            'session_id' => 'activity-test',
            'user_id' => $user->id,
            'ip_address' => '172.16.0.1',
            'user_agent' => 'Test Browser',
            'csrf_token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addMinutes(30),
            'last_activity_at' => now()->subMinutes(5),
        ]);
        
        $initialActivity = $session->last_activity_at;
        
        sleep(1);
        
        $validation = $this->authService->validateSession('activity-test');
        
        $this->assertTrue($validation['valid']);
        
        $session->refresh();
        $this->assertGreaterThan($initialActivity->timestamp, $session->last_activity_at->timestamp);
    }
    
    public function testCsrfTokenRotation()
    {
        $user = new AdminUser();
        $user->email = 'csrf@example.com';
        $user->name = 'CSRF User';
        $user->setPassword('Password123');
        $user->role = AdminUser::ROLE_ADMIN;
        $user->status = AdminUser::STATUS_ACTIVE;
        $user->save();
        
        session_id('csrf-rotation-test');
        
        $loginResult = $this->authService->authenticate(
            'csrf@example.com',
            'Password123',
            '127.0.0.1',
            'Test'
        );
        
        $this->assertTrue($loginResult['success']);
        $initialCsrf = $loginResult['csrf_token'];
        
        $newCsrf = $this->authService->regenerateCsrfToken('csrf-rotation-test');
        
        $this->assertNotEquals($initialCsrf, $newCsrf);
        
        $validInitial = $this->authService->validateCsrfToken('csrf-rotation-test', $initialCsrf);
        $this->assertFalse($validInitial);
        
        $validNew = $this->authService->validateCsrfToken('csrf-rotation-test', $newCsrf);
        $this->assertTrue($validNew);
    }
    
    public function testRoleBasedAccessChecks()
    {
        $superAdmin = new AdminUser();
        $superAdmin->email = 'super@example.com';
        $superAdmin->name = 'Super Admin';
        $superAdmin->setPassword('password');
        $superAdmin->role = AdminUser::ROLE_SUPER_ADMIN;
        $superAdmin->status = AdminUser::STATUS_ACTIVE;
        $superAdmin->save();
        
        $admin = new AdminUser();
        $admin->email = 'admin@example.com';
        $admin->name = 'Regular Admin';
        $admin->setPassword('password');
        $admin->role = AdminUser::ROLE_ADMIN;
        $admin->status = AdminUser::STATUS_ACTIVE;
        $admin->save();
        
        $editor = new AdminUser();
        $editor->email = 'editor@example.com';
        $editor->name = 'Editor';
        $editor->setPassword('password');
        $editor->role = AdminUser::ROLE_EDITOR;
        $editor->status = AdminUser::STATUS_ACTIVE;
        $editor->save();
        
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertTrue($superAdmin->isAdmin());
        $this->assertTrue($superAdmin->hasRole(AdminUser::ROLE_SUPER_ADMIN));
        
        $this->assertFalse($admin->isSuperAdmin());
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->hasRole(AdminUser::ROLE_ADMIN));
        
        $this->assertFalse($editor->isSuperAdmin());
        $this->assertFalse($editor->isAdmin());
        $this->assertTrue($editor->hasRole(AdminUser::ROLE_EDITOR));
    }
    
    public function testActionLogging()
    {
        $user = new AdminUser();
        $user->email = 'logger@example.com';
        $user->name = 'Logger User';
        $user->setPassword('password');
        $user->role = AdminUser::ROLE_ADMIN;
        $user->status = AdminUser::STATUS_ACTIVE;
        $user->save();
        
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'Test Browser';
        
        $this->authService->logAction(
            $user->id,
            AdminActionLog::ACTION_CREATE,
            'service',
            1,
            ['name' => 'New Service', 'price' => 100]
        );
        
        $this->authService->logAction(
            $user->id,
            AdminActionLog::ACTION_UPDATE,
            'service',
            1,
            ['price' => 150]
        );
        
        $this->authService->logAction(
            $user->id,
            AdminActionLog::ACTION_DELETE,
            'order',
            5
        );
        
        $logs = AdminActionLog::byUser($user->id)->get();
        $this->assertCount(3, $logs);
        
        $createLog = $logs->where('action', AdminActionLog::ACTION_CREATE)->first();
        $this->assertEquals('service', $createLog->entity_type);
        $this->assertEquals(['name' => 'New Service', 'price' => 100], $createLog->payload);
        
        $updateLog = $logs->where('action', AdminActionLog::ACTION_UPDATE)->first();
        $this->assertEquals(['price' => 150], $updateLog->payload);
    }
    
    public function testRememberMeFunctionality()
    {
        $user = new AdminUser();
        $user->email = 'remember@example.com';
        $user->name = 'Remember User';
        $user->setPassword('password');
        $user->role = AdminUser::ROLE_ADMIN;
        $user->status = AdminUser::STATUS_ACTIVE;
        $user->save();
        
        session_id('remember-me-test');
        
        $result = $this->authService->authenticate(
            'remember@example.com',
            'password',
            '127.0.0.1',
            'Test',
            true
        );
        
        $this->assertTrue($result['success']);
        
        $session = AdminSession::bySessionId('remember-me-test')->first();
        $this->assertNotNull($session);
        
        $expiryDays = $session->expires_at->diffInDays(now());
        $this->assertGreaterThan(25, $expiryDays);
        
        $user->refresh();
        $this->assertNotNull($user->remember_token);
    }
}
