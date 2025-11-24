<?php
// ========================================
// Security Headers Helper v2.0
// Enhanced with strict CSP, XSS protection, and context-aware policies
// ========================================

class SecurityHeaders {
    // Security policy presets
    const CONTEXT_PUBLIC = 'public';
    const CONTEXT_ADMIN = 'admin';
    const CONTEXT_API = 'api';
    
    /**
     * Apply comprehensive security headers to response
     * 
     * @param string $context One of: public, admin, api
     * @param array $options Additional options for fine-tuning
     */
    public static function apply($context = self::CONTEXT_API, array $options = []) {
        // CORS headers for API endpoints
        if ($context === self::CONTEXT_API) {
            $allowedOrigins = $options['cors_origins'] ?? '*';
            header('Access-Control-Allow-Origin: ' . $allowedOrigins);
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');
            header('Access-Control-Max-Age: 86400'); // Cache preflight for 24 hours
            
            if ($allowedOrigins !== '*') {
                header('Access-Control-Allow-Credentials: true');
            }
        }
        
        // Content-Type header
        $contentType = $options['content_type'] ?? 'application/json';
        header("Content-Type: {$contentType}; charset=utf-8");
        
        // Security headers (universal)
        header('X-Content-Type-Options: nosniff'); // Prevent MIME sniffing
        header('X-Frame-Options: DENY'); // Prevent clickjacking (stricter than SAMEORIGIN)
        header('X-XSS-Protection: 1; mode=block'); // Enable XSS protection (legacy browsers)
        
        // Referrer Policy - strict by default
        $referrerPolicy = $options['referrer_policy'] ?? 'strict-origin-when-cross-origin';
        header("Referrer-Policy: {$referrerPolicy}");
        
        // Permissions Policy (formerly Feature-Policy)
        $permissionsPolicy = $options['permissions_policy'] ?? self::getDefaultPermissionsPolicy();
        if ($permissionsPolicy) {
            header("Permissions-Policy: {$permissionsPolicy}");
        }
        
        // Content Security Policy - context-aware
        $csp = $options['csp'] ?? self::getDefaultCSP($context);
        if ($csp) {
            header("Content-Security-Policy: {$csp}");
        }
        
        // Strict-Transport-Security (HSTS) - only on HTTPS
        if (self::isHttps() && ($options['hsts'] ?? true)) {
            $hstsMaxAge = $options['hsts_max_age'] ?? 31536000; // 1 year
            header("Strict-Transport-Security: max-age={$hstsMaxAge}; includeSubDomains; preload");
        }
        
        // Additional cache control for sensitive endpoints
        if ($context === self::CONTEXT_ADMIN || ($options['no_cache'] ?? false)) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
    
    /**
     * Get default Content Security Policy by context
     * 
     * @param string $context
     * @return string
     */
    private static function getDefaultCSP($context) {
        switch ($context) {
            case self::CONTEXT_ADMIN:
                // Stricter CSP for admin panel
                return implode('; ', [
                    "default-src 'self'",
                    "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
                    "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com",
                    "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
                    "img-src 'self' data: https:",
                    "connect-src 'self'",
                    "frame-ancestors 'none'",
                    "base-uri 'self'",
                    "form-action 'self'",
                    "upgrade-insecure-requests"
                ]);
                
            case self::CONTEXT_PUBLIC:
                // Moderate CSP for public pages
                return implode('; ', [
                    "default-src 'self'",
                    "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://www.googletagmanager.com https://www.google-analytics.com https://mc.yandex.ru",
                    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                    "font-src 'self' https://fonts.gstatic.com",
                    "img-src 'self' data: https:",
                    "connect-src 'self' https://www.google-analytics.com https://mc.yandex.ru",
                    "frame-src https://www.youtube.com https://yandex.ru",
                    "base-uri 'self'",
                    "form-action 'self'",
                    "upgrade-insecure-requests"
                ]);
                
            case self::CONTEXT_API:
            default:
                // Minimal CSP for API endpoints (JSON only)
                return implode('; ', [
                    "default-src 'none'",
                    "frame-ancestors 'none'",
                    "base-uri 'none'"
                ]);
        }
    }
    
    /**
     * Get default Permissions Policy
     * 
     * @return string
     */
    private static function getDefaultPermissionsPolicy() {
        return implode(', ', [
            'camera=()',
            'microphone=()',
            'geolocation=()',
            'interest-cohort=()', // Disable FLoC
            'payment=()',
            'usb=()',
            'magnetometer=()',
            'gyroscope=()',
            'accelerometer=()'
        ]);
    }
    
    /**
     * Check if request is over HTTPS
     * 
     * @return bool
     */
    private static function isHttps() {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            $_SERVER['SERVER_PORT'] == 443 ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        );
    }
    
    /**
     * Handle OPTIONS preflight request
     */
    public static function handlePreflight() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            self::apply(self::CONTEXT_API);
            http_response_code(200);
            exit(0);
        }
    }
    
    /**
     * Apply security headers specifically for HTML responses
     * Use this in public and admin HTML pages
     * 
     * @param string $context
     */
    public static function applyForHtml($context = self::CONTEXT_PUBLIC) {
        self::apply($context, ['content_type' => 'text/html']);
    }
    
    /**
     * Get CSP meta tag for HTML templates
     * Use when headers cannot be set (static HTML)
     * 
     * @param string $context
     * @return string
     */
    public static function getCspMetaTag($context = self::CONTEXT_PUBLIC) {
        $csp = self::getDefaultCSP($context);
        return '<meta http-equiv="Content-Security-Policy" content="' . htmlspecialchars($csp, ENT_QUOTES) . '">';
    }
    
    /**
     * Get all security meta tags for HTML templates
     * 
     * @param string $context
     * @return string
     */
    public static function getSecurityMetaTags($context = self::CONTEXT_PUBLIC) {
        $tags = [];
        
        // CSP
        $tags[] = self::getCspMetaTag($context);
        
        // X-Content-Type-Options
        $tags[] = '<meta http-equiv="X-Content-Type-Options" content="nosniff">';
        
        // Referrer Policy
        $tags[] = '<meta name="referrer" content="strict-origin-when-cross-origin">';
        
        return implode("\n    ", $tags);
    }
}
