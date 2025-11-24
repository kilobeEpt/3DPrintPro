<?php
// ========================================
// Shared Admin Session Bootstrap
// Defines session name and secure cookie settings
// Use this in both admin pages and API endpoints
// ========================================

// Define admin session name as constant
if (!defined('ADMIN_SESSION_NAME')) {
    define('ADMIN_SESSION_NAME', '3DPRINT_ADMIN_SESSION');
}

// ========================================
// Session Security Settings (ini_set)
// Must be called BEFORE session_start()
// ========================================

/**
 * Bootstrap admin session with secure settings
 * Call this before session_start() in both admin pages and API endpoints
 * 
 * @return void
 */
function bootstrapAdminSession() {
    // Only apply settings if session not already started
    if (session_status() === PHP_SESSION_NONE) {
        // Session name (custom to avoid default PHPSESSID)
        ini_set('session.name', ADMIN_SESSION_NAME);
        
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
    }
}

// Auto-bootstrap when this file is included
bootstrapAdminSession();
