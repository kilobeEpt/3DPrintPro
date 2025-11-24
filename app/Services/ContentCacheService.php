<?php

namespace App\Services;

/**
 * Content Cache Service
 * 
 * Manages cache headers (ETag, Last-Modified) and cache invalidation
 * for content API responses to support near real-time frontend refreshes.
 */
class ContentCacheService
{
    const CACHE_DIR = __DIR__ . '/../../storage/cache';
    const CACHE_FILE = 'content_cache_timestamps.json';
    
    /**
     * Generate ETag from data
     * 
     * @param mixed $data Data to hash
     * @return string ETag value
     */
    public function generateETag($data)
    {
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data);
        }
        
        return md5($data);
    }
    
    /**
     * Generate ETag from timestamp
     * 
     * @param string|\DateTime $timestamp Timestamp
     * @return string ETag value
     */
    public function generateETagFromTimestamp($timestamp)
    {
        if ($timestamp instanceof \DateTime) {
            $timestamp = $timestamp->getTimestamp();
        } elseif (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        
        return md5((string)$timestamp);
    }
    
    /**
     * Set cache headers for response
     * 
     * @param string|\DateTime $lastModified Last modified timestamp
     * @param string|null $etag ETag value (auto-generated if not provided)
     * @param int $maxAge Cache max-age in seconds (default: 300 = 5 minutes)
     * @return void
     */
    public function setCacheHeaders($lastModified, $etag = null, $maxAge = 300)
    {
        // Convert timestamp to GMT string
        if ($lastModified instanceof \DateTime) {
            $lastModifiedStr = $lastModified->format('D, d M Y H:i:s') . ' GMT';
        } elseif (is_string($lastModified)) {
            $timestamp = strtotime($lastModified);
            $lastModifiedStr = gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
        } else {
            $lastModifiedStr = gmdate('D, d M Y H:i:s', $lastModified) . ' GMT';
        }
        
        // Generate ETag if not provided
        if ($etag === null) {
            $etag = $this->generateETagFromTimestamp($lastModified);
        }
        
        // Set headers
        header('Last-Modified: ' . $lastModifiedStr);
        header('ETag: "' . $etag . '"');
        header('Cache-Control: public, max-age=' . $maxAge . ', must-revalidate');
        
        // Check if client has valid cache
        $this->checkClientCache($lastModified, $etag);
    }
    
    /**
     * Check if client has valid cached version
     * 
     * @param string|\DateTime $lastModified Last modified timestamp
     * @param string $etag ETag value
     * @return void Exits with 304 if cache is valid
     */
    private function checkClientCache($lastModified, $etag)
    {
        // Check If-None-Match (ETag)
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            $clientEtag = trim($_SERVER['HTTP_IF_NONE_MATCH'], '"');
            if ($clientEtag === $etag) {
                $this->send304();
            }
        }
        
        // Check If-Modified-Since
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $clientTimestamp = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            
            if ($lastModified instanceof \DateTime) {
                $serverTimestamp = $lastModified->getTimestamp();
            } elseif (is_string($lastModified)) {
                $serverTimestamp = strtotime($lastModified);
            } else {
                $serverTimestamp = $lastModified;
            }
            
            if ($clientTimestamp >= $serverTimestamp) {
                $this->send304();
            }
        }
    }
    
    /**
     * Send 304 Not Modified response and exit
     * 
     * @return void
     */
    private function send304()
    {
        http_response_code(304);
        exit;
    }
    
    /**
     * Get the most recent updated_at timestamp for a collection
     * 
     * @param \Illuminate\Support\Collection $collection Collection of models
     * @return \DateTime|null Most recent timestamp
     */
    public function getLatestTimestamp($collection)
    {
        if ($collection->isEmpty()) {
            return null;
        }
        
        $latest = $collection->max('updated_at');
        
        if ($latest instanceof \Carbon\Carbon) {
            return $latest;
        }
        
        if (is_string($latest)) {
            return \Carbon\Carbon::parse($latest);
        }
        
        return null;
    }
    
    /**
     * Invalidate cache for a specific resource type
     * 
     * @param string $resourceType Resource type (e.g., 'services', 'portfolio')
     * @return void
     */
    public function invalidateCache($resourceType)
    {
        $cacheData = $this->loadCacheTimestamps();
        $cacheData[$resourceType] = time();
        $this->saveCacheTimestamps($cacheData);
    }
    
    /**
     * Get cache invalidation timestamp for a resource type
     * 
     * @param string $resourceType Resource type
     * @return int|null Timestamp or null if not set
     */
    public function getCacheTimestamp($resourceType)
    {
        $cacheData = $this->loadCacheTimestamps();
        return $cacheData[$resourceType] ?? null;
    }
    
    /**
     * Load cache timestamps from file
     * 
     * @return array Cache timestamps
     */
    private function loadCacheTimestamps()
    {
        $filePath = self::CACHE_DIR . '/' . self::CACHE_FILE;
        
        if (!file_exists($filePath)) {
            return [];
        }
        
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        
        return is_array($data) ? $data : [];
    }
    
    /**
     * Save cache timestamps to file
     * 
     * @param array $data Cache timestamps
     * @return void
     */
    private function saveCacheTimestamps($data)
    {
        $filePath = self::CACHE_DIR . '/' . self::CACHE_FILE;
        
        // Ensure cache directory exists
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }
        
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    /**
     * Clear all cache timestamps
     * 
     * @return void
     */
    public function clearAll()
    {
        $filePath = self::CACHE_DIR . '/' . self::CACHE_FILE;
        
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    /**
     * Store JSON snapshot for a resource
     * 
     * @param string $resourceType Resource type (e.g., 'services', 'portfolio')
     * @param array $data Data to cache
     * @param int $ttl Time-to-live in seconds (default: 300 = 5 minutes)
     * @return bool Success status
     */
    public function storeSnapshot($resourceType, $data, $ttl = 300)
    {
        $snapshotFile = self::CACHE_DIR . '/' . $resourceType . '_snapshot.json';
        
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }
        
        $snapshot = [
            'resource' => $resourceType,
            'data' => $data,
            'timestamp' => time(),
            'expiresAt' => time() + $ttl,
            'etag' => $this->generateETag($data)
        ];
        
        $result = file_put_contents($snapshotFile, json_encode($snapshot, JSON_PRETTY_PRINT));
        
        if ($result !== false) {
            $this->invalidateCache($resourceType);
        }
        
        return $result !== false;
    }
    
    /**
     * Load JSON snapshot for a resource
     * 
     * @param string $resourceType Resource type
     * @return array|null Snapshot data or null if not found/expired
     */
    public function loadSnapshot($resourceType)
    {
        $snapshotFile = self::CACHE_DIR . '/' . $resourceType . '_snapshot.json';
        
        if (!file_exists($snapshotFile)) {
            return null;
        }
        
        $content = file_get_contents($snapshotFile);
        $snapshot = json_decode($content, true);
        
        if (!is_array($snapshot)) {
            return null;
        }
        
        if (isset($snapshot['expiresAt']) && $snapshot['expiresAt'] < time()) {
            unlink($snapshotFile);
            return null;
        }
        
        return $snapshot;
    }
    
    /**
     * Delete snapshot for a resource
     * 
     * @param string $resourceType Resource type
     * @return bool Success status
     */
    public function deleteSnapshot($resourceType)
    {
        $snapshotFile = self::CACHE_DIR . '/' . $resourceType . '_snapshot.json';
        
        if (file_exists($snapshotFile)) {
            return unlink($snapshotFile);
        }
        
        return true;
    }
    
    /**
     * Get all active invalidation events
     * 
     * @param int $since Unix timestamp to filter events after
     * @return array Array of events with resource and timestamp
     */
    public function getInvalidationEvents($since = null)
    {
        $cacheData = $this->loadCacheTimestamps();
        $events = [];
        
        foreach ($cacheData as $resource => $timestamp) {
            if ($since === null || $timestamp > $since) {
                $events[] = [
                    'resource' => $resource,
                    'timestamp' => $timestamp,
                    'event' => 'invalidate'
                ];
            }
        }
        
        usort($events, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        return $events;
    }
}
