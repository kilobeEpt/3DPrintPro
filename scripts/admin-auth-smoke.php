#!/usr/bin/env php
<?php
/**
 * Admin Authentication Smoke Test
 * 
 * Tests real-world authentication flows:
 * - Login with valid credentials
 * - Login with invalid credentials
 * - Rate limiting and account lockout
 * - Session management and CSRF tokens
 * - Session expiration
 * - Multiple concurrent sessions
 * - Logout functionality
 * 
 * Usage: php scripts/admin-auth-smoke.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\AdminUser;
use App\Models\AdminSession;
use App\Models\AdminLoginAttempt;
use App\Services\AdminAuthService;

// Colors for output
function success($msg) { echo "\033[32m✓\033[0m " . $msg . PHP_EOL; }
function error($msg) { echo "\033[31m✗\033[0m " . $msg . PHP_EOL; }
function info($msg) { echo "\033[34mℹ\033[0m " . $msg . PHP_EOL; }
function section($msg) { echo PHP_EOL . "\033[1m" . $msg . "\033[0m" . PHP_EOL . str_repeat('-', 60) . PHP_EOL; }

$authService = new AdminAuthService();
$testsPassed = 0;
$testsFailed = 0;

section('Admin Authentication Smoke Test');

// Clean up test data
AdminSession::where('ip_address', '127.0.0.1')->delete();
AdminLoginAttempt::where('ip_address', '127.0.0.1')->delete();
AdminUser::where('email', 'smoke@test.com')->delete();

// Create test user
section('1. Setup Test User');
try {
    $testUser = AdminUser::create([
        'email' => 'smoke@test.com',
        'name' => 'Smoke Test User',
        'password_hash' => password_hash('TestPassword123', PASSWORD_BCRYPT),
        'role' => 'admin',
        'status' => 'active'
    ]);
    success("Created test user: {$testUser->email}");
    $testsPassed++;
} catch (Exception $e) {
    error("Failed to create test user: " . $e->getMessage());
    $testsFailed++;
    exit(1);
}

// Test 1: Valid login
section('2. Test Valid Login');
try {
    $result = $authService->authenticate('smoke@test.com', 'TestPassword123', '127.0.0.1', 'PHPUnit Test');
    
    if ($result['success'] && isset($result['session_id'])) {
        success("Valid login successful. Session ID: {$result['session_id']}");
        $sessionId = $result['session_id'];
        $testsPassed++;
    } else {
        error("Valid login failed: " . ($result['error'] ?? 'Unknown error'));
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Login exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 2: Session validation
section('3. Test Session Validation');
try {
    $session = $authService->validateSession($sessionId);
    
    if ($session && $session->user_id === $testUser->id) {
        success("Session validation passed. User: {$session->user->name}");
        $testsPassed++;
    } else {
        error("Session validation failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Session validation exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 3: Invalid password
section('4. Test Invalid Password');
try {
    $result = $authService->authenticate('smoke@test.com', 'WrongPassword', '127.0.0.1', 'PHPUnit Test');
    
    if (!$result['success']) {
        success("Invalid password correctly rejected: " . $result['error']);
        $testsPassed++;
    } else {
        error("Invalid password was incorrectly accepted");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Invalid password test exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 4: Non-existent user
section('5. Test Non-Existent User');
try {
    $result = $authService->authenticate('nonexistent@test.com', 'password', '127.0.0.1', 'PHPUnit Test');
    
    if (!$result['success']) {
        success("Non-existent user correctly rejected: " . $result['error']);
        $testsPassed++;
    } else {
        error("Non-existent user was incorrectly accepted");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Non-existent user test exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 5: Rate limiting (simulate multiple failures)
section('6. Test Rate Limiting');
try {
    info("Simulating 5 failed login attempts...");
    
    for ($i = 1; $i <= 5; $i++) {
        $result = $authService->authenticate('smoke@test.com', 'wrong', '127.0.0.2', 'PHPUnit Test');
        info("Attempt {$i}: " . $result['error']);
    }
    
    // 6th attempt should be locked
    $result = $authService->authenticate('smoke@test.com', 'TestPassword123', '127.0.0.2', 'PHPUnit Test');
    
    if (!$result['success'] && strpos($result['error'], 'locked') !== false) {
        success("Account correctly locked after 5 failed attempts");
        $testsPassed++;
    } else {
        error("Rate limiting did not trigger");
        $testsFailed++;
    }
    
    // Unlock account for next tests
    $testUser->locked_until = null;
    $testUser->failed_login_attempts = 0;
    $testUser->save();
    
} catch (Exception $e) {
    error("Rate limiting test exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 6: Inactive user
section('7. Test Inactive User');
try {
    $testUser->status = 'inactive';
    $testUser->save();
    
    $result = $authService->authenticate('smoke@test.com', 'TestPassword123', '127.0.0.1', 'PHPUnit Test');
    
    if (!$result['success']) {
        success("Inactive user correctly rejected: " . $result['error']);
        $testsPassed++;
    } else {
        error("Inactive user was incorrectly accepted");
        $testsFailed++;
    }
    
    // Reactivate for next tests
    $testUser->status = 'active';
    $testUser->save();
    
} catch (Exception $e) {
    error("Inactive user test exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 7: Multiple concurrent sessions
section('8. Test Multiple Concurrent Sessions');
try {
    $session1 = $authService->authenticate('smoke@test.com', 'TestPassword123', '127.0.0.1', 'Browser 1');
    $session2 = $authService->authenticate('smoke@test.com', 'TestPassword123', '127.0.0.2', 'Browser 2');
    
    $sessions = AdminSession::where('user_id', $testUser->id)->get();
    
    if ($sessions->count() >= 2) {
        success("Multiple concurrent sessions allowed. Active sessions: " . $sessions->count());
        $testsPassed++;
    } else {
        error("Multiple sessions not created");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Multiple sessions test exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 8: Logout
section('9. Test Logout');
try {
    $result = $authService->authenticate('smoke@test.com', 'TestPassword123', '127.0.0.1', 'PHPUnit Test');
    $sessionId = $result['session_id'];
    
    $authService->destroySession($sessionId);
    
    $session = AdminSession::where('session_id', $sessionId)->first();
    
    if (!$session) {
        success("Logout successfully destroyed session");
        $testsPassed++;
    } else {
        error("Session still exists after logout");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Logout test exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 9: CSRF token presence
section('10. Test CSRF Token Generation');
try {
    $result = $authService->authenticate('smoke@test.com', 'TestPassword123', '127.0.0.1', 'PHPUnit Test');
    $sessionId = $result['session_id'];
    
    $session = AdminSession::where('session_id', $sessionId)->first();
    
    if (!empty($session->csrf_token)) {
        success("CSRF token generated: " . substr($session->csrf_token, 0, 16) . "...");
        $testsPassed++;
    } else {
        error("CSRF token not generated");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("CSRF token test exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 10: Login attempt logging
section('11. Test Login Attempt Logging');
try {
    $attemptsBefore = AdminLoginAttempt::where('email', 'smoke@test.com')->count();
    
    $authService->authenticate('smoke@test.com', 'TestPassword123', '127.0.0.1', 'PHPUnit Test');
    
    $attemptsAfter = AdminLoginAttempt::where('email', 'smoke@test.com')->count();
    
    if ($attemptsAfter > $attemptsBefore) {
        success("Login attempts are being logged. Total attempts: {$attemptsAfter}");
        $testsPassed++;
    } else {
        error("Login attempts not logged");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Login attempt logging test exception: " . $e->getMessage());
    $testsFailed++;
}

// Cleanup
section('Cleanup');
try {
    AdminSession::where('user_id', $testUser->id)->delete();
    AdminLoginAttempt::where('email', 'smoke@test.com')->delete();
    $testUser->delete();
    success("Test data cleaned up");
} catch (Exception $e) {
    error("Cleanup failed: " . $e->getMessage());
}

// Summary
section('Test Summary');
$total = $testsPassed + $testsFailed;
$percentage = $total > 0 ? round(($testsPassed / $total) * 100, 1) : 0;

echo "Total Tests: {$total}" . PHP_EOL;
echo "Passed: \033[32m{$testsPassed}\033[0m" . PHP_EOL;
echo "Failed: \033[31m{$testsFailed}\033[0m" . PHP_EOL;
echo "Success Rate: {$percentage}%" . PHP_EOL;
echo PHP_EOL;

if ($testsFailed === 0) {
    success("All authentication tests passed! ✨");
    exit(0);
} else {
    error("Some tests failed. Please review the output above.");
    exit(1);
}
