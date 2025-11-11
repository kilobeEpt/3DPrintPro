<?php
// ========================================
// Settings API Endpoint
// ========================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/logger.php';

$db = new Database();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all settings or specific setting
            if (isset($_GET['key'])) {
                $key = $_GET['key'];
                
                if (empty($key) || !is_string($key)) {
                    ApiResponse::validationError('Setting key must be a non-empty string');
                }
                
                try {
                    $value = $db->getSetting($key);
                    
                    if ($value !== null) {
                        ApiResponse::success([
                            'key' => $key,
                            'value' => $value
                        ]);
                    } else {
                        ApiLogger::warning("Setting not found", ['key' => $key]);
                        ApiResponse::notFound('Setting not found');
                    }
                } catch (PDOException $e) {
                    ApiLogger::dbError('SELECT', 'settings', $e, ['key' => $key]);
                    ApiResponse::serverError('Failed to retrieve setting. Please try again.');
                }
            } else {
                // Get all settings
                try {
                    $settings = $db->getAllSettings();
                    ApiResponse::success(['settings' => $settings]);
                } catch (PDOException $e) {
                    ApiLogger::dbError('SELECT', 'settings', $e);
                    ApiResponse::serverError('Failed to retrieve settings. Please try again.');
                }
            }
            break;
            
        case 'POST':
        case 'PUT':
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
                    $db->saveSetting($data['key'], $data['value']);
                    ApiLogger::info("Setting saved successfully", ['key' => $data['key']]);
                    
                    ApiResponse::success([
                        'message' => 'Setting saved successfully',
                        'key' => $data['key']
                    ]);
                } catch (PDOException $e) {
                    ApiLogger::dbError('UPSERT', 'settings', $e, ['key' => $data['key']]);
                    ApiResponse::serverError('Failed to save setting. Please try again.');
                }
            } else {
                // Save multiple settings
                $savedCount = 0;
                $errors = [];
                
                foreach ($data as $key => $value) {
                    if (empty($key) || !is_string($key)) {
                        $errors[] = "Invalid key: $key";
                        continue;
                    }
                    
                    try {
                        $db->saveSetting($key, $value);
                        $savedCount++;
                    } catch (PDOException $e) {
                        ApiLogger::dbError('UPSERT', 'settings', $e, ['key' => $key]);
                        $errors[] = "Failed to save: $key";
                    }
                }
                
                if ($savedCount > 0) {
                    ApiLogger::info("Multiple settings saved", [
                        'count' => $savedCount,
                        'errors_count' => count($errors)
                    ]);
                    
                    $response = [
                        'message' => 'Settings saved successfully',
                        'saved_count' => $savedCount
                    ];
                    
                    if (!empty($errors)) {
                        $response['errors'] = $errors;
                    }
                    
                    ApiResponse::success($response);
                } else {
                    ApiLogger::error("Failed to save any settings", ['errors' => $errors]);
                    ApiResponse::serverError('Failed to save settings. Please try again.');
                }
            }
            break;
            
        case 'DELETE':
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
                $db->deleteSetting($data['key']);
                ApiLogger::info("Setting deleted successfully", ['key' => $data['key']]);
                
                ApiResponse::success([
                    'message' => 'Setting deleted successfully',
                    'key' => $data['key']
                ]);
            } catch (PDOException $e) {
                ApiLogger::dbError('DELETE', 'settings', $e, ['key' => $data['key']]);
                ApiResponse::serverError('Failed to delete setting. Please try again.');
            }
            break;
            
        default:
            ApiLogger::warning("Method not allowed", ['method' => $method]);
            ApiResponse::methodNotAllowed();
            break;
    }
    
} catch (PDOException $e) {
    ApiLogger::dbError('QUERY', 'settings', $e);
    ApiResponse::serverError('Database error occurred. Please try again later.');
    
} catch (Exception $e) {
    ApiLogger::error("Unexpected error in settings endpoint", ['exception' => $e]);
    ApiResponse::serverError('An unexpected error occurred. Please try again later.');
}

$db->close();
