<?php
// ========================================
// API Response Helper
// Standardizes JSON responses with proper HTTP status codes
// ========================================

class ApiResponse {
    /**
     * Send a successful JSON response
     * 
     * @param mixed $data The data payload
     * @param array $meta Optional metadata (pagination, etc.)
     * @param int $statusCode HTTP status code (default: 200)
     */
    public static function success($data = null, $meta = [], $statusCode = 200) {
        http_response_code($statusCode);
        
        $response = [
            'success' => true
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit(0);
    }
    
    /**
     * Send an error JSON response
     * 
     * @param string $message User-friendly error message
     * @param int $statusCode HTTP status code
     * @param array $details Optional additional error details (for development)
     */
    public static function error($message, $statusCode = 400, $details = []) {
        http_response_code($statusCode);
        
        $response = [
            'success' => false,
            'error' => $message
        ];
        
        // Only include details in non-production environments
        if (!empty($details) && defined('DEBUG_MODE') && DEBUG_MODE === true) {
            $response['details'] = $details;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit(0);
    }
    
    /**
     * Send a validation error response (400)
     */
    public static function validationError($message, $errors = []) {
        http_response_code(400);
        
        $response = [
            'success' => false,
            'error' => $message
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit(0);
    }
    
    /**
     * Send an unprocessable entity error response (422)
     */
    public static function unprocessableEntity($message, $errors = []) {
        http_response_code(422);
        
        $response = [
            'success' => false,
            'error' => $message
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit(0);
    }
    
    /**
     * Send a rate limit exceeded error response (429)
     */
    public static function rateLimitExceeded($message = 'Rate limit exceeded', $meta = []) {
        http_response_code(429);
        
        $response = [
            'success' => false,
            'error' => $message
        ];
        
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit(0);
    }
    
    /**
     * Send a not found error response (404)
     */
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }
    
    /**
     * Send an unauthorized error response (401)
     */
    public static function unauthorized($message = 'Unauthorized access') {
        self::error($message, 401);
    }
    
    /**
     * Send a forbidden error response (403)
     */
    public static function forbidden($message = 'Access forbidden') {
        self::error($message, 403);
    }
    
    /**
     * Send a method not allowed error response (405)
     */
    public static function methodNotAllowed($message = 'Method not allowed') {
        self::error($message, 405);
    }
    
    /**
     * Send a server error response (500)
     */
    public static function serverError($message = 'Internal server error') {
        self::error($message, 500);
    }
    
    /**
     * Send a created response (201) for successful POST operations
     */
    public static function created($data = null, $meta = []) {
        self::success($data, $meta, 201);
    }
}
