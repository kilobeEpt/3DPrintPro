<?php
// ========================================
// Admin Authentication Helper for API Endpoints
// Include this in admin-only API endpoints
// ========================================

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../bootstrap/eloquent.php';

// Load admin session config if not already loaded (for backward compatibility)
if (!defined('ADMIN_SESSION_NAME')) {
    require_once __DIR__ . '/../../includes/admin-session.php';
}

use App\Services\AdminAuthService;

function requireAdminAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $sessionId = session_id();
    
    if (empty($sessionId)) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'No session found. Please log in.'
        ]);
        exit;
    }
    
    $authService = new AdminAuthService();
    $validation = $authService->validateSession($sessionId);
    
    if (!$validation['valid']) {
        session_unset();
        session_destroy();
        
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => $validation['error'] ?? 'Authentication required. Please log in.'
        ]);
        exit;
    }
    
    $user = $validation['user'];
    $session = $validation['session'];
    
    $_SESSION['ADMIN_AUTHENTICATED'] = true;
    $_SESSION['ADMIN_USER_ID'] = $user->id;
    $_SESSION['ADMIN_LOGIN'] = $user->email;
    $_SESSION['ADMIN_USER_NAME'] = $user->name;
    $_SESSION['ADMIN_USER_ROLE'] = $user->role;
    $_SESSION['LAST_ACTIVITY'] = time();
    
    if (!empty($session->csrf_token)) {
        $_SESSION['CSRF_TOKEN'] = $session->csrf_token;
    }
}

function verifyCsrfToken($headerName = 'X-CSRF-Token') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $headers = getallheaders();
    $token = $headers[$headerName] ?? '';
    
    if (empty($token) && isset($_POST['csrf_token'])) {
        $token = $_POST['csrf_token'];
    }
    
    $sessionId = session_id();
    $authService = new AdminAuthService();
    
    if (!$authService->validateCsrfToken($sessionId, $token)) {
        if (empty($token) || !isset($_SESSION['CSRF_TOKEN']) || !hash_equals($_SESSION['CSRF_TOKEN'], $token)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid CSRF token. Please refresh the page and try again.'
            ]);
            exit;
        }
    }
}

function requireAdminAuthWithCsrf() {
    requireAdminAuth();
    verifyCsrfToken();
}

function getAuthenticatedUser() {
    if (!isset($_SESSION['ADMIN_USER_ID'])) {
        return null;
    }
    
    return \App\Models\AdminUser::find($_SESSION['ADMIN_USER_ID']);
}

function logAdminAction($action, $entityType = null, $entityId = null, $payload = null) {
    if (!isset($_SESSION['ADMIN_USER_ID'])) {
        return null;
    }
    
    $authService = new AdminAuthService();
    return $authService->logAction(
        $_SESSION['ADMIN_USER_ID'],
        $action,
        $entityType,
        $entityId,
        $payload
    );
}
