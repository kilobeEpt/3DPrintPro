<?php
// ========================================
// Admin Login Handler
// Processes login form submission
// ========================================

define('ADMIN_INIT', true);

require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/login.php');
    exit;
}

// Verify CSRF token
CSRF::verifyPostToken();

// Get form data
$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

// Validate input
if (empty($login) || empty($password)) {
    $_SESSION['LOGIN_ERROR'] = 'Пожалуйста, заполните все поля.';
    header('Location: /admin/login.php');
    exit;
}

// Rate limiting - prevent brute force attacks
$maxAttempts = 5;
$lockoutTime = 900; // 15 minutes

if (!isset($_SESSION['LOGIN_ATTEMPTS'])) {
    $_SESSION['LOGIN_ATTEMPTS'] = 0;
    $_SESSION['LAST_ATTEMPT_TIME'] = time();
}

// Reset attempts if lockout time has passed
if (isset($_SESSION['LOCKOUT_UNTIL']) && time() > $_SESSION['LOCKOUT_UNTIL']) {
    $_SESSION['LOGIN_ATTEMPTS'] = 0;
    unset($_SESSION['LOCKOUT_UNTIL']);
}

// Check if account is locked
if (isset($_SESSION['LOCKOUT_UNTIL']) && time() < $_SESSION['LOCKOUT_UNTIL']) {
    $remainingTime = ceil(($_SESSION['LOCKOUT_UNTIL'] - time()) / 60);
    $_SESSION['LOGIN_ERROR'] = "Слишком много попыток входа. Попробуйте снова через {$remainingTime} минут.";
    header('Location: /admin/login.php');
    exit;
}

try {
    // Connect to database
    $db = new Database();
    
    // Get admin credentials from settings
    $adminLogin = $db->getSetting('admin_login');
    $adminPasswordHash = $db->getSetting('admin_password_hash');
    
    // Check if admin credentials are set
    if (empty($adminLogin) || empty($adminPasswordHash)) {
        $_SESSION['LOGIN_ERROR'] = 'Учетные данные администратора не настроены. Обратитесь к системному администратору.';
        header('Location: /admin/login.php');
        exit;
    }
    
    // Verify login
    if ($login !== $adminLogin) {
        // Increment failed attempts
        $_SESSION['LOGIN_ATTEMPTS']++;
        $_SESSION['LAST_ATTEMPT_TIME'] = time();
        
        // Lock account if too many attempts
        if ($_SESSION['LOGIN_ATTEMPTS'] >= $maxAttempts) {
            $_SESSION['LOCKOUT_UNTIL'] = time() + $lockoutTime;
            $remainingTime = ceil($lockoutTime / 60);
            $_SESSION['LOGIN_ERROR'] = "Слишком много неудачных попыток входа. Аккаунт заблокирован на {$remainingTime} минут.";
        } else {
            $_SESSION['LOGIN_ERROR'] = 'Неверный логин или пароль.';
        }
        
        header('Location: /admin/login.php');
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $adminPasswordHash)) {
        // Increment failed attempts
        $_SESSION['LOGIN_ATTEMPTS']++;
        $_SESSION['LAST_ATTEMPT_TIME'] = time();
        
        // Lock account if too many attempts
        if ($_SESSION['LOGIN_ATTEMPTS'] >= $maxAttempts) {
            $_SESSION['LOCKOUT_UNTIL'] = time() + $lockoutTime;
            $remainingTime = ceil($lockoutTime / 60);
            $_SESSION['LOGIN_ERROR'] = "Слишком много неудачных попыток входа. Аккаунт заблокирован на {$remainingTime} минут.";
        } else {
            $_SESSION['LOGIN_ERROR'] = 'Неверный логин или пароль.';
        }
        
        header('Location: /admin/login.php');
        exit;
    }
    
    // ========================================
    // Login successful
    // ========================================
    
    // Reset login attempts
    $_SESSION['LOGIN_ATTEMPTS'] = 0;
    unset($_SESSION['LOCKOUT_UNTIL']);
    unset($_SESSION['LAST_ATTEMPT_TIME']);
    
    // Log in the user (regenerates session ID)
    Auth::login($login);
    
    // Regenerate CSRF token after successful login
    CSRF::regenerateToken();
    
    // Redirect to intended URL or dashboard
    $redirectUrl = $_SESSION['INTENDED_URL'] ?? '/admin/index.php';
    unset($_SESSION['INTENDED_URL']);
    
    header('Location: ' . $redirectUrl);
    exit;
    
} catch (Exception $e) {
    // Log error (in production, this should be logged to a file)
    error_log('Login error: ' . $e->getMessage());
    
    $_SESSION['LOGIN_ERROR'] = 'Ошибка при входе. Пожалуйста, попробуйте позже.';
    header('Location: /admin/login.php');
    exit;
}
