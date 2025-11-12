<?php
// ========================================
// Authentication Middleware
// Checks if user is authenticated, redirects to login if not
// Include this at the top of every admin page
// ========================================

// Prevent direct access
if (!defined('ADMIN_INIT')) {
    http_response_code(403);
    die('Direct access not allowed');
}

// Require session configuration
require_once __DIR__ . '/session-config.php';

class Auth {
    /**
     * Check if user is authenticated
     * 
     * @return bool True if authenticated, false otherwise
     */
    public static function check() {
        return isset($_SESSION['ADMIN_AUTHENTICATED']) 
            && $_SESSION['ADMIN_AUTHENTICATED'] === true
            && isset($_SESSION['ADMIN_LOGIN']);
    }
    
    /**
     * Require authentication - redirect to login if not authenticated
     * Call this at the top of protected pages
     * 
     * @param string $redirectUrl Where to redirect if not authenticated
     */
    public static function require($redirectUrl = '/admin/login.php') {
        if (!self::check()) {
            // Store the intended destination for redirect after login
            if (!isset($_SESSION['INTENDED_URL'])) {
                $_SESSION['INTENDED_URL'] = $_SERVER['REQUEST_URI'];
            }
            
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    /**
     * Require authentication for API endpoints
     * Returns JSON error if not authenticated (no redirect)
     */
    public static function requireApi() {
        if (!self::check()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Authentication required. Please log in to access this resource.'
            ]);
            exit;
        }
    }
    
    /**
     * Log in a user
     * 
     * @param string $login The admin login username
     */
    public static function login($login) {
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);
        
        // Set authentication flags
        $_SESSION['ADMIN_AUTHENTICATED'] = true;
        $_SESSION['ADMIN_LOGIN'] = $login;
        $_SESSION['LOGIN_TIME'] = time();
        $_SESSION['LOGIN_IP'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Update session timestamps
        $_SESSION['CREATED'] = time();
        $_SESSION['LAST_ACTIVITY'] = time();
    }
    
    /**
     * Log out a user
     */
    public static function logout() {
        // Unset all session variables
        $_SESSION = [];
        
        // Delete the session cookie
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destroy the session
        session_destroy();
    }
    
    /**
     * Get the currently authenticated admin login
     * 
     * @return string|null The admin login or null if not authenticated
     */
    public static function user() {
        return $_SESSION['ADMIN_LOGIN'] ?? null;
    }
    
    /**
     * Get login information
     * 
     * @return array Login details or empty array
     */
    public static function getInfo() {
        if (!self::check()) {
            return [];
        }
        
        return [
            'login' => $_SESSION['ADMIN_LOGIN'] ?? null,
            'login_time' => $_SESSION['LOGIN_TIME'] ?? null,
            'login_ip' => $_SESSION['LOGIN_IP'] ?? null,
            'last_activity' => $_SESSION['LAST_ACTIVITY'] ?? null
        ];
    }
}
