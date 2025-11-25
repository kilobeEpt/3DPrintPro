<?php
// ========================================
// Admin Logout Handler
// Destroys session and redirects to login page
// ========================================

define('ADMIN_INIT', true);

require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';

// Log out the user
Auth::logout();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Выход...</title>
    <script>
        // Clear auth tokens from localStorage
        localStorage.removeItem('admin_auth_token');
        localStorage.removeItem('admin_csrf_token');
        
        // Redirect to login page
        window.location.href = '/admin/login.php?logged_out=1';
    </script>
</head>
<body>
    <p>Выход из системы...</p>
</body>
</html>
