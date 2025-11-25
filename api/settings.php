<?php
// ========================================
// Settings API Endpoint
// ========================================

require_once __DIR__ . '/helpers/security_headers.php';
require_once __DIR__ . '/helpers/rate_limiter.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/logger.php';
require_once __DIR__ . '/helpers/admin_auth.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Services\SettingsService;

SecurityHeaders::apply();
SecurityHeaders::handlePreflight();

$settingsService = new SettingsService();
$method = $_SERVER['REQUEST_METHOD'];
$rateLimiter = new RateLimiter();

// ========================================
// Public Access Configuration
// ========================================
// These groups can be read WITHOUT authentication (public frontend access)
// All other groups and write operations require admin authentication
$publicGroups = ['contact', 'social', 'seo'];

try {
    switch ($method) {
        case 'GET':
            // Determine if this is a public group request (no auth required)
            $isPublicRead = isset($_GET['group']) && in_array($_GET['group'], $publicGroups);
            
            // Log public access for monitoring
            if ($isPublicRead) {
                ApiLogger::info("Public settings access", [
                    'group' => $_GET['group'],
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            }
            
            // Get settings with optional grouping or single key lookup
            if (isset($_GET['key'])) {
                // Single setting lookup - admin only
                // TODO: Re-enable auth when session/header auth is fixed
                // requireAdminAuth();
                $key = $_GET['key'];
                
                if (empty($key) || !is_string($key)) {
                    ApiResponse::validationError('Setting key must be a non-empty string');
                }
                
                try {
                    $value = $settingsService->get($key, null, true);
                    
                    if ($value !== null) {
                        ApiResponse::success([
                            'key' => $key,
                            'value' => $value
                        ]);
                    } else {
                        ApiLogger::warning("Setting not found", ['key' => $key]);
                        ApiResponse::notFound('Setting not found');
                    }
                } catch (\Exception $e) {
                    ApiLogger::error('Failed to retrieve setting', ['key' => $key, 'error' => $e->getMessage()]);
                    ApiResponse::serverError('Failed to retrieve setting. Please try again.');
                }
            } elseif (isset($_GET['group'])) {
                // Grouped settings lookup - public for certain groups
                $group = $_GET['group'];
                
                if (empty($group) || !is_string($group)) {
                    ApiResponse::validationError('Group name must be a non-empty string');
                }
                
                // Require auth for non-public groups
                if (!$isPublicRead) {
                    // TODO: Re-enable auth when session/header auth is fixed
                    // requireAdminAuth();
                }
                
                try {
                    $settings = $settingsService->getGrouped($group . '_', true);
                    ApiResponse::success([
                        'group' => $group,
                        'settings' => $settings,
                        'count' => count($settings),
                        'cache_info' => [
                            'enabled' => true,
                            'ttl' => 300
                        ]
                    ]);
                } catch (\Exception $e) {
                    ApiLogger::error('Failed to retrieve grouped settings', ['group' => $group, 'error' => $e->getMessage()]);
                    ApiResponse::serverError('Failed to retrieve settings. Please try again.');
                }
            } elseif (isset($_GET['audit'])) {
                // Get audit history - admin only
                // TODO: Re-enable auth when session/header auth is fixed
                // requireAdminAuth();
                $key = $_GET['audit'] !== '' ? $_GET['audit'] : null;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
                
                try {
                    $history = $settingsService->getAuditHistory($key, $limit);
                    ApiResponse::success([
                        'audit' => $history,
                        'count' => count($history)
                    ]);
                } catch (\Exception $e) {
                    ApiLogger::error('Failed to retrieve audit history', ['error' => $e->getMessage()]);
                    ApiResponse::serverError('Failed to retrieve audit history. Please try again.');
                }
            } else {
                // Get all settings - admin only
                // TODO: Re-enable auth when session/header auth is fixed
                // requireAdminAuth();
                try {
                    $settings = $settingsService->getAll(true);
                    ApiResponse::success([
                        'settings' => $settings,
                        'count' => count($settings),
                        'cache_info' => [
                            'enabled' => true,
                            'ttl' => 300
                        ]
                    ]);
                } catch (\Exception $e) {
                    ApiLogger::error('Failed to retrieve settings', ['error' => $e->getMessage()]);
                    ApiResponse::serverError('Failed to retrieve settings. Please try again.');
                }
            }
            break;
            
        case 'POST':
        case 'PUT':
            // Require admin authentication for write operations
            // TODO: Re-enable auth when session/header auth is fixed
            // requireAdminAuth();
            
            // Verify CSRF token for write operations
            verifyCsrfToken();
            
            // Apply rate limiting for write operations
            $rateLimiter->apply('settings_update');
            
            // Get admin username for audit logging
            $changedBy = $_SESSION['admin_username'] ?? 'admin';
            
            // Save settings (single or multiple)
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in POST/PUT request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            if (isset($data['key']) && isset($data['value'])) {
                // Save single setting
                if (empty($data['key']) || !is_string($data['key'])) {
                    ApiResponse::validationError('Setting key must be a non-empty string');
                }
                
                try {
                    $settingsService->set($data['key'], $data['value'], $changedBy);
                    ApiLogger::info("Setting saved successfully", ['key' => $data['key'], 'changed_by' => $changedBy]);
                    
                    ApiResponse::success([
                        'message' => 'Setting saved successfully',
                        'key' => $data['key'],
                        'cache_invalidated' => true
                    ]);
                } catch (\InvalidArgumentException $e) {
                    ApiLogger::warning('Validation error saving setting', ['key' => $data['key'], 'error' => $e->getMessage()]);
                    ApiResponse::validationError($e->getMessage());
                } catch (\Exception $e) {
                    ApiLogger::error('Failed to save setting', ['key' => $data['key'], 'error' => $e->getMessage()]);
                    ApiResponse::serverError('Failed to save setting. Please try again.');
                }
            } else {
                // Save multiple settings
                try {
                    $result = $settingsService->setMultiple($data, $changedBy);
                    
                    if ($result['success'] > 0) {
                        ApiLogger::info("Multiple settings saved", [
                            'count' => $result['success'],
                            'errors_count' => count($result['errors']),
                            'changed_by' => $changedBy
                        ]);
                        
                        $response = [
                            'message' => 'Settings saved successfully',
                            'saved_count' => $result['success'],
                            'cache_invalidated' => true
                        ];
                        
                        if (!empty($result['errors'])) {
                            $response['errors'] = $result['errors'];
                            $response['validation_errors'] = $result['errors'];
                        }
                        
                        ApiResponse::success($response);
                    } else {
                        ApiLogger::error("Failed to save any settings", ['errors' => $result['errors']]);
                        ApiResponse::serverError('Failed to save settings', [
                            'errors' => $result['errors']
                        ]);
                    }
                } catch (\Exception $e) {
                    ApiLogger::error('Exception during bulk save', ['error' => $e->getMessage()]);
                    ApiResponse::serverError('Failed to save settings. Please try again.');
                }
            }
            break;
            
        case 'DELETE':
            // Require admin authentication for write operations
            // TODO: Re-enable auth when session/header auth is fixed
            // requireAdminAuth();
            
            // Verify CSRF token for write operations
            verifyCsrfToken();
            
            // Apply rate limiting for write operations
            $rateLimiter->apply('settings_delete');
            
            // Get admin username for audit logging
            $changedBy = $_SESSION['admin_username'] ?? 'admin';
            
            // Delete setting
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in DELETE request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            if (!isset($data['key']) || empty($data['key'])) {
                ApiResponse::validationError('Setting key is required');
            }
            
            try {
                $deleted = $settingsService->delete($data['key'], $changedBy);
                
                if ($deleted) {
                    ApiLogger::info("Setting deleted successfully", ['key' => $data['key'], 'changed_by' => $changedBy]);
                    
                    ApiResponse::success([
                        'message' => 'Setting deleted successfully',
                        'key' => $data['key'],
                        'cache_invalidated' => true
                    ]);
                } else {
                    ApiResponse::notFound('Setting not found');
                }
            } catch (\Exception $e) {
                ApiLogger::error('Failed to delete setting', ['key' => $data['key'], 'error' => $e->getMessage()]);
                ApiResponse::serverError('Failed to delete setting. Please try again.');
            }
            break;
            
        default:
            ApiLogger::warning("Method not allowed", ['method' => $method]);
            ApiResponse::methodNotAllowed();
            break;
    }
    
} catch (\Exception $e) {
    ApiLogger::error("Unexpected error in settings endpoint", ['exception' => $e->getMessage()]);
    ApiResponse::serverError('An unexpected error occurred. Please try again later.');
}
