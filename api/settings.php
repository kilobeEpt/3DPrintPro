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

$db = new Database();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all settings or specific setting
            if (isset($_GET['key'])) {
                $key = $_GET['key'];
                $value = $db->getSetting($key);
                
                if ($value !== null) {
                    echo json_encode([
                        'success' => true,
                        'key' => $key,
                        'value' => $value
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Setting not found'
                    ]);
                }
            } else {
                // Get all settings
                $settings = $db->getAllSettings();
                echo json_encode([
                    'success' => true,
                    'settings' => $settings
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'POST':
        case 'PUT':
            // Save settings (single or multiple)
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                throw new Exception('Invalid JSON data');
            }
            
            if (isset($data['key']) && isset($data['value'])) {
                // Save single setting
                $db->saveSetting($data['key'], $data['value']);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Setting saved successfully'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                // Save multiple settings
                foreach ($data as $key => $value) {
                    $db->saveSetting($key, $value);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Settings saved successfully'
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'DELETE':
            // Delete setting
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!isset($data['key'])) {
                throw new Exception('Setting key required');
            }
            
            $db->deleteSetting($data['key']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Setting deleted successfully'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$db->close();
