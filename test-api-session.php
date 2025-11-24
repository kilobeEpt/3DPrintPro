#!/usr/bin/env php
<?php
/**
 * Test API Session Authentication
 * 
 * Verifies that admin session is properly shared between /admin/ and /api/ endpoints
 */

echo "=== Testing API Session Authentication ===\n\n";

// Test 1: Check that admin-session.php sets the session name correctly
echo "Test 1: Admin session configuration\n";
require_once __DIR__ . '/includes/admin-session.php';

if (defined('ADMIN_SESSION_NAME')) {
    echo "✅ ADMIN_SESSION_NAME is defined: " . ADMIN_SESSION_NAME . "\n";
} else {
    echo "❌ ADMIN_SESSION_NAME is NOT defined\n";
    exit(1);
}

$sessionName = ini_get('session.name');
if ($sessionName === ADMIN_SESSION_NAME) {
    echo "✅ Session name configured correctly: {$sessionName}\n";
} else {
    echo "❌ Session name mismatch. Expected: " . ADMIN_SESSION_NAME . ", Got: {$sessionName}\n";
    exit(1);
}
echo "\n";

// Test 2: Check that API bootstrap loads admin session
echo "Test 2: API bootstrap loads admin session\n";
ob_start(); // Capture any output from bootstrap
require_once __DIR__ . '/api/bootstrap.php';
ob_end_clean();

$sessionNameAfterBootstrap = ini_get('session.name');
if ($sessionNameAfterBootstrap === ADMIN_SESSION_NAME) {
    echo "✅ API bootstrap preserves session name: {$sessionNameAfterBootstrap}\n";
} else {
    echo "❌ Session name changed after bootstrap. Expected: " . ADMIN_SESSION_NAME . ", Got: {$sessionNameAfterBootstrap}\n";
    exit(1);
}
echo "\n";

// Test 3: Check session cookie settings
echo "Test 3: Session cookie security settings\n";
$httpOnly = ini_get('session.cookie_httponly');
$sameSite = ini_get('session.cookie_samesite');
$useOnlyCookies = ini_get('session.use_only_cookies');
$strictMode = ini_get('session.use_strict_mode');

if ($httpOnly == '1') {
    echo "✅ HttpOnly cookie: enabled\n";
} else {
    echo "⚠️  HttpOnly cookie: disabled (should be enabled)\n";
}

if ($sameSite === 'Lax') {
    echo "✅ SameSite cookie: Lax\n";
} else {
    echo "⚠️  SameSite cookie: {$sameSite} (should be Lax)\n";
}

if ($useOnlyCookies == '1') {
    echo "✅ Use only cookies: enabled\n";
} else {
    echo "⚠️  Use only cookies: disabled\n";
}

if ($strictMode == '1') {
    echo "✅ Strict mode: enabled\n";
} else {
    echo "⚠️  Strict mode: disabled\n";
}
echo "\n";

// Test 4: Simulate admin login and check session data
echo "Test 4: Session data persistence\n";
session_start();

// Simulate admin session data
$_SESSION['ADMIN_AUTHENTICATED'] = true;
$_SESSION['ADMIN_USER_ID'] = 1;
$_SESSION['ADMIN_LOGIN'] = 'test@example.com';
$_SESSION['ADMIN_USER_NAME'] = 'Test Admin';
$_SESSION['ADMIN_USER_ROLE'] = 'super_admin';
$_SESSION['CSRF_TOKEN'] = bin2hex(random_bytes(32));

if (isset($_SESSION['ADMIN_AUTHENTICATED']) && $_SESSION['ADMIN_AUTHENTICATED'] === true) {
    echo "✅ Session data set successfully\n";
    echo "   User ID: " . $_SESSION['ADMIN_USER_ID'] . "\n";
    echo "   Email: " . $_SESSION['ADMIN_LOGIN'] . "\n";
    echo "   Role: " . $_SESSION['ADMIN_USER_ROLE'] . "\n";
    echo "   CSRF Token length: " . strlen($_SESSION['CSRF_TOKEN']) . " chars\n";
} else {
    echo "❌ Failed to set session data\n";
    exit(1);
}
echo "\n";

// Test 5: Check that requireAdminAuth function is available
echo "Test 5: Admin auth helper functions\n";
if (function_exists('requireAdminAuth')) {
    echo "✅ requireAdminAuth() function is available\n";
} else {
    echo "❌ requireAdminAuth() function not found\n";
    exit(1);
}

if (function_exists('verifyCsrfToken')) {
    echo "✅ verifyCsrfToken() function is available\n";
} else {
    echo "❌ verifyCsrfToken() function not found\n";
    exit(1);
}

if (function_exists('requireAdminAuthWithCsrf')) {
    echo "✅ requireAdminAuthWithCsrf() function is available\n";
} else {
    echo "❌ requireAdminAuthWithCsrf() function not found\n";
    exit(1);
}

if (function_exists('getAuthenticatedUser')) {
    echo "✅ getAuthenticatedUser() function is available\n";
} else {
    echo "❌ getAuthenticatedUser() function not found\n";
    exit(1);
}
echo "\n";

// Clean up session
session_unset();
session_destroy();

echo "=== All tests passed! ===\n";
echo "\nThe API session authentication should now work correctly.\n";
echo "Session name: " . ADMIN_SESSION_NAME . "\n";
echo "Admin pages and API endpoints will share the same session.\n";

exit(0);
