<?php

namespace App\Services;

use App\Models\SettingsAudit;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Settings Service
 * 
 * Centralized service for managing application settings with:
 * - JSON file-based caching for performance
 * - Typed value casting (string, int, bool, float, array, json)
 * - Grouped reads for optimized queries
 * - Single key lookups with cache
 * - Bulk updates with validation
 * - Automatic cache invalidation
 * - Full audit logging of changes
 * 
 * Cache location: storage/cache/settings.json
 * Audit logs: settings_audit table
 */
class SettingsService
{
    private $cacheFile;
    private $cache = null;
    private $cacheLoaded = false;
    
    /**
     * Setting type constants for typed casting
     */
    const TYPE_STRING = 'string';
    const TYPE_INT = 'int';
    const TYPE_BOOL = 'bool';
    const TYPE_FLOAT = 'float';
    const TYPE_ARRAY = 'array';
    const TYPE_JSON = 'json';
    
    /**
     * Default type definitions for known settings
     * Can be extended by the application
     */
    private $typeMap = [
        'telegram_chat_id' => self::TYPE_STRING,
        'telegram_bot_token' => self::TYPE_STRING,
        'notifications_enabled' => self::TYPE_BOOL,
        'max_file_size' => self::TYPE_INT,
        'price_per_gram' => self::TYPE_FLOAT,
        'allowed_extensions' => self::TYPE_ARRAY,
        'calculator_config' => self::TYPE_JSON,
    ];
    
    /**
     * Validation rules for settings
     * Format: 'key' => ['rule' => 'value', ...]
     */
    private $validationRules = [
        'telegram_chat_id' => [
            'type' => 'string',
            'maxLength' => 255,
        ],
        'telegram_bot_token' => [
            'type' => 'string',
            'maxLength' => 255,
        ],
        'max_file_size' => [
            'type' => 'int',
            'min' => 0,
            'max' => 104857600, // 100MB
        ],
        'price_per_gram' => [
            'type' => 'float',
            'min' => 0,
        ],
    ];
    
    public function __construct()
    {
        $this->cacheFile = __DIR__ . '/../../storage/cache/settings.json';
        $this->ensureCacheDirectoryExists();
    }
    
    /**
     * Get all settings as associative array
     * 
     * @param bool $useCache Whether to use cache (default: true)
     * @return array Associative array of setting_key => value
     */
    public function getAll($useCache = true)
    {
        if ($useCache && $this->isCacheValid()) {
            return $this->loadCache();
        }
        
        $settings = Capsule::table('settings')
            ->select('setting_key', 'setting_value')
            ->get();
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->setting_key] = $this->castValue(
                $setting->setting_key,
                $setting->setting_value
            );
        }
        
        if ($useCache) {
            $this->saveCache($result);
        }
        
        return $result;
    }
    
    /**
     * Get settings grouped by prefix
     * Example: getGrouped('telegram') returns all telegram_* settings
     * 
     * @param string $prefix Setting key prefix
     * @param bool $useCache Whether to use cache (default: true)
     * @return array Associative array of settings matching prefix
     */
    public function getGrouped($prefix, $useCache = true)
    {
        $allSettings = $this->getAll($useCache);
        $grouped = [];
        
        foreach ($allSettings as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $grouped[$key] = $value;
            }
        }
        
        return $grouped;
    }
    
    /**
     * Get a single setting value
     * 
     * @param string $key Setting key
     * @param mixed $default Default value if setting not found
     * @param bool $useCache Whether to use cache (default: true)
     * @return mixed Setting value or default
     */
    public function get($key, $default = null, $useCache = true)
    {
        if ($useCache && $this->isCacheValid()) {
            $cache = $this->loadCache();
            return $cache[$key] ?? $default;
        }
        
        $setting = Capsule::table('settings')
            ->where('setting_key', $key)
            ->first();
        
        if (!$setting) {
            return $default;
        }
        
        return $this->castValue($key, $setting->setting_value);
    }
    
    /**
     * Set a single setting value with audit logging
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @param string $changedBy Username or identifier (default: 'system')
     * @return bool Success status
     * @throws \InvalidArgumentException if validation fails
     */
    public function set($key, $value, $changedBy = 'system')
    {
        // Validate the value
        $this->validate($key, $value);
        
        // Get old value for audit logging
        $oldValue = $this->get($key, null, false);
        
        // Convert value to storage format (JSON if array/object)
        $storageValue = $this->toStorageFormat($value);
        
        // Upsert the setting
        $exists = Capsule::table('settings')
            ->where('setting_key', $key)
            ->exists();
        
        if ($exists) {
            Capsule::table('settings')
                ->where('setting_key', $key)
                ->update([
                    'setting_value' => $storageValue,
                    'updated_at' => now(),
                ]);
        } else {
            Capsule::table('settings')->insert([
                'setting_key' => $key,
                'setting_value' => $storageValue,
            ]);
        }
        
        // Log the change
        $this->logChange($key, $oldValue, $value, $changedBy);
        
        // Invalidate cache
        $this->invalidateCache();
        
        return true;
    }
    
    /**
     * Set multiple settings with validation and audit logging
     * 
     * @param array $settings Associative array of key => value
     * @param string $changedBy Username or identifier (default: 'system')
     * @return array Array with 'success' => count, 'errors' => [key => error]
     */
    public function setMultiple(array $settings, $changedBy = 'system')
    {
        $successCount = 0;
        $errors = [];
        
        Capsule::beginTransaction();
        
        try {
            foreach ($settings as $key => $value) {
                try {
                    $this->set($key, $value, $changedBy);
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[$key] = $e->getMessage();
                }
            }
            
            Capsule::commit();
        } catch (\Exception $e) {
            Capsule::rollBack();
            throw $e;
        }
        
        return [
            'success' => $successCount,
            'errors' => $errors,
        ];
    }
    
    /**
     * Delete a setting
     * 
     * @param string $key Setting key
     * @param string $changedBy Username or identifier (default: 'system')
     * @return bool Success status
     */
    public function delete($key, $changedBy = 'system')
    {
        // Get old value for audit logging
        $oldValue = $this->get($key, null, false);
        
        if ($oldValue === null) {
            return false; // Setting doesn't exist
        }
        
        // Delete the setting
        $deleted = Capsule::table('settings')
            ->where('setting_key', $key)
            ->delete();
        
        if ($deleted) {
            // Log the deletion
            $this->logChange($key, $oldValue, null, $changedBy);
            
            // Invalidate cache
            $this->invalidateCache();
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Invalidate the settings cache
     * 
     * @return bool Success status
     */
    public function invalidateCache()
    {
        $this->cache = null;
        $this->cacheLoaded = false;
        
        if (file_exists($this->cacheFile)) {
            return @unlink($this->cacheFile);
        }
        
        return true;
    }
    
    /**
     * Warm up the cache by loading all settings
     * 
     * @return bool Success status
     */
    public function warmCache()
    {
        $settings = $this->getAll(false);
        return $this->saveCache($settings);
    }
    
    /**
     * Get audit history for a setting
     * 
     * @param string|null $key Setting key (null for all)
     * @param int $limit Maximum number of records
     * @return array Audit records
     */
    public function getAuditHistory($key = null, $limit = 50)
    {
        $query = SettingsAudit::query()
            ->orderBy('created_at', 'desc')
            ->limit($limit);
        
        if ($key !== null) {
            $query->where('setting_key', $key);
        }
        
        return $query->get()->toArray();
    }
    
    /**
     * Cast a setting value to its proper type
     * 
     * @param string $key Setting key
     * @param mixed $value Raw value from database
     * @return mixed Typed value
     */
    private function castValue($key, $value)
    {
        // Try to decode JSON first
        if (is_string($value) && !empty($value) && ($value[0] === '{' || $value[0] === '[')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // If we have a type map, apply it
                $type = $this->typeMap[$key] ?? null;
                if ($type === self::TYPE_JSON || $type === self::TYPE_ARRAY) {
                    return $decoded;
                }
            }
        }
        
        // Apply type casting based on type map
        $type = $this->typeMap[$key] ?? self::TYPE_STRING;
        
        switch ($type) {
            case self::TYPE_INT:
                return (int) $value;
            case self::TYPE_BOOL:
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case self::TYPE_FLOAT:
                return (float) $value;
            case self::TYPE_ARRAY:
            case self::TYPE_JSON:
                return is_array($value) ? $value : json_decode($value, true);
            case self::TYPE_STRING:
            default:
                return (string) $value;
        }
    }
    
    /**
     * Convert a value to storage format (JSON for arrays/objects)
     * 
     * @param mixed $value Value to convert
     * @return string Storage-ready value
     */
    private function toStorageFormat($value)
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        
        return (string) $value;
    }
    
    /**
     * Validate a setting value
     * 
     * @param string $key Setting key
     * @param mixed $value Value to validate
     * @throws \InvalidArgumentException if validation fails
     */
    private function validate($key, $value)
    {
        // Check if we have validation rules for this key
        if (!isset($this->validationRules[$key])) {
            return; // No validation rules, allow any value
        }
        
        $rules = $this->validationRules[$key];
        
        // Type validation
        if (isset($rules['type'])) {
            switch ($rules['type']) {
                case 'int':
                    if (!is_numeric($value)) {
                        throw new \InvalidArgumentException("Setting '{$key}' must be a number");
                    }
                    break;
                case 'float':
                    if (!is_numeric($value)) {
                        throw new \InvalidArgumentException("Setting '{$key}' must be a number");
                    }
                    break;
                case 'bool':
                    if (!is_bool($value) && !in_array($value, ['0', '1', 0, 1, true, false], true)) {
                        throw new \InvalidArgumentException("Setting '{$key}' must be a boolean");
                    }
                    break;
            }
        }
        
        // String length validation
        if (isset($rules['maxLength']) && is_string($value)) {
            if (strlen($value) > $rules['maxLength']) {
                throw new \InvalidArgumentException("Setting '{$key}' exceeds maximum length of {$rules['maxLength']}");
            }
        }
        
        // Numeric range validation
        if (isset($rules['min']) && is_numeric($value)) {
            if ($value < $rules['min']) {
                throw new \InvalidArgumentException("Setting '{$key}' must be at least {$rules['min']}");
            }
        }
        
        if (isset($rules['max']) && is_numeric($value)) {
            if ($value > $rules['max']) {
                throw new \InvalidArgumentException("Setting '{$key}' must not exceed {$rules['max']}");
            }
        }
    }
    
    /**
     * Log a setting change to the audit table
     * 
     * @param string $key Setting key
     * @param mixed $oldValue Old value
     * @param mixed $newValue New value
     * @param string $changedBy Username or identifier
     */
    private function logChange($key, $oldValue, $newValue, $changedBy)
    {
        SettingsAudit::logChange(
            $key,
            $this->toStorageFormat($oldValue),
            $this->toStorageFormat($newValue),
            $changedBy
        );
    }
    
    /**
     * Check if cache is valid (exists and not stale)
     * 
     * @return bool True if cache is valid
     */
    private function isCacheValid()
    {
        if (!file_exists($this->cacheFile)) {
            return false;
        }
        
        // Cache is valid for 5 minutes
        $maxAge = 300; // seconds
        $fileTime = filemtime($this->cacheFile);
        
        return (time() - $fileTime) < $maxAge;
    }
    
    /**
     * Load settings from cache file
     * 
     * @return array Cached settings
     */
    private function loadCache()
    {
        if ($this->cacheLoaded && $this->cache !== null) {
            return $this->cache;
        }
        
        if (!file_exists($this->cacheFile)) {
            return [];
        }
        
        $json = file_get_contents($this->cacheFile);
        $this->cache = json_decode($json, true) ?? [];
        $this->cacheLoaded = true;
        
        return $this->cache;
    }
    
    /**
     * Save settings to cache file
     * 
     * @param array $settings Settings to cache
     * @return bool Success status
     */
    private function saveCache(array $settings)
    {
        $json = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return file_put_contents($this->cacheFile, $json, LOCK_EX) !== false;
    }
    
    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDirectoryExists()
    {
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
}
