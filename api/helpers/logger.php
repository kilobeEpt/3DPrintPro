<?php
// ========================================
// API Logger Helper
// Centralized error logging with proper formatting
// ========================================

class ApiLogger {
    private static $logFile = null;
    
    /**
     * Initialize logger with log file path
     */
    private static function init() {
        if (self::$logFile === null) {
            self::$logFile = __DIR__ . '/../../logs/api.log';
            
            // Ensure logs directory exists and is writable
            $logDir = dirname(self::$logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            // Set file permissions if file doesn't exist
            if (!file_exists(self::$logFile)) {
                touch(self::$logFile);
                chmod(self::$logFile, 0644);
            }
        }
    }
    
    /**
     * Log an error message
     * 
     * @param string $message Error message
     * @param array $context Additional context (exception, request data, etc.)
     */
    public static function error($message, $context = []) {
        self::log('ERROR', $message, $context);
    }
    
    /**
     * Log a warning message
     * 
     * @param string $message Warning message
     * @param array $context Additional context
     */
    public static function warning($message, $context = []) {
        self::log('WARNING', $message, $context);
    }
    
    /**
     * Log an info message
     * 
     * @param string $message Info message
     * @param array $context Additional context
     */
    public static function info($message, $context = []) {
        self::log('INFO', $message, $context);
    }
    
    /**
     * Log a debug message (only in debug mode)
     * 
     * @param string $message Debug message
     * @param array $context Additional context
     */
    public static function debug($message, $context = []) {
        if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
            self::log('DEBUG', $message, $context);
        }
    }
    
    /**
     * Core logging function
     */
    private static function log($level, $message, $context = []) {
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'N/A';
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
        
        // Build log entry
        $logEntry = sprintf(
            "[%s] [%s] %s %s | %s\n",
            $timestamp,
            $level,
            $requestMethod,
            $requestUri,
            $message
        );
        
        // Add context if provided
        if (!empty($context)) {
            $contextStr = self::formatContext($context);
            $logEntry .= "Context: " . $contextStr . "\n";
        }
        
        // Add request info
        $logEntry .= sprintf("IP: %s | User-Agent: %s\n", 
            $remoteAddr, 
            $_SERVER['HTTP_USER_AGENT'] ?? 'N/A'
        );
        
        // Add separator for readability
        $logEntry .= str_repeat('-', 80) . "\n";
        
        // Write to log file
        error_log($logEntry, 3, self::$logFile);
        
        // Also write to PHP error log in production for critical errors
        if ($level === 'ERROR' && (!defined('DEBUG_MODE') || DEBUG_MODE === false)) {
            error_log("API Error: $message");
        }
    }
    
    /**
     * Format context array for logging
     */
    private static function formatContext($context) {
        if (empty($context)) {
            return '{}';
        }
        
        // Handle exception objects
        if (isset($context['exception']) && $context['exception'] instanceof Exception) {
            $exception = $context['exception'];
            $context['exception'] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => self::sanitizeTrace($exception->getTraceAsString())
            ];
        }
        
        // Sanitize sensitive data
        $context = self::sanitizeSensitiveData($context);
        
        return json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
    /**
     * Sanitize sensitive data from logs (passwords, tokens, etc.)
     */
    private static function sanitizeSensitiveData($data) {
        $sensitiveKeys = ['password', 'token', 'secret', 'api_key', 'apikey', 'authorization'];
        
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $lowerKey = strtolower($key);
                
                // Check if key contains sensitive information
                foreach ($sensitiveKeys as $sensitiveKey) {
                    if (strpos($lowerKey, $sensitiveKey) !== false) {
                        $data[$key] = '***REDACTED***';
                        continue 2;
                    }
                }
                
                // Recursively sanitize nested arrays
                if (is_array($value)) {
                    $data[$key] = self::sanitizeSensitiveData($value);
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Sanitize stack trace (limit length for readability)
     */
    private static function sanitizeTrace($trace) {
        $lines = explode("\n", $trace);
        // Keep only first 10 lines of stack trace
        $lines = array_slice($lines, 0, 10);
        return implode("\n", $lines);
    }
    
    /**
     * Log database error with proper context
     */
    public static function dbError($operation, $table, Exception $e, $additionalContext = []) {
        self::error(
            "Database error during $operation on table '$table'",
            array_merge([
                'operation' => $operation,
                'table' => $table,
                'exception' => $e
            ], $additionalContext)
        );
    }
    
    /**
     * Log validation error
     */
    public static function validationError($endpoint, $errors) {
        self::warning(
            "Validation failed for $endpoint",
            [
                'endpoint' => $endpoint,
                'validation_errors' => $errors
            ]
        );
    }
    
    /**
     * Get log file path
     */
    public static function getLogFile() {
        self::init();
        return self::$logFile;
    }
}
