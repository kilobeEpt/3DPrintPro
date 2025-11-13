#!/usr/bin/env php
<?php
// ========================================
// Admin Session Synchronization Test
// Verifies that admin pages and API endpoints use the same session
// ========================================

echo "\n";
echo "========================================\n";
echo "Admin Session Synchronization Test\n";
echo "========================================\n\n";

// Test 1: Verify shared bootstrap file exists
echo "✓ Test 1: Shared bootstrap file exists... ";
$bootstrapPath = __DIR__ . '/../includes/admin-session.php';
if (file_exists($bootstrapPath)) {
    echo "✅ PASS\n";
} else {
    echo "❌ FAIL\n";
    exit(1);
}

// Test 2: Verify bootstrap defines session name constant
echo "✓ Test 2: Bootstrap defines ADMIN_SESSION_NAME... ";
require_once $bootstrapPath;
if (defined('ADMIN_SESSION_NAME') && ADMIN_SESSION_NAME === '3DPRINT_ADMIN_SESSION') {
    echo "✅ PASS (value: " . ADMIN_SESSION_NAME . ")\n";
} else {
    echo "❌ FAIL\n";
    exit(1);
}

// Test 3: Verify session config uses shared bootstrap
echo "✓ Test 3: Admin session-config.php includes bootstrap... ";
$sessionConfigPath = __DIR__ . '/../admin/includes/session-config.php';
$sessionConfigContent = file_get_contents($sessionConfigPath);
if (strpos($sessionConfigContent, 'includes/admin-session.php') !== false) {
    echo "✅ PASS\n";
} else {
    echo "❌ FAIL\n";
    exit(1);
}

// Test 4: Verify API admin_auth.php uses shared bootstrap
echo "✓ Test 4: API admin_auth.php includes bootstrap... ";
$adminAuthPath = __DIR__ . '/../api/helpers/admin_auth.php';
$adminAuthContent = file_get_contents($adminAuthPath);
if (strpos($adminAuthContent, 'includes/admin-session.php') !== false) {
    echo "✅ PASS\n";
} else {
    echo "❌ FAIL\n";
    exit(1);
}

// Test 5: Verify session config no longer has hardcoded ini_set calls
echo "✓ Test 5: Session config no longer duplicates ini_set... ";
if (strpos($sessionConfigContent, "ini_set('session.name'") === false) {
    echo "✅ PASS\n";
} else {
    echo "❌ FAIL (still contains hardcoded session.name)\n";
    exit(1);
}

// Test 6: Verify bootstrap function exists
echo "✓ Test 6: Bootstrap function exists... ";
if (function_exists('bootstrapAdminSession')) {
    echo "✅ PASS\n";
} else {
    echo "❌ FAIL\n";
    exit(1);
}

// Test 7: Verify session timeout logic in requireAdminAuth
echo "✓ Test 7: API auth has session timeout check... ";
if (strpos($adminAuthContent, 'LAST_ACTIVITY') !== false && 
    strpos($adminAuthContent, 'Session expired') !== false) {
    echo "✅ PASS\n";
} else {
    echo "❌ FAIL\n";
    exit(1);
}

// Test 8: Verify CSRF token validation uses same session
echo "✓ Test 8: CSRF validation uses shared session... ";
if (strpos($adminAuthContent, 'verifyCsrfToken') !== false && 
    strpos($adminAuthContent, 'CSRF_TOKEN') !== false) {
    echo "✅ PASS\n";
} else {
    echo "❌ FAIL\n";
    exit(1);
}

echo "\n";
echo "========================================\n";
echo "✅ All tests passed!\n";
echo "========================================\n\n";

echo "📋 Summary:\n";
echo "  • Shared session bootstrap created: includes/admin-session.php\n";
echo "  • Session name unified: 3DPRINT_ADMIN_SESSION\n";
echo "  • Admin pages use shared bootstrap\n";
echo "  • API endpoints use shared bootstrap\n";
echo "  • Session timeout enforced in API layer\n";
echo "  • CSRF tokens accessible across both contexts\n\n";

echo "🧪 Manual Testing Steps:\n";
echo "  1. Start a local PHP server or use existing setup\n";
echo "  2. Log in via /admin/login.php\n";
echo "  3. Check browser DevTools → Application → Cookies\n";
echo "     → Should see cookie named '3DPRINT_ADMIN_SESSION'\n";
echo "  4. Open browser console and run:\n";
echo "     fetch('/api/orders.php').then(r => r.json()).then(console.log)\n";
echo "  5. Should return 200 with orders data (not 401)\n";
echo "  6. Test CSRF protection with:\n";
echo "     fetch('/api/services.php', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: '{}'}).then(r => r.json()).then(console.log)\n";
echo "  7. Should return 403 'Invalid CSRF token' (not 401)\n\n";

exit(0);
