<?php
// ========================================
// Admin Login Handler
// Processes login form submission
// ========================================

// Start output buffering to prevent any accidental output before headers
ob_start();

define('ADMIN_INIT', true);

// Debug logging
$debugLog = '/tmp/login-debug.log';
$logMsg = date('[Y-m-d H:i:s] ') . "Login handler started\n";
file_put_contents($debugLog, $logMsg, FILE_APPEND);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';
require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/../api/helpers/rate_limiter.php';

use App\Services\AdminAuthService;
use App\Models\AdminUser;

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Dependencies loaded, session ID: " . session_id() . "\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Not POST request, redirecting\n", FILE_APPEND);
    ob_end_clean();
    header('Location: /admin/login.php');
    exit;
}

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "POST request confirmed\n", FILE_APPEND);

// Apply strict rate limiting for login attempts
$rateLimiter = new RateLimiter(RateLimiter::PROFILE_AUTH);
$rateCheck = $rateLimiter->check('admin_login');
if (!$rateCheck['allowed']) {
    file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Rate limit exceeded\n", FILE_APPEND);
    $_SESSION['LOGIN_ERROR'] = 'Слишком много попыток входа. Попробуйте через ' . $rateCheck['retry_after'] . ' секунд.';
    session_write_close();
    ob_end_clean();
    header('Location: /admin/login.php');
    exit;
}

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Rate limit check passed\n", FILE_APPEND);
file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Session CSRF token: " . ($_SESSION['CSRF_TOKEN'] ?? 'NOT SET') . "\n", FILE_APPEND);
file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "POST CSRF token: " . ($_POST['csrf_token'] ?? 'NOT SET') . "\n", FILE_APPEND);

CSRF::verifyPostToken();

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "CSRF verification passed\n", FILE_APPEND);

$email = trim($_POST['email'] ?? $_POST['login'] ?? '');
$password = $_POST['password'] ?? '';
$rememberMe = isset($_POST['remember_me']);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Email: $email, Password length: " . strlen($password) . "\n", FILE_APPEND);

if (empty($email) || empty($password)) {
    file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Empty credentials\n", FILE_APPEND);
    $_SESSION['LOGIN_ERROR'] = 'Пожалуйста, заполните все поля.';
    session_write_close();
    ob_end_clean();
    header('Location: /admin/login.php');
    exit;
}

try {
    file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Starting authentication\n", FILE_APPEND);
    $authService = new AdminAuthService();
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $result = $authService->authenticate($email, $password, $ipAddress, $userAgent, $rememberMe);
    
    file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Auth result: " . json_encode(['success' => $result['success']]) . "\n", FILE_APPEND);
    
    if (!$result['success']) {
        file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Auth failed: " . ($result['error'] ?? 'unknown') . "\n", FILE_APPEND);
        $_SESSION['LOGIN_ERROR'] = $result['error'];
        
        if (isset($result['locked_until'])) {
            $_SESSION['LOCKOUT_UNTIL'] = $result['locked_until']->timestamp;
        }
        
        session_write_close();
        ob_end_clean();
        header('Location: /admin/login.php');
        exit;
    }
    
    $user = $result['user'];
    $session = $result['session'];
    
    file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Auth success, user ID: " . $user->id . ", email: " . $user->email . "\n", FILE_APPEND);
    
    unset($_SESSION['LOGIN_ERROR']);
    unset($_SESSION['LOCKOUT_UNTIL']);
    unset($_SESSION['LOGIN_ATTEMPTS']);
    unset($_SESSION['LAST_ATTEMPT_TIME']);
    
    $_SESSION['ADMIN_AUTHENTICATED'] = true;
    $_SESSION['ADMIN_LOGIN'] = $user->email;
    $_SESSION['ADMIN_USER_ID'] = $user->id;
    $_SESSION['ADMIN_USER_NAME'] = $user->name;
    $_SESSION['ADMIN_USER_ROLE'] = $user->role;
    $_SESSION['LOGIN_TIME'] = time();
    $_SESSION['LOGIN_IP'] = $ipAddress;
    $_SESSION['CREATED'] = time();
    $_SESSION['LAST_ACTIVITY'] = time();
    $_SESSION['CSRF_TOKEN'] = $result['csrf_token'];
    
    file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Session vars set. Session ID: " . session_id() . "\n", FILE_APPEND);
    
    $redirectUrl = $_SESSION['INTENDED_URL'] ?? '/admin/index.php';
    unset($_SESSION['INTENDED_URL']);
    
    file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Redirecting to: $redirectUrl\n", FILE_APPEND);
    
    // CRITICAL: Write session data before redirect
    session_write_close();
    
    file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Session closed, now redirecting\n", FILE_APPEND);
    
    // Clean output buffer and send redirect
    ob_end_clean();
    header('Location: ' . $redirectUrl);
    exit;
    
} catch (Exception $e) {
    file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Exception caught: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
    error_log('Login error: ' . $e->getMessage());
    
    $_SESSION['LOGIN_ERROR'] = 'Ошибка при входе. Пожалуйста, попробуйте позже.';
    session_write_close();
    ob_end_clean();
    header('Location: /admin/login.php');
    exit;
}
