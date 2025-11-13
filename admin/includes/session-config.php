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
// Shared Session Bootstrap
// Use shared configuration for session name and security settings
// This ensures admin pages and API endpoints use the same session
// ========================================

require_once __DIR__ . '/../../includes/admin-session.php';

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
