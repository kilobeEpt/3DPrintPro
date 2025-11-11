<?php
// ========================================
// API Test & Diagnostics Endpoint
// ========================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

try {
    $db = new Database();
    
    $response = [
        'success' => true,
        'message' => 'API is working correctly',
        'timestamp' => date('Y-m-d H:i:s'),
        'database_status' => 'Connected',
        'database_info' => [
            'host' => DB_HOST,
            'name' => DB_NAME,
            'user' => DB_USER,
            'charset' => DB_CHARSET
        ],
        'tables_info' => []
    ];
    
    // Count records in each table
    $tables_with_active = ['services', 'portfolio', 'testimonials', 'faq', 'content_blocks'];
    $tables_without_active = ['settings', 'orders'];
    
    // Check tables WITH active column
    foreach ($tables_with_active as $table) {
        try {
            $total = $db->getCount($table);
            $active = $db->getCount($table, ['active' => 1]);
            
            $response['tables_info'][$table] = [
                'total' => $total,
                'active' => $active,
                'status' => $total > 0 ? '✅ OK' : '⚠️ Empty'
            ];
        } catch (Exception $e) {
            $response['tables_info'][$table] = [
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Check tables WITHOUT active column
    foreach ($tables_without_active as $table) {
        try {
            $total = $db->getCount($table);
            
            $response['tables_info'][$table] = [
                'total' => $total,
                'status' => 'N/A (no active column)'
            ];
        } catch (Exception $e) {
            $response['tables_info'][$table] = [
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Get sample data from each table
    $response['sample_data'] = [];
    
    // Settings
    try {
        $settings = $db->getAllSettings();
        $response['sample_data']['settings'] = $settings;
    } catch (Exception $e) {
        $response['sample_data']['settings'] = ['error' => $e->getMessage()];
    }
    
    // Services (first 3)
    try {
        $services = $db->getRecords('services', ['active' => 1], 'sort_order', 3);
        $response['sample_data']['services'] = array_map(function($s) {
            return [
                'id' => $s['id'] ?? null,
                'name' => $s['name'] ?? null,
                'slug' => $s['slug'] ?? null,
                'active' => $s['active'] ?? null
            ];
        }, $services);
    } catch (Exception $e) {
        $response['sample_data']['services'] = ['error' => $e->getMessage()];
    }
    
    // FAQ (first 3)
    try {
        $faq = $db->getRecords('faq', ['active' => 1], 'sort_order', 3);
        $response['sample_data']['faq'] = array_map(function($f) {
            return [
                'id' => $f['id'] ?? null,
                'question' => mb_substr($f['question'] ?? '', 0, 50) . '...',
                'active' => $f['active'] ?? null
            ];
        }, $faq);
    } catch (Exception $e) {
        $response['sample_data']['faq'] = ['error' => $e->getMessage()];
    }
    
    // Testimonials (first 3)
    try {
        $testimonials = $db->getRecords('testimonials', ['active' => 1, 'approved' => 1], 'sort_order', 3);
        $response['sample_data']['testimonials'] = array_map(function($t) {
            return [
                'id' => $t['id'] ?? null,
                'name' => $t['name'] ?? null,
                'active' => $t['active'] ?? null,
                'approved' => $t['approved'] ?? null
            ];
        }, $testimonials);
    } catch (Exception $e) {
        $response['sample_data']['testimonials'] = ['error' => $e->getMessage()];
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    $db->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
