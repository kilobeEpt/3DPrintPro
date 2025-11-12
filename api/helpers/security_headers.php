<?php
// ========================================
// Security Headers Helper
// ========================================

class SecurityHeaders {
    /**
     * Apply security headers to response
     * Includes CORS, content security, and anti-clickjacking headers
     */
    public static function apply() {
        // CORS headers (already set in individual files, but ensuring consistency)
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Security headers
        header('X-Content-Type-Options: nosniff'); // Prevent MIME sniffing
        header('X-Frame-Options: DENY'); // Prevent clickjacking
        header('Referrer-Policy: strict-origin-when-cross-origin'); // Control referrer information
        header('X-XSS-Protection: 1; mode=block'); // Enable XSS protection (legacy browsers)
        
        // Content type
        header('Content-Type: application/json; charset=utf-8');
    }
    
    /**
     * Handle OPTIONS preflight request
     */
    public static function handlePreflight() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit(0);
        }
    }
}
