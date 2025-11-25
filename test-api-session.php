<?php
/**
 * Admin API Session Test
 * Validates session configuration and authentication flow
 * 
 * Access: https://3dprint-omsk.ru/test-api-session.php
 */

header('Content-Type: application/json');

$tests = [];
$errors = [];
$warnings = [];

// Test 1: Session Configuration
$tests['session_config'] = [
    'name' => 'Session Configuration',
    'status' => 'pending'
];

require_once __DIR__ . '/includes/admin-session.php';

$sessionName = ini_get('session.name');
$cookiePath = ini_get('session.cookie_path');
$cookieDomain = ini_get('session.cookie_domain');
$cookieHttpOnly = ini_get('session.cookie_httponly');
$cookieSameSite = ini_get('session.cookie_samesite');
$useOnlyCookies = ini_get('session.use_only_cookies');

$tests['session_config']['data'] = [
    'session_name' => $sessionName,
    'expected_name' => ADMIN_SESSION_NAME,
    'cookie_path' => $cookiePath,
    'cookie_domain' => $cookieDomain,
    'cookie_httponly' => $cookieHttpOnly,
    'cookie_samesite' => $cookieSameSite,
    'use_only_cookies' => $useOnlyCookies
];

if ($sessionName !== ADMIN_SESSION_NAME) {
    $errors[] = "Session name mismatch: expected '" . ADMIN_SESSION_NAME . "', got '$sessionName'";
    $tests['session_config']['status'] = 'failed';
} elseif ($cookiePath !== '/') {
    $errors[] = "Cookie path must be '/', got '$cookiePath'";
    $tests['session_config']['status'] = 'failed';
} elseif (!$cookieHttpOnly) {
    $warnings[] = "Cookie HttpOnly should be enabled";
    $tests['session_config']['status'] = 'warning';
} elseif (!$useOnlyCookies) {
    $warnings[] = "use_only_cookies should be enabled";
    $tests['session_config']['status'] = 'warning';
} else {
    $tests['session_config']['status'] = 'passed';
}

// Test 2: Session Start
$tests['session_start'] = [
    'name' => 'Session Start',
    'status' => 'pending'
];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionId = session_id();

$tests['session_start']['data'] = [
    'session_id' => $sessionId,
    'session_id_length' => strlen($sessionId),
    'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'active' : 'not_active'
];

if (empty($sessionId)) {
    $errors[] = "Session ID is empty";
    $tests['session_start']['status'] = 'failed';
} elseif (strlen($sessionId) < 26) {
    $warnings[] = "Session ID seems short (length: " . strlen($sessionId) . ")";
    $tests['session_start']['status'] = 'warning';
} else {
    $tests['session_start']['status'] = 'passed';
}

// Test 3: Session Data Write/Read
$tests['session_data'] = [
    'name' => 'Session Data Persistence',
    'status' => 'pending'
];

$testValue = 'test_' . time();
$_SESSION['API_TEST_VALUE'] = $testValue;
$_SESSION['API_TEST_TIME'] = time();

$readValue = $_SESSION['API_TEST_VALUE'] ?? null;

$tests['session_data']['data'] = [
    'write_value' => $testValue,
    'read_value' => $readValue,
    'match' => $readValue === $testValue
];

if ($readValue !== $testValue) {
    $errors[] = "Session data write/read failed";
    $tests['session_data']['status'] = 'failed';
} else {
    $tests['session_data']['status'] = 'passed';
}

// Test 4: Admin Auth Helper
$tests['admin_auth'] = [
    'name' => 'Admin Auth Helper',
    'status' => 'pending'
];

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/eloquent.php';

$adminAuthExists = file_exists(__DIR__ . '/api/helpers/admin_auth.php');
$tests['admin_auth']['data'] = [
    'file_exists' => $adminAuthExists
];

if (!$adminAuthExists) {
    $errors[] = "admin_auth.php helper not found";
    $tests['admin_auth']['status'] = 'failed';
} else {
    require_once __DIR__ . '/api/helpers/admin_auth.php';
    
    $functionsExist = [
        'requireAdminAuth' => function_exists('requireAdminAuth'),
        'verifyCsrfToken' => function_exists('verifyCsrfToken'),
        'getAuthenticatedUser' => function_exists('getAuthenticatedUser'),
        'logAdminAction' => function_exists('logAdminAction')
    ];
    
    $tests['admin_auth']['data']['functions'] = $functionsExist;
    
    $allExist = !in_array(false, $functionsExist, true);
    
    if (!$allExist) {
        $missing = array_keys(array_filter($functionsExist, function($v) { return !$v; }));
        $errors[] = "Missing auth functions: " . implode(', ', $missing);
        $tests['admin_auth']['status'] = 'failed';
    } else {
        $tests['admin_auth']['status'] = 'passed';
    }
}

// Test 5: Bootstrap Consistency
$tests['bootstrap'] = [
    'name' => 'Bootstrap Configuration',
    'status' => 'pending'
];

$sessionNameAfterBootstrap = ini_get('session.name');
$cookiePathAfterBootstrap = ini_get('session.cookie_path');

$tests['bootstrap']['data'] = [
    'session_name_consistent' => $sessionNameAfterBootstrap === ADMIN_SESSION_NAME,
    'cookie_path_consistent' => $cookiePathAfterBootstrap === '/',
    'session_name' => $sessionNameAfterBootstrap,
    'cookie_path' => $cookiePathAfterBootstrap
];

if ($sessionNameAfterBootstrap !== ADMIN_SESSION_NAME) {
    $errors[] = "Session name changed after bootstrap: '$sessionNameAfterBootstrap'";
    $tests['bootstrap']['status'] = 'failed';
} elseif ($cookiePathAfterBootstrap !== '/') {
    $errors[] = "Cookie path changed after bootstrap: '$cookiePathAfterBootstrap'";
    $tests['bootstrap']['status'] = 'failed';
} else {
    $tests['bootstrap']['status'] = 'passed';
}

// Test 6: Session Cookie Headers (check if Set-Cookie would be sent)
$tests['cookie_headers'] = [
    'name' => 'Session Cookie Configuration',
    'status' => 'pending'
];

$expectedCookieName = ADMIN_SESSION_NAME;
$headersSent = headers_sent();

$tests['cookie_headers']['data'] = [
    'expected_cookie_name' => $expectedCookieName,
    'session_id' => $sessionId,
    'headers_sent' => $headersSent,
    'cookie_params' => session_get_cookie_params()
];

$cookieParams = session_get_cookie_params();

if ($cookieParams['path'] !== '/') {
    $warnings[] = "Session cookie path is '" . $cookieParams['path'] . "', should be '/'";
    $tests['cookie_headers']['status'] = 'warning';
} else {
    $tests['cookie_headers']['status'] = 'passed';
}

// Test 7: Database Connection (for AdminAuthService)
$tests['database'] = [
    'name' => 'Database Connection',
    'status' => 'pending'
];

try {
    $dbConnection = \Illuminate\Database\Capsule\Manager::connection();
    $dbConnection->getPdo();
    
    $tests['database']['data'] = [
        'connected' => true,
        'driver' => $dbConnection->getDriverName()
    ];
    
    $tests['database']['status'] = 'passed';
} catch (\Exception $e) {
    $errors[] = "Database connection failed: " . $e->getMessage();
    $tests['database']['data'] = [
        'connected' => false,
        'error' => $e->getMessage()
    ];
    $tests['database']['status'] = 'failed';
}

// Summary
$passedCount = count(array_filter($tests, function($t) { return $t['status'] === 'passed'; }));
$failedCount = count(array_filter($tests, function($t) { return $t['status'] === 'failed'; }));
$warningCount = count(array_filter($tests, function($t) { return $t['status'] === 'warning'; }));
$totalCount = count($tests);

$overallStatus = $failedCount > 0 ? 'FAILED' : ($warningCount > 0 ? 'WARNING' : 'PASSED');

// Output
$output = [
    'success' => $failedCount === 0,
    'timestamp' => date('Y-m-d H:i:s'),
    'overall_status' => $overallStatus,
    'summary' => [
        'total' => $totalCount,
        'passed' => $passedCount,
        'failed' => $failedCount,
        'warnings' => $warningCount
    ],
    'tests' => $tests,
    'errors' => $errors,
    'warnings' => $warnings,
    'recommendations' => []
];

if ($failedCount > 0) {
    $output['recommendations'][] = "Fix failed tests before deploying to production";
}

if ($cookiePathAfterBootstrap !== '/') {
    $output['recommendations'][] = "Ensure session.cookie_path is set to '/' in includes/admin-session.php";
}

if ($warningCount > 0) {
    $output['recommendations'][] = "Review warnings and apply security best practices";
}

if (empty($output['recommendations'])) {
    $output['recommendations'][] = "All tests passed! Session configuration is correct.";
}

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
