<?php
// ========================================
// Admin Login Page
// ========================================

define('ADMIN_INIT', true);

require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// If already logged in, redirect to dashboard
if (Auth::check()) {
    header('Location: /admin/index.php');
    exit;
}

// Check for session expiration message
$sessionExpired = isset($_SESSION['SESSION_EXPIRED']) && $_SESSION['SESSION_EXPIRED'] === true;
if ($sessionExpired) {
    unset($_SESSION['SESSION_EXPIRED']);
}

// Check for logout message
$loggedOut = isset($_GET['logged_out']) && $_GET['logged_out'] === '1';

// Check for login error
$loginError = isset($_SESSION['LOGIN_ERROR']) ? $_SESSION['LOGIN_ERROR'] : null;
if ($loginError) {
    unset($_SESSION['LOGIN_ERROR']);
}

// Generate CSRF token
$csrfToken = CSRF::getToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель - 3D Print Pro</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link rel="stylesheet" href="/css/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔐</text></svg>">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-screen {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header i {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .login-header h2 {
            margin: 0 0 5px 0;
            font-size: 24px;
            color: #333;
        }
        
        .login-header p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group label i {
            margin-right: 5px;
            color: #667eea;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-primary {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-primary i {
            margin-right: 8px;
        }
        
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }
        
        .alert-success {
            background: #efe;
            border: 1px solid #cfc;
            color: #3c3;
        }
        
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
        }
        
        .alert i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="login-screen">
        <div class="login-container">
            <div class="login-header">
                <i class="fas fa-cube"></i>
                <h2>3D Print Pro</h2>
                <p>Административная панель</p>
            </div>
            
            <?php if ($sessionExpired): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-clock"></i>
                    Сессия истекла. Пожалуйста, войдите снова.
                </div>
            <?php endif; ?>
            
            <?php if ($loggedOut): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Вы успешно вышли из системы.
                </div>
            <?php endif; ?>
            
            <?php if ($loginError): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <form action="/admin/login-handler.php" method="POST" class="login-form">
                <?php echo CSRF::getTokenField(); ?>
                
                <div class="form-group">
                    <label for="login">
                        <i class="fas fa-user"></i>
                        Логин
                    </label>
                    <input 
                        type="text" 
                        id="login" 
                        name="login" 
                        class="form-control" 
                        placeholder="admin" 
                        required 
                        autocomplete="username"
                        autofocus
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Пароль
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="••••••••" 
                        required 
                        autocomplete="current-password"
                    >
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Войти
                </button>
            </form>
        </div>
    </div>
</body>
</html>
