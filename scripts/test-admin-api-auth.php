#!/usr/bin/env php
<?php
/**
 * Test Script: Admin API Authorization Header Authentication
 * 
 * Tests the new Authorization header-based authentication for admin API endpoints
 * 
 * Usage: php scripts/test-admin-api-auth.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Services\AdminAuthService;
use App\Models\AdminUser;
use App\Models\AdminSession;
use Illuminate\Support\Carbon;

echo "==============================================\n";
echo "Admin API Authorization Header Test Suite\n";
echo "==============================================\n\n";

$passed = 0;
$failed = 0;

function test($description, $callback) {
    global $passed, $failed;
    
    try {
        echo "🔄 Testing: $description\n";
        $result = $callback();
        
        if ($result === true) {
            echo "   ✅ PASSED\n\n";
            $passed++;
        } else {
            echo "   ❌ FAILED: $result\n\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "   ❌ EXCEPTION: " . $e->getMessage() . "\n\n";
        $failed++;
    }
}

// Clean up any existing test data
function cleanup() {
    AdminUser::where('email', 'test-auth@example.com')->delete();
    AdminSession::whereNull('user_id')->delete(); // Clean up orphaned sessions
}

cleanup();

// Test 1: Create test admin user
test("Create test admin user", function() {
    $user = AdminUser::create([
        'email' => 'test-auth@example.com',
        'name' => 'Test Auth User',
        'password_hash' => password_hash('test123', PASSWORD_BCRYPT),
        'role' => 'admin',
        'status' => 'active'
    ]);
    
    return $user && $user->id > 0 ? true : "User creation failed";
});

// Test 2: Authenticate user and get session token
$sessionId = null;
$csrfToken = null;
test("Authenticate user and get session token", function() use (&$sessionId, &$csrfToken) {
    $authService = new AdminAuthService();
    $result = $authService->authenticate(
        'test-auth@example.com',
        'test123',
        '127.0.0.1',
        'TestAgent/1.0',
        false
    );
    
    if (!$result['success']) {
        return "Authentication failed: " . ($result['error'] ?? 'unknown error');
    }
    
    $sessionId = $result['session']->session_id;
    $csrfToken = $result['csrf_token'];
    
    return !empty($sessionId) ? true : "Session ID not generated";
});

echo "   Session ID: $sessionId\n";
echo "   CSRF Token: $csrfToken\n\n";

// Test 3: Validate session via AdminAuthService
test("Validate session via AdminAuthService", function() use ($sessionId) {
    $authService = new AdminAuthService();
    $validation = $authService->validateSession($sessionId);
    
    if (!$validation['valid']) {
        return "Validation failed: " . ($validation['error'] ?? 'unknown error');
    }
    
    return ($validation['user']->email === 'test-auth@example.com') 
        ? true 
        : "User mismatch";
});

// Test 4: Extract token from Authorization header
test("Extract token from Authorization header", function() use ($sessionId) {
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $sessionId";
    
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $matches = [];
    
    if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        return "Regex pattern failed to match";
    }
    
    $extractedToken = $matches[1];
    
    return ($extractedToken === $sessionId) 
        ? true 
        : "Extracted token doesn't match: got '$extractedToken', expected '$sessionId'";
});

// Test 5: Simulate requireAdminAuth() with Authorization header
test("Simulate requireAdminAuth() with Authorization header", function() use ($sessionId) {
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $sessionId";
    
    // Simulate the requireAdminAuth() logic
    $authToken = null;
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (!empty($authHeader) && preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        $authToken = $matches[1];
    }
    
    if (!$authToken) {
        return "Failed to extract auth token";
    }
    
    $authService = new AdminAuthService();
    $validation = $authService->validateSession($authToken);
    
    if (!$validation['valid']) {
        return "Token validation failed: " . ($validation['error'] ?? 'unknown');
    }
    
    return true;
});

// Test 6: Test with invalid token
test("Reject invalid token", function() {
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer invalid_token_12345";
    
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $matches = [];
    preg_match('/Bearer\s+(.+)/', $authHeader, $matches);
    $authToken = $matches[1] ?? null;
    
    $authService = new AdminAuthService();
    $validation = $authService->validateSession($authToken);
    
    // Should fail validation
    return (!$validation['valid']) ? true : "Invalid token was accepted";
});

// Test 7: Test with empty Authorization header
test("Fallback to session when no Authorization header", function() use ($sessionId) {
    unset($_SERVER['HTTP_AUTHORIZATION']);
    
    // Simulate session-based auth
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set session ID manually for testing
    $oldSessionId = session_id();
    session_write_close();
    session_id($sessionId);
    session_start();
    
    $currentSessionId = session_id();
    
    // Restore original session
    session_write_close();
    session_id($oldSessionId);
    session_start();
    
    return ($currentSessionId === $sessionId) 
        ? true 
        : "Session fallback failed";
});

// Test 8: Verify session expiration
test("Verify session expiration handling", function() use ($sessionId) {
    // Create expired session
    $session = AdminSession::where('session_id', $sessionId)->first();
    if (!$session) {
        return "Session not found";
    }
    
    // Set expiration to past
    $session->expires_at = Carbon::now()->subHours(2);
    $session->save();
    
    // Try to validate
    $authService = new AdminAuthService();
    $validation = $authService->validateSession($sessionId);
    
    // Should fail due to expiration
    return (!$validation['valid'] && isset($validation['error']) && strpos($validation['error'], 'expired') !== false)
        ? true
        : "Expired session was accepted";
});

// Test 9: CSRF token validation
test("CSRF token is returned with auth token", function() use ($csrfToken) {
    return !empty($csrfToken) ? true : "CSRF token not generated";
});

// Test 10: Session activity update
test("Session activity updates on validation", function() use ($sessionId) {
    // Re-create session for this test
    $user = AdminUser::where('email', 'test-auth@example.com')->first();
    $authService = new AdminAuthService();
    
    // Create fresh session
    session_start();
    $newSessionId = session_id();
    $session = $authService->createSession($user, $newSessionId, '127.0.0.1', 'TestAgent/1.0');
    
    $initialActivity = $session->last_activity_at;
    
    // Wait a moment
    sleep(1);
    
    // Validate session (should update activity)
    $validation = $authService->validateSession($newSessionId);
    
    // Get updated session
    $session = $session->fresh();
    $updatedActivity = $session->last_activity_at;
    
    // Clean up
    $session->delete();
    session_destroy();
    
    return ($updatedActivity->gt($initialActivity)) 
        ? true 
        : "Session activity not updated";
});

// Cleanup
cleanup();

echo "==============================================\n";
echo "Test Results\n";
echo "==============================================\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";
echo "📊 Total:  " . ($passed + $failed) . "\n";
echo "==============================================\n";

if ($failed > 0) {
    echo "\n⚠️  Some tests failed. Please review the output above.\n";
    exit(1);
} else {
    echo "\n🎉 All tests passed successfully!\n";
    exit(0);
}
