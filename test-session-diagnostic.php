<?php
/**
 * Session Diagnostic Tool
 * Tests session configuration across admin and API contexts
 */

// Test 1: Check session settings from admin context
echo "=== SESSION DIAGNOSTIC TOOL ===\n\n";

echo "TEST 1: Admin Session Configuration\n";
echo str_repeat("-", 50) . "\n";

define('ADMIN_INIT', true);
require_once __DIR__ . '/includes/admin-session.php';

echo "Session Status: " . (session_status() === PHP_SESSION_NONE ? "NOT STARTED" : 
       (session_status() === PHP_SESSION_ACTIVE ? "ACTIVE" : "DISABLED")) . "\n";
echo "Session Name (ini): " . ini_get('session.name') . "\n";
echo "Expected Name: 3DPRINT_ADMIN_SESSION\n";
echo "Session Name Constant: " . (defined('ADMIN_SESSION_NAME') ? ADMIN_SESSION_NAME : 'NOT DEFINED') . "\n";
echo "Cookie Path: " . ini_get('session.cookie_path') . "\n";
echo "Cookie Domain: " . ini_get('session.cookie_domain') . "\n";
echo "Cookie Lifetime: " . ini_get('session.cookie_lifetime') . "\n";
echo "Cookie Secure: " . ini_get('session.cookie_secure') . "\n";
echo "Cookie HttpOnly: " . ini_get('session.cookie_httponly') . "\n";
echo "Cookie SameSite: " . ini_get('session.cookie_samesite') . "\n";
echo "Use Only Cookies: " . ini_get('session.use_only_cookies') . "\n";
echo "Use Strict Mode: " . ini_get('session.use_strict_mode') . "\n";
echo "GC Maxlifetime: " . ini_get('session.gc_maxlifetime') . " seconds\n";
echo "Save Path: " . ini_get('session.save_path') . "\n";
echo "\n";

// Test 2: Start session and check ID
echo "TEST 2: Session Start and ID\n";
echo str_repeat("-", 50) . "\n";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "Session started successfully\n";
} else {
    echo "Session already active\n";
}

$sessionId = session_id();
echo "Session ID: " . (!empty($sessionId) ? $sessionId : "EMPTY!") . "\n";
echo "Session ID Length: " . strlen($sessionId) . "\n";
echo "\n";

// Test 3: Set test data
echo "TEST 3: Session Data Write/Read\n";
echo str_repeat("-", 50) . "\n";

$_SESSION['TEST_DATA'] = 'test_value_' . time();
$_SESSION['ADMIN_AUTHENTICATED'] = true;
$_SESSION['ADMIN_USER_ID'] = 999;
$_SESSION['ADMIN_LOGIN'] = 'test@example.com';
$_SESSION['CSRF_TOKEN'] = bin2hex(random_bytes(32));

echo "Set TEST_DATA: " . $_SESSION['TEST_DATA'] . "\n";
echo "Set ADMIN_AUTHENTICATED: " . ($_SESSION['ADMIN_AUTHENTICATED'] ? 'true' : 'false') . "\n";
echo "Set ADMIN_USER_ID: " . $_SESSION['ADMIN_USER_ID'] . "\n";
echo "Set ADMIN_LOGIN: " . $_SESSION['ADMIN_LOGIN'] . "\n";
echo "CSRF Token Length: " . strlen($_SESSION['CSRF_TOKEN']) . "\n";
echo "\n";

// Test 4: Force write and check file
echo "TEST 4: Session Persistence\n";
echo str_repeat("-", 50) . "\n";

session_write_close();
echo "Session written and closed\n";

$savePath = ini_get('session.save_path') ?: '/tmp';
$sessionFile = $savePath . '/sess_' . $sessionId;
echo "Expected Session File: " . $sessionFile . "\n";
echo "File Exists: " . (file_exists($sessionFile) ? "YES" : "NO") . "\n";

if (file_exists($sessionFile)) {
    $size = filesize($sessionFile);
    echo "File Size: " . $size . " bytes\n";
    
    if ($size > 0) {
        $content = file_get_contents($sessionFile);
        echo "File Content Preview: " . substr($content, 0, 100) . "...\n";
        echo "Contains ADMIN_AUTHENTICATED: " . (strpos($content, 'ADMIN_AUTHENTICATED') !== false ? "YES" : "NO") . "\n";
    } else {
        echo "⚠️ WARNING: Session file is empty!\n";
    }
} else {
    echo "⚠️ WARNING: Session file not found!\n";
    echo "Checking save_path permissions...\n";
    echo "Save Path Exists: " . (is_dir($savePath) ? "YES" : "NO") . "\n";
    echo "Save Path Writable: " . (is_writable($savePath) ? "YES" : "NO") . "\n";
}

echo "\n";

// Test 5: Simulate API request context
echo "TEST 5: API Bootstrap Simulation\n";
echo str_repeat("-", 50) . "\n";

// Reset for clean test
session_abort();

// Simulate API bootstrap loading
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/eloquent.php';

echo "API bootstrap loaded\n";
echo "Session Name After Bootstrap: " . ini_get('session.name') . "\n";
echo "Cookie Path After Bootstrap: " . ini_get('session.cookie_path') . "\n";

// Try to resume session with same ID
session_id($sessionId);
session_start();

echo "Session resumed with ID: " . session_id() . "\n";
echo "Session ID matches: " . (session_id() === $sessionId ? "YES" : "NO") . "\n";
echo "\n";

// Test 6: Check session data persistence
echo "TEST 6: Session Data Persistence Check\n";
echo str_repeat("-", 50) . "\n";

echo "TEST_DATA exists: " . (isset($_SESSION['TEST_DATA']) ? "YES" : "NO") . "\n";
if (isset($_SESSION['TEST_DATA'])) {
    echo "TEST_DATA value: " . $_SESSION['TEST_DATA'] . "\n";
}

echo "ADMIN_AUTHENTICATED exists: " . (isset($_SESSION['ADMIN_AUTHENTICATED']) ? "YES" : "NO") . "\n";
if (isset($_SESSION['ADMIN_AUTHENTICATED'])) {
    echo "ADMIN_AUTHENTICATED value: " . ($_SESSION['ADMIN_AUTHENTICATED'] ? 'true' : 'false') . "\n";
}

echo "ADMIN_USER_ID exists: " . (isset($_SESSION['ADMIN_USER_ID']) ? "YES" : "NO") . "\n";
if (isset($_SESSION['ADMIN_USER_ID'])) {
    echo "ADMIN_USER_ID value: " . $_SESSION['ADMIN_USER_ID'] . "\n";
}

echo "\n";

// Test 7: Summary
echo "TEST 7: Summary and Recommendations\n";
echo str_repeat("-", 50) . "\n";

$issues = [];

if (ini_get('session.name') !== '3DPRINT_ADMIN_SESSION') {
    $issues[] = "❌ Session name mismatch: " . ini_get('session.name');
}

if (ini_get('session.cookie_path') !== '/') {
    $issues[] = "⚠️ Cookie path is not '/': " . ini_get('session.cookie_path');
}

if (!ini_get('session.use_only_cookies')) {
    $issues[] = "⚠️ use_only_cookies is disabled";
}

if (!file_exists($sessionFile)) {
    $issues[] = "❌ Session file not persisted to disk";
}

if (!isset($_SESSION['TEST_DATA'])) {
    $issues[] = "❌ Session data lost after close/reopen";
}

if (empty($issues)) {
    echo "✅ All checks passed! Session configuration looks correct.\n";
} else {
    echo "⚠️ Issues found:\n";
    foreach ($issues as $issue) {
        echo "  " . $issue . "\n";
    }
}

echo "\n=== END DIAGNOSTIC ===\n";
