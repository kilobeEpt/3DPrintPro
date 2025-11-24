<?php
// ========================================
// Rate Limiter Helper v2.0
// Enhanced with settings integration and admin logging
// ========================================

class RateLimiter {
    private $storageDir;
    private $maxRequests;
    private $timeWindow;
    private $endpoint;
    
    // Default rate limit profiles
    const PROFILE_AUTH = 'auth';           // 5 requests per 15 min
    const PROFILE_API_READ = 'api_read';   // 100 requests per minute
    const PROFILE_API_WRITE = 'api_write'; // 30 requests per minute
    const PROFILE_ADMIN = 'admin';         // 60 requests per minute
    const PROFILE_PUBLIC = 'public';       // 60 requests per minute
    
    private static $profiles = [
        self::PROFILE_AUTH => ['max' => 5, 'window' => 900],      // 5 per 15 min
        self::PROFILE_API_READ => ['max' => 100, 'window' => 60], // 100 per min
        self::PROFILE_API_WRITE => ['max' => 30, 'window' => 60], // 30 per min
        self::PROFILE_ADMIN => ['max' => 60, 'window' => 60],     // 60 per min
        self::PROFILE_PUBLIC => ['max' => 60, 'window' => 60],    // 60 per min
    ];
    
    /**
     * Initialize rate limiter
     * 
     * @param string|null $profile Rate limit profile or null for custom
     * @param int|null $maxRequests Maximum requests per time window (if custom)
     * @param int|null $timeWindow Time window in seconds (if custom)
     */
    public function __construct($profile = null, $maxRequests = null, $timeWindow = null) {
        // Load from profile or use custom values
        if ($profile && isset(self::$profiles[$profile])) {
            $this->maxRequests = self::$profiles[$profile]['max'];
            $this->timeWindow = self::$profiles[$profile]['window'];
            $this->endpoint = $profile;
        } else {
            $this->maxRequests = $maxRequests ?? $this->loadSettingOrDefault('rate_limit_default_max', 60);
            $this->timeWindow = $timeWindow ?? $this->loadSettingOrDefault('rate_limit_default_window', 60);
            $this->endpoint = $profile ?? 'default';
        }
        
        $this->storageDir = __DIR__ . '/../../storage/cache/rate_limits';
        
        // Create storage directory if it doesn't exist
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0755, true);
        }
    }
    
    /**
     * Load setting from database or return default
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function loadSettingOrDefault($key, $default) {
        try {
            // Try to load from settings service if available
            if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                require_once __DIR__ . '/../../vendor/autoload.php';
                require_once __DIR__ . '/../../bootstrap/eloquent.php';
                
                if (class_exists('\App\Services\SettingsService')) {
                    $service = new \App\Services\SettingsService();
                    $value = $service->get($key);
                    if ($value !== null) {
                        return $value;
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore errors, use default
        }
        
        return $default;
    }
    
    /**
     * Get client IP address with proxy support
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
        $key = $endpoint ?: $this->endpoint;
        if (!isset($data[$key])) {
            $data[$key] = [
                'requests' => [],
                'reset' => $now + $this->timeWindow,
                'violations' => 0
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
        } else {
            // Track violation
            $limitData['violations'] = ($limitData['violations'] ?? 0) + 1;
            @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
            
            // Log violation to admin logs
            $this->logViolation($ip, $key, $limitData['violations']);
        }
        
        $remaining = max(0, $this->maxRequests - $requestCount);
        $retryAfter = $allowed ? 0 : ($limitData['reset'] - $now);
        
        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'reset' => $limitData['reset'],
            'retry_after' => $retryAfter,
            'ip' => $ip,
            'endpoint' => $key
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
     * Log rate limit violation to admin action logs
     * 
     * @param string $ip
     * @param string $endpoint
     * @param int $violationCount
     */
    private function logViolation($ip, $endpoint, $violationCount) {
        try {
            if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                require_once __DIR__ . '/../../vendor/autoload.php';
                require_once __DIR__ . '/../../bootstrap/eloquent.php';
                
                if (class_exists('\App\Models\AdminActionLog')) {
                    \App\Models\AdminActionLog::create([
                        'user_id' => null, // System action
                        'action' => 'rate_limit_violation',
                        'entity_type' => 'rate_limiter',
                        'entity_id' => null,
                        'ip_address' => $ip,
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                        'payload' => json_encode([
                            'endpoint' => $endpoint,
                            'violation_count' => $violationCount,
                            'limit' => $this->maxRequests,
                            'window' => $this->timeWindow,
                            'url' => $_SERVER['REQUEST_URI'] ?? null,
                            'method' => $_SERVER['REQUEST_METHOD'] ?? null
                        ])
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Silently fail - rate limiting should not break the app
            error_log("Rate limit violation logging failed: " . $e->getMessage());
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
    
    /**
     * Get rate limit profile configuration
     * 
     * @param string $profile
     * @return array|null
     */
    public static function getProfile($profile) {
        return self::$profiles[$profile] ?? null;
    }
    
    /**
     * Get all available profiles
     * 
     * @return array
     */
    public static function getProfiles() {
        return self::$profiles;
    }
}

/**
 * Helper function to quickly apply rate limiting
 * 
 * @param string $profile Rate limit profile
 * @param string|null $endpoint Optional endpoint identifier
 */
function applyRateLimit($profile = RateLimiter::PROFILE_PUBLIC, $endpoint = null) {
    $limiter = new RateLimiter($profile);
    $limiter->apply($endpoint);
}
