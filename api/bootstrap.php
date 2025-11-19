<?php
/**
 * API Bootstrap
 * 
 * This file loads all necessary dependencies for API endpoints:
 * - Composer autoloader
 * - Eloquent ORM
 * - Common helpers
 * - Request/response wrappers
 * - Global exception handling
 * 
 * Usage:
 *   require_once __DIR__ . '/bootstrap.php';
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load Eloquent ORM
require_once __DIR__ . '/../bootstrap/eloquent.php';

// Load API helpers
require_once __DIR__ . '/helpers/security_headers.php';
require_once __DIR__ . '/helpers/rate_limiter.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/logger.php';
require_once __DIR__ . '/helpers/admin_auth.php';

// Apply security headers for all API requests
SecurityHeaders::apply();
SecurityHeaders::handlePreflight();

// Global exception handler for API requests
set_exception_handler(function ($exception) {
    ApiLogger::error('Uncaught exception', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
    
    $message = 'An unexpected error occurred';
    $details = [];
    
    // Include details in debug mode
    if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
        $details = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ];
    }
    
    ApiResponse::serverError($message);
});

// Set error reporting based on debug mode
if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}
