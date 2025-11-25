<?php
/**
 * Session Cookie Diagnostics
 * 
 * This script helps diagnose session cookie issues between admin and API endpoints.
 * It simulates the login flow and checks if cookies would be sent to /api/* paths.
 * 
 * Usage: Access from browser at https://3dprint-omsk.ru/diagnose-session-cookies.php
 */

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: text/html; charset=utf-8');

$diagnostics = [];
$errors = [];
$warnings = [];
$recommendations = [];

// ==================================================
// Test 1: Load session configuration
// ==================================================
$diagnostics['config_load'] = [
    'name' => 'Session Configuration Loading',
    'status' => 'pending',
    'details' => []
];

try {
    require_once __DIR__ . '/includes/admin-session.php';
    
    $sessionName = ini_get('session.name');
    $cookiePath = ini_get('session.cookie_path');
    $cookieDomain = ini_get('session.cookie_domain');
    $cookieHttpOnly = ini_get('session.cookie_httponly');
    $cookieSameSite = ini_get('session.cookie_samesite');
    $cookieSecure = ini_get('session.cookie_secure');
    $useOnlyCookies = ini_get('session.use_only_cookies');
    $strictMode = ini_get('session.use_strict_mode');
    
    $diagnostics['config_load']['details'] = [
        'session_name' => $sessionName,
        'cookie_path' => $cookiePath,
        'cookie_domain' => $cookieDomain,
        'cookie_httponly' => $cookieHttpOnly,
        'cookie_samesite' => $cookieSameSite,
        'cookie_secure' => $cookieSecure,
        'use_only_cookies' => $useOnlyCookies,
        'use_strict_mode' => $strictMode,
        'expected_name' => defined('ADMIN_SESSION_NAME') ? ADMIN_SESSION_NAME : 'NOT_DEFINED'
    ];
    
    // Critical checks
    if ($sessionName !== '3DPRINT_ADMIN_SESSION') {
        $errors[] = "❌ Session name incorrect: '$sessionName' (expected '3DPRINT_ADMIN_SESSION')";
        $diagnostics['config_load']['status'] = 'failed';
    } elseif ($cookiePath !== '/') {
        $errors[] = "❌ CRITICAL: Cookie path is '$cookiePath' (MUST be '/')";
        $recommendations[] = "Edit includes/admin-session.php and ensure ini_set('session.cookie_path', '/') is set BEFORE session_start()";
        $diagnostics['config_load']['status'] = 'failed';
    } elseif (!$useOnlyCookies) {
        $warnings[] = "⚠️ use_only_cookies is disabled (security risk)";
        $diagnostics['config_load']['status'] = 'warning';
    } else {
        $diagnostics['config_load']['status'] = 'passed';
    }
    
} catch (Exception $e) {
    $errors[] = "❌ Failed to load session config: " . $e->getMessage();
    $diagnostics['config_load']['status'] = 'failed';
    $diagnostics['config_load']['error'] = $e->getMessage();
}

// ==================================================
// Test 2: Start session and verify cookie params
// ==================================================
$diagnostics['session_start'] = [
    'name' => 'Session Start & Cookie Parameters',
    'status' => 'pending',
    'details' => []
];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionId = session_id();
$cookieParams = session_get_cookie_params();

$diagnostics['session_start']['details'] = [
    'session_id' => $sessionId,
    'session_id_length' => strlen($sessionId),
    'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'active' : 'not_active',
    'cookie_params' => $cookieParams
];

if (empty($sessionId)) {
    $errors[] = "❌ Session ID is empty (session not started)";
    $diagnostics['session_start']['status'] = 'failed';
} elseif ($cookieParams['path'] !== '/') {
    $errors[] = "❌ CRITICAL: Runtime cookie path is '{$cookieParams['path']}' (MUST be '/')";
    $diagnostics['session_start']['status'] = 'failed';
} else {
    $diagnostics['session_start']['status'] = 'passed';
}

// ==================================================
// Test 3: Set test data and verify persistence
// ==================================================
$diagnostics['session_data'] = [
    'name' => 'Session Data Write/Read',
    'status' => 'pending',
    'details' => []
];

$testValue = 'diagnostic_' . time();
$_SESSION['DIAGNOSTIC_TEST'] = $testValue;

$readValue = $_SESSION['DIAGNOSTIC_TEST'] ?? null;

$diagnostics['session_data']['details'] = [
    'write_value' => $testValue,
    'read_value' => $readValue,
    'match' => $readValue === $testValue
];

if ($readValue !== $testValue) {
    $errors[] = "❌ Session write/read failed";
    $diagnostics['session_data']['status'] = 'failed';
} else {
    $diagnostics['session_data']['status'] = 'passed';
}

// ==================================================
// Test 4: Check browser cookie behavior
// ==================================================
$diagnostics['browser_cookie'] = [
    'name' => 'Browser Cookie Information',
    'status' => 'pending',
    'details' => []
];

$receivedCookies = $_COOKIE;
$sessionCookieName = ini_get('session.name');
$sessionCookieExists = isset($_COOKIE[$sessionCookieName]);

$diagnostics['browser_cookie']['details'] = [
    'expected_cookie_name' => $sessionCookieName,
    'cookie_received' => $sessionCookieExists,
    'cookie_value' => $sessionCookieExists ? substr($_COOKIE[$sessionCookieName], 0, 10) . '...' : 'NOT_SET',
    'all_cookies' => array_keys($receivedCookies),
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'request_scheme' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http'
];

if (!$sessionCookieExists) {
    $warnings[] = "⚠️ Session cookie not found in request (first visit is normal)";
    $diagnostics['browser_cookie']['status'] = 'warning';
} else {
    $diagnostics['browser_cookie']['status'] = 'passed';
}

// ==================================================
// Test 5: Check API bootstrap consistency
// ==================================================
$diagnostics['api_bootstrap'] = [
    'name' => 'API Bootstrap Configuration',
    'status' => 'pending',
    'details' => []
];

$bootstrapFile = __DIR__ . '/api/bootstrap.php';
$bootstrapExists = file_exists($bootstrapFile);

$diagnostics['api_bootstrap']['details'] = [
    'file_exists' => $bootstrapExists,
    'file_path' => $bootstrapFile
];

if (!$bootstrapExists) {
    $errors[] = "❌ api/bootstrap.php not found";
    $diagnostics['api_bootstrap']['status'] = 'failed';
} else {
    $bootstrapContent = file_get_contents($bootstrapFile);
    $loadsAdminSession = strpos($bootstrapContent, "require_once __DIR__ . '/../includes/admin-session.php'") !== false;
    
    $diagnostics['api_bootstrap']['details']['loads_admin_session'] = $loadsAdminSession;
    
    if (!$loadsAdminSession) {
        $errors[] = "❌ api/bootstrap.php does not load includes/admin-session.php";
        $diagnostics['api_bootstrap']['status'] = 'failed';
    } else {
        $diagnostics['api_bootstrap']['status'] = 'passed';
    }
}

// ==================================================
// Test 6: Frontend credentials check
// ==================================================
$diagnostics['frontend_config'] = [
    'name' => 'Frontend API Client Configuration',
    'status' => 'pending',
    'details' => []
];

$apiClientFile = __DIR__ . '/js/api-client.js';
$apiClientExists = file_exists($apiClientFile);

$diagnostics['frontend_config']['details'] = [
    'file_exists' => $apiClientExists,
    'file_path' => $apiClientFile
];

if (!$apiClientExists) {
    $errors[] = "❌ js/api-client.js not found";
    $diagnostics['frontend_config']['status'] = 'failed';
} else {
    $apiClientContent = file_get_contents($apiClientFile);
    $hasCredentialsInclude = strpos($apiClientContent, "credentials: 'include'") !== false;
    
    $diagnostics['frontend_config']['details']['credentials_include'] = $hasCredentialsInclude;
    
    if (!$hasCredentialsInclude) {
        $errors[] = "❌ js/api-client.js missing credentials: 'include'";
        $recommendations[] = "Add 'credentials: \"include\"' to all fetch() calls in js/api-client.js";
        $diagnostics['frontend_config']['status'] = 'failed';
    } else {
        $diagnostics['frontend_config']['status'] = 'passed';
    }
}

// ==================================================
// Generate Summary
// ==================================================
$passedCount = 0;
$failedCount = 0;
$warningCount = 0;

foreach ($diagnostics as $test) {
    if ($test['status'] === 'passed') $passedCount++;
    elseif ($test['status'] === 'failed') $failedCount++;
    elseif ($test['status'] === 'warning') $warningCount++;
}

$overallStatus = $failedCount > 0 ? 'FAILED' : ($warningCount > 0 ? 'WARNING' : 'PASSED');
$statusColor = $failedCount > 0 ? '#dc3545' : ($warningCount > 0 ? '#ffc107' : '#28a745');

// Add default recommendations
if ($failedCount === 0 && $warningCount === 0) {
    $recommendations[] = "✅ All tests passed! Session configuration is correct.";
    $recommendations[] = "🔍 If you still experience issues:";
    $recommendations[] = "  1. Clear your browser cookies for this domain";
    $recommendations[] = "  2. Open DevTools → Network tab";
    $recommendations[] = "  3. Log in to admin panel";
    $recommendations[] = "  4. Check if Cookie header is sent with /api/* requests";
    $recommendations[] = "  5. Verify the cookie name is '3DPRINT_ADMIN_SESSION'";
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Cookie Diagnostics - 3D Print Pro</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin-top: 10px;
            color: white;
            background: <?php echo $statusColor; ?>;
        }
        
        .summary {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .summary h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 22px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat {
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat.passed {
            background: #d4edda;
            color: #155724;
        }
        
        .stat.failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .stat.warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .test-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .test-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .test-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 20px;
        }
        
        .test-icon.passed {
            background: #28a745;
            color: white;
        }
        
        .test-icon.failed {
            background: #dc3545;
            color: white;
        }
        
        .test-icon.warning {
            background: #ffc107;
            color: #333;
        }
        
        .test-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .test-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .test-details pre {
            margin: 0;
            font-size: 13px;
            line-height: 1.6;
            overflow-x: auto;
        }
        
        .messages {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .messages h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .message {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .message.warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .message.recommendation {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        
        .actions {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 5px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Session Cookie Diagnostics</h1>
            <p>Проверка конфигурации сессий для админ-панели и API</p>
            <div class="status-badge"><?php echo $overallStatus; ?></div>
        </div>
        
        <div class="summary">
            <h2>📊 Результаты тестирования</h2>
            <div class="stats">
                <div class="stat passed">
                    <div class="stat-number"><?php echo $passedCount; ?></div>
                    <div class="stat-label">Passed</div>
                </div>
                <div class="stat failed">
                    <div class="stat-number"><?php echo $failedCount; ?></div>
                    <div class="stat-label">Failed</div>
                </div>
                <div class="stat warning">
                    <div class="stat-number"><?php echo $warningCount; ?></div>
                    <div class="stat-label">Warnings</div>
                </div>
            </div>
        </div>
        
        <?php foreach ($diagnostics as $key => $test): ?>
        <div class="test-card">
            <div class="test-header">
                <div class="test-icon <?php echo $test['status']; ?>">
                    <?php 
                    if ($test['status'] === 'passed') echo '✓';
                    elseif ($test['status'] === 'failed') echo '✗';
                    else echo '!';
                    ?>
                </div>
                <div class="test-title"><?php echo htmlspecialchars($test['name']); ?></div>
            </div>
            
            <?php if (!empty($test['details'])): ?>
            <div class="test-details">
                <pre><?php echo htmlspecialchars(json_encode($test['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></pre>
            </div>
            <?php endif; ?>
            
            <?php if (isset($test['error'])): ?>
            <div class="message error">
                <?php echo htmlspecialchars($test['error']); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
        <?php if (!empty($errors)): ?>
        <div class="messages">
            <h3>❌ Ошибки</h3>
            <?php foreach ($errors as $error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($warnings)): ?>
        <div class="messages">
            <h3>⚠️ Предупреждения</h3>
            <?php foreach ($warnings as $warning): ?>
            <div class="message warning"><?php echo htmlspecialchars($warning); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($recommendations)): ?>
        <div class="messages">
            <h3>💡 Рекомендации</h3>
            <?php foreach ($recommendations as $recommendation): ?>
            <div class="message recommendation"><?php echo nl2br(htmlspecialchars($recommendation)); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="actions">
            <a href="/admin/login.php" class="btn btn-primary">Admin Login</a>
            <a href="/admin/index.php" class="btn btn-secondary">Admin Dashboard</a>
            <a href="?refresh=<?php echo time(); ?>" class="btn btn-secondary">Refresh Diagnostics</a>
        </div>
    </div>
</body>
</html>
