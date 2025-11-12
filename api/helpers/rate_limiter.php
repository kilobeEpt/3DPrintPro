<?php
// ========================================
// Rate Limiter Helper
// Simple IP-based rate limiting for API endpoints
// ========================================

class RateLimiter {
    private $storageDir;
    private $maxRequests;
    private $timeWindow;
    
    /**
     * Initialize rate limiter
     * 
     * @param int $maxRequests Maximum requests per time window
     * @param int $timeWindow Time window in seconds
     */
    public function __construct($maxRequests = null, $timeWindow = null) {
        $this->maxRequests = $maxRequests ?? (defined('RATE_LIMIT_MAX_REQUESTS') ? RATE_LIMIT_MAX_REQUESTS : 60);
        $this->timeWindow = $timeWindow ?? (defined('RATE_LIMIT_TIME_WINDOW') ? RATE_LIMIT_TIME_WINDOW : 60);
        $this->storageDir = __DIR__ . '/../../logs/rate_limits';
        
        // Create storage directory if it doesn't exist
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0755, true);
        }
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                return $ip;
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Get storage file path for IP
     */
    private function getStorageFile($ip) {
        return $this->storageDir . '/' . md5($ip) . '.json';
    }
    
    /**
     * Check if request is allowed
     * 
     * @param string $endpoint Optional endpoint identifier for separate limits
     * @return array ['allowed' => bool, 'remaining' => int, 'reset' => int, 'retry_after' => int]
     */
    public function check($endpoint = null) {
        $ip = $this->getClientIp();
        $file = $this->getStorageFile($ip);
        $now = time();
        
        // Load existing rate limit data
        $data = [];
        if (file_exists($file)) {
            $json = @file_get_contents($file);
            if ($json) {
                $data = json_decode($json, true) ?: [];
            }
        }
        
        // Get endpoint-specific data or global data
        $key = $endpoint ?: 'global';
        if (!isset($data[$key])) {
            $data[$key] = [
                'requests' => [],
                'reset' => $now + $this->timeWindow
            ];
        }
        
        $limitData = &$data[$key];
        
        // Remove expired requests
        $limitData['requests'] = array_filter($limitData['requests'], function($timestamp) use ($now) {
            return $timestamp > ($now - $this->timeWindow);
        });
        
        // Reset window if expired
        if ($now > $limitData['reset']) {
            $limitData['requests'] = [];
            $limitData['reset'] = $now + $this->timeWindow;
        }
        
        // Check if limit exceeded
        $requestCount = count($limitData['requests']);
        $allowed = $requestCount < $this->maxRequests;
        
        if ($allowed) {
            // Add current request
            $limitData['requests'][] = $now;
            $requestCount++;
            
            // Save updated data
            @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
        }
        
        $remaining = max(0, $this->maxRequests - $requestCount);
        $retryAfter = $allowed ? 0 : ($limitData['reset'] - $now);
        
        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'reset' => $limitData['reset'],
            'retry_after' => $retryAfter
        ];
    }
    
    /**
     * Apply rate limiting to current request
     * Sends 429 response if limit exceeded
     * 
     * @param string $endpoint Optional endpoint identifier
     */
    public function apply($endpoint = null) {
        $result = $this->check($endpoint);
        
        // Always add rate limit headers
        header('X-RateLimit-Limit: ' . $this->maxRequests);
        header('X-RateLimit-Remaining: ' . $result['remaining']);
        header('X-RateLimit-Reset: ' . $result['reset']);
        
        if (!$result['allowed']) {
            header('Retry-After: ' . $result['retry_after']);
            http_response_code(429);
            
            echo json_encode([
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
                'meta' => [
                    'retry_after' => $result['retry_after'],
                    'reset' => $result['reset'],
                    'limit' => $this->maxRequests,
                    'window' => $this->timeWindow
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
            exit(0);
        }
    }
    
    /**
     * Clean up old rate limit files (maintenance)
     */
    public function cleanup() {
        if (!is_dir($this->storageDir)) {
            return;
        }
        
        $files = glob($this->storageDir . '/*.json');
        $now = time();
        $cleaned = 0;
        
        foreach ($files as $file) {
            // Remove files older than 2x time window
            if (filemtime($file) < ($now - ($this->timeWindow * 2))) {
                @unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
}
