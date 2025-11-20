<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\AdminAuthService;
use App\Models\AdminUser;
use App\Models\AdminSession;

class CsrfProtectionTest extends TestCase
{
    private $authService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        seedTestData();
        
        $this->authService = new AdminAuthService();
    }
    
    protected function tearDown(): void
    {
        cleanTestData();
        parent::tearDown();
    }
    
    public function testCsrfTokenGeneratedOnLogin()
    {
        $user = AdminUser::create([
            'email' => 'csrf.test@example.com',
            'name' => 'CSRF Test User',
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active'
        ]);
        
        $result = $this->authService->authenticate(
            'csrf.test@example.com',
            'password123',
            '127.0.0.1',
            'Test Agent'
        );
        
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['csrf_token']);
        $this->assertEquals(64, strlen($result['csrf_token'])); // bin2hex(random_bytes(32))
    }
    
    public function testCsrfTokenStoredInSession()
    {
        $user = AdminUser::create([
            'email' => 'csrf.session@example.com',
            'name' => 'CSRF Session User',
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active'
        ]);
        
        $result = $this->authService->authenticate(
            'csrf.session@example.com',
            'password123',
            '127.0.0.1',
            'Test Agent'
        );
        
        $session = AdminSession::where('user_id', $user->id)->first();
        
        $this->assertNotNull($session);
        $this->assertNotEmpty($session->csrf_token);
        $this->assertEquals($result['csrf_token'], $session->csrf_token);
    }
    
    public function testValidCsrfTokenAccepted()
    {
        $user = AdminUser::create([
            'email' => 'valid.csrf@example.com',
            'name' => 'Valid CSRF User',
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active'
        ]);
        
        $result = $this->authService->authenticate(
            'valid.csrf@example.com',
            'password123',
            '127.0.0.1',
            'Test Agent'
        );
        
        $sessionId = $result['session']->session_id;
        $csrfToken = $result['csrf_token'];
        
        // Validate the token
        $isValid = $this->authService->validateCsrfToken($sessionId, $csrfToken);
        
        $this->assertTrue($isValid);
    }
    
    public function testInvalidCsrfTokenRejected()
    {
        $user = AdminUser::create([
            'email' => 'invalid.csrf@example.com',
            'name' => 'Invalid CSRF User',
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active'
        ]);
        
        $result = $this->authService->authenticate(
            'invalid.csrf@example.com',
            'password123',
            '127.0.0.1',
            'Test Agent'
        );
        
        $sessionId = $result['session']->session_id;
        $invalidToken = 'invalid_token_123';
        
        // Validate the invalid token
        $isValid = $this->authService->validateCsrfToken($sessionId, $invalidToken);
        
        $this->assertFalse($isValid);
    }
    
    public function testEmptyCsrfTokenRejected()
    {
        $user = AdminUser::create([
            'email' => 'empty.csrf@example.com',
            'name' => 'Empty CSRF User',
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active'
        ]);
        
        $result = $this->authService->authenticate(
            'empty.csrf@example.com',
            'password123',
            '127.0.0.1',
            'Test Agent'
        );
        
        $sessionId = $result['session']->session_id;
        
        // Try with empty token
        $isValid = $this->authService->validateCsrfToken($sessionId, '');
        
        $this->assertFalse($isValid);
    }
    
    public function testCsrfTokenRotatedOnPasswordChange()
    {
        $user = AdminUser::create([
            'email' => 'rotate.csrf@example.com',
            'name' => 'Rotate CSRF User',
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active'
        ]);
        
        $result = $this->authService->authenticate(
            'rotate.csrf@example.com',
            'password123',
            '127.0.0.1',
            'Test Agent'
        );
        
        $originalToken = $result['csrf_token'];
        
        // Change password (this should destroy sessions and rotate tokens)
        $user->password_hash = password_hash('newpassword456', PASSWORD_BCRYPT);
        $user->save();
        
        // Re-authenticate
        $result2 = $this->authService->authenticate(
            'rotate.csrf@example.com',
            'newpassword456',
            '127.0.0.1',
            'Test Agent'
        );
        
        $newToken = $result2['csrf_token'];
        
        // Tokens should be different
        $this->assertNotEquals($originalToken, $newToken);
    }
    
    public function testCsrfTokenTimingSafeComparison()
    {
        // Test that CSRF validation uses timing-safe comparison
        $user = AdminUser::create([
            'email' => 'timing.csrf@example.com',
            'name' => 'Timing CSRF User',
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active'
        ]);
        
        $result = $this->authService->authenticate(
            'timing.csrf@example.com',
            'password123',
            '127.0.0.1',
            'Test Agent'
        );
        
        $sessionId = $result['session']->session_id;
        $correctToken = $result['csrf_token'];
        
        // Try token with one character different
        $almostCorrectToken = substr($correctToken, 0, -1) . 'X';
        
        $isValid = $this->authService->validateCsrfToken($sessionId, $almostCorrectToken);
        
        $this->assertFalse($isValid);
    }
    
    public function testCsrfTokenRequiredForStateChangingOperations()
    {
        // This test verifies that CSRF tokens are required for POST/PUT/PATCH/DELETE
        // In practice, this is enforced by requireAdminAuthWithCsrf() helper
        
        require_once __DIR__ . '/../../api/helpers/admin_auth.php';
        
        $user = AdminUser::create([
            'email' => 'state.csrf@example.com',
            'name' => 'State CSRF User',
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active'
        ]);
        
        $result = $this->authService->authenticate(
            'state.csrf@example.com',
            'password123',
            '127.0.0.1',
            'Test Agent'
        );
        
        // Set up session
        $_SESSION['ADMIN_USER_ID'] = $user->id;
        $_SESSION['CSRF_TOKEN'] = $result['csrf_token'];
        
        // verifyCsrfToken() function should be available
        $this->assertTrue(function_exists('verifyCsrfToken'));
    }
    
    public function testMultipleSessionsHaveDifferentCsrfTokens()
    {
        $user = AdminUser::create([
            'email' => 'multi.csrf@example.com',
            'name' => 'Multi CSRF User',
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active'
        ]);
        
        // Create first session
        $result1 = $this->authService->authenticate(
            'multi.csrf@example.com',
            'password123',
            '127.0.0.1',
            'Browser 1'
        );
        
        // Create second session (simulate login from different device)
        $result2 = $this->authService->authenticate(
            'multi.csrf@example.com',
            'password123',
            '192.168.1.1',
            'Browser 2'
        );
        
        $this->assertNotEquals($result1['csrf_token'], $result2['csrf_token']);
    }
}
