<?php
// ========================================
// Admin Login Handler
// Processes login form submission
// ========================================

define('ADMIN_INIT', true);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';
require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

use App\Services\AdminAuthService;
use App\Models\AdminUser;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/login.php');
    exit;
}

CSRF::verifyPostToken();

$email = trim($_POST['email'] ?? $_POST['login'] ?? '');
$password = $_POST['password'] ?? '';
$rememberMe = isset($_POST['remember_me']);

if (empty($email) || empty($password)) {
    $_SESSION['LOGIN_ERROR'] = 'Пожалуйста, заполните все поля.';
    header('Location: /admin/login.php');
    exit;
}

try {
    $authService = new AdminAuthService();
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $result = $authService->authenticate($email, $password, $ipAddress, $userAgent, $rememberMe);
    
    if (!$result['success']) {
        $_SESSION['LOGIN_ERROR'] = $result['error'];
        
        if (isset($result['locked_until'])) {
            $_SESSION['LOCKOUT_UNTIL'] = $result['locked_until']->timestamp;
        }
        
        header('Location: /admin/login.php');
        exit;
    }
    
    $user = $result['user'];
    $session = $result['session'];
    
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
    
    $redirectUrl = $_SESSION['INTENDED_URL'] ?? '/admin/index.php';
    unset($_SESSION['INTENDED_URL']);
    
    header('Location: ' . $redirectUrl);
    exit;
    
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    
    $_SESSION['LOGIN_ERROR'] = 'Ошибка при входе. Пожалуйста, попробуйте позже.';
    header('Location: /admin/login.php');
    exit;
}
