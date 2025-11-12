<?php
// ========================================
// CSRF Token Management
// Generate and validate CSRF tokens for form protection
// ========================================

// Prevent direct access
if (!defined('ADMIN_INIT')) {
    http_response_code(403);
    die('Direct access not allowed');
}

class CSRF {
    /**
     * Generate a new CSRF token and store in session
     * 
     * @return string The generated token
     */
    public static function generateToken() {
        if (!isset($_SESSION['CSRF_TOKEN']) || empty($_SESSION['CSRF_TOKEN'])) {
            $_SESSION['CSRF_TOKEN'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['CSRF_TOKEN'];
    }
    
    /**
     * Get the current CSRF token (generates if doesn't exist)
     * 
     * @return string The CSRF token
     */
    public static function getToken() {
        return self::generateToken();
    }
    
    /**
     * Validate a CSRF token from request
     * 
     * @param string $token The token to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateToken($token) {
        if (empty($token) || !isset($_SESSION['CSRF_TOKEN'])) {
            return false;
        }
        
        // Use hash_equals to prevent timing attacks
        return hash_equals($_SESSION['CSRF_TOKEN'], $token);
    }
    
    /**
     * Verify CSRF token from POST request
     * Dies with 403 if invalid
     * 
     * @param string $fieldName The form field name (default: csrf_token)
     */
    public static function verifyPostToken($fieldName = 'csrf_token') {
        $token = $_POST[$fieldName] ?? '';
        
        if (!self::validateToken($token)) {
            http_response_code(403);
            die(json_encode([
                'success' => false,
                'error' => 'Invalid CSRF token. Please refresh the page and try again.'
            ]));
        }
    }
    
    /**
     * Verify CSRF token from request headers (for AJAX)
     * Dies with 403 if invalid
     * 
     * @param string $headerName The header name (default: X-CSRF-Token)
     */
    public static function verifyHeaderToken($headerName = 'X-CSRF-Token') {
        $headers = getallheaders();
        $token = $headers[$headerName] ?? '';
        
        if (!self::validateToken($token)) {
            http_response_code(403);
            die(json_encode([
                'success' => false,
                'error' => 'Invalid CSRF token. Please refresh the page and try again.'
            ]));
        }
    }
    
    /**
     * Generate a hidden input field with CSRF token
     * 
     * @param string $fieldName The form field name (default: csrf_token)
     * @return string HTML input field
     */
    public static function getTokenField($fieldName = 'csrf_token') {
        $token = self::getToken();
        return '<input type="hidden" name="' . htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Get token as meta tag for AJAX requests
     * 
     * @return string HTML meta tag
     */
    public static function getTokenMeta() {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Regenerate CSRF token (call after successful form submission)
     */
    public static function regenerateToken() {
        $_SESSION['CSRF_TOKEN'] = bin2hex(random_bytes(32));
    }
}
