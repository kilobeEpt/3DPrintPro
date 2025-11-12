<?php
// ========================================
// Session Configuration
// Secure PHP session settings for admin panel
// ========================================

// Prevent direct access
if (!defined('ADMIN_INIT')) {
    http_response_code(403);
    die('Direct access not allowed');
}

// ========================================
// Session Security Settings
// ========================================

// Session name (custom to avoid default PHPSESSID)
ini_set('session.name', '3DPRINT_ADMIN_SESSION');

// HttpOnly - JavaScript cannot access session cookie
ini_set('session.cookie_httponly', 1);

// SameSite - Prevent CSRF attacks (Lax allows top-level navigation)
ini_set('session.cookie_samesite', 'Lax');

// Secure - Only send cookie over HTTPS (auto-detect HTTPS)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

if ($isHttps) {
    ini_set('session.cookie_secure', 1);
}

// Session timeout (30 minutes of inactivity)
ini_set('session.gc_maxlifetime', 1800);

// Cookie lifetime (0 = until browser closes)
ini_set('session.cookie_lifetime', 0);

// Use only cookies for session ID (no URL parameters)
ini_set('session.use_only_cookies', 1);

// Strict session ID (stronger security)
ini_set('session.use_strict_mode', 1);

// ========================================
// Start Session
// ========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// Session Activity Timeout Check
// ========================================

$timeout = 1800; // 30 minutes in seconds

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    // Session expired due to inactivity
    session_unset();
    session_destroy();
    session_start(); // Start fresh session
    $_SESSION['SESSION_EXPIRED'] = true;
}

// Update last activity timestamp
$_SESSION['LAST_ACTIVITY'] = time();

// ========================================
// Session Fixation Protection
// Regenerate session ID periodically (every 15 minutes)
// ========================================

if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} elseif (time() - $_SESSION['CREATED'] > 900) {
    // Session started more than 15 minutes ago
    session_regenerate_id(true); // Regenerate ID and delete old session
    $_SESSION['CREATED'] = time();
}
