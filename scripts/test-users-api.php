#!/usr/bin/env php
<?php
/**
 * Admin Users API Smoke Test
 * 
 * Tests basic functionality of the admin users management API.
 * Run: php scripts/test-users-api.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\AdminUser;
use App\Services\AdminAuthService;

echo "===================================\n";
echo "Admin Users API Smoke Test\n";
echo "===================================\n\n";

$errors = 0;

// Test 1: Check if admin users table exists
echo "1. Testing admin users table exists...\n";
try {
    $count = AdminUser::count();
    echo "   ✓ Admin users table exists (count: $count)\n\n";
} catch (Exception $e) {
    echo "   ✗ Failed: " . $e->getMessage() . "\n\n";
    $errors++;
}

// Test 2: Check roles constants
echo "2. Testing role constants...\n";
try {
    if (!defined('App\Models\AdminUser::ROLE_SUPER_ADMIN')) {
        throw new Exception('ROLE_SUPER_ADMIN not defined');
    }
    echo "   ✓ ROLE_SUPER_ADMIN: " . AdminUser::ROLE_SUPER_ADMIN . "\n";
    echo "   ✓ ROLE_ADMIN: " . AdminUser::ROLE_ADMIN . "\n";
    echo "   ✓ ROLE_EDITOR: " . AdminUser::ROLE_EDITOR . "\n\n";
} catch (Exception $e) {
    echo "   ✗ Failed: " . $e->getMessage() . "\n\n";
    $errors++;
}

// Test 3: Check status constants
echo "3. Testing status constants...\n";
try {
    echo "   ✓ STATUS_ACTIVE: " . AdminUser::STATUS_ACTIVE . "\n";
    echo "   ✓ STATUS_INACTIVE: " . AdminUser::STATUS_INACTIVE . "\n";
    echo "   ✓ STATUS_LOCKED: " . AdminUser::STATUS_LOCKED . "\n\n";
} catch (Exception $e) {
    echo "   ✗ Failed: " . $e->getMessage() . "\n\n";
    $errors++;
}

// Test 4: Test user creation and methods
echo "4. Testing user model methods...\n";
try {
    // Create test user
    $user = new AdminUser();
    $user->email = 'test@example.com';
    $user->name = 'Test User';
    $user->setPassword('TestPassword123');
    $user->role = AdminUser::ROLE_ADMIN;
    $user->status = AdminUser::STATUS_ACTIVE;
    
    // Test password verification
    if (!$user->verifyPassword('TestPassword123')) {
        throw new Exception('Password verification failed');
    }
    echo "   ✓ Password hashing and verification working\n";
    
    // Test role checks
    if ($user->isSuperAdmin()) {
        throw new Exception('Role check failed: admin should not be super admin');
    }
    if (!$user->isAdmin()) {
        throw new Exception('Role check failed: admin should be admin');
    }
    echo "   ✓ Role checking methods working\n";
    
    // Test status checks
    if (!$user->isActive()) {
        throw new Exception('Status check failed: user should be active');
    }
    if ($user->isLocked()) {
        throw new Exception('Status check failed: user should not be locked');
    }
    echo "   ✓ Status checking methods working\n\n";
} catch (Exception $e) {
    echo "   ✗ Failed: " . $e->getMessage() . "\n\n";
    $errors++;
}

// Test 5: Test AdminAuthService integration
echo "5. Testing AdminAuthService...\n";
try {
    $authService = new AdminAuthService();
    
    // Check methods exist
    $methods = ['authenticate', 'validateSession', 'destroySession', 'destroyAllUserSessions', 'logAction'];
    foreach ($methods as $method) {
        if (!method_exists($authService, $method)) {
            throw new Exception("Method $method not found");
        }
    }
    echo "   ✓ All required methods exist\n\n";
} catch (Exception $e) {
    echo "   ✗ Failed: " . $e->getMessage() . "\n\n";
    $errors++;
}

// Test 6: Test query scopes
echo "6. Testing query scopes...\n";
try {
    // These should not throw errors even if no data exists
    AdminUser::active()->count();
    AdminUser::byRole(AdminUser::ROLE_ADMIN)->count();
    AdminUser::byEmail('test@example.com')->count();
    
    echo "   ✓ Query scopes working\n\n";
} catch (Exception $e) {
    echo "   ✗ Failed: " . $e->getMessage() . "\n\n";
    $errors++;
}

// Summary
echo "===================================\n";
if ($errors === 0) {
    echo "✅ All tests passed!\n";
    exit(0);
} else {
    echo "❌ $errors test(s) failed\n";
    exit(1);
}
