<?php
// ========================================
// Admin Authentication Helper for API Endpoints
// Include this in admin-only API endpoints
// ========================================

// This file should be included in API endpoints that require admin authentication
// It checks if the user is authenticated via PHP session

/**
 * Require admin authentication for API endpoint
 * Dies with 401 if not authenticated
 * 
 * @return void
 */
function requireAdminAuth() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is authenticated
    $isAuthenticated = isset($_SESSION['ADMIN_AUTHENTICATED']) 
        && $_SESSION['ADMIN_AUTHENTICATED'] === true
        && isset($_SESSION['ADMIN_LOGIN']);
    
    if (!$isAuthenticated) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Authentication required. Please log in to access this resource.'
        ]);
        exit;
    }
    
    // Update last activity timestamp
    $_SESSION['LAST_ACTIVITY'] = time();
}

/**
 * Verify CSRF token from request
 * Dies with 403 if invalid
 * 
 * @param string $headerName Optional custom header name
 * @return void
 */
function verifyCsrfToken($headerName = 'X-CSRF-Token') {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Get token from header
    $headers = getallheaders();
    $token = $headers[$headerName] ?? '';
    
    // Also check POST data as fallback
    if (empty($token) && isset($_POST['csrf_token'])) {
        $token = $_POST['csrf_token'];
    }
    
    // Verify token
    if (empty($token) || !isset($_SESSION['CSRF_TOKEN']) || !hash_equals($_SESSION['CSRF_TOKEN'], $token)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid CSRF token. Please refresh the page and try again.'
        ]);
        exit;
    }
}

/**
 * Require admin authentication AND CSRF token validation
 * Use this for all admin POST/PUT/DELETE operations
 * 
 * @return void
 */
function requireAdminAuthWithCsrf() {
    requireAdminAuth();
    verifyCsrfToken();
}
