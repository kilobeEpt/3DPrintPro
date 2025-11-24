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

// Redirect to login page with logout message
header('Location: /admin/login.php?logged_out=1');
exit;
