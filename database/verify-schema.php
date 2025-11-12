<?php
// ========================================
// Database Schema Verification Script
// Version: 1.0
// ========================================
//
// This script verifies that all required tables and columns exist
// in the target database. Can be run from CLI or HTTP.
//
// CLI Usage:
//   php database/verify-schema.php
//
// HTTP Usage:
//   https://your-site.com/database/verify-schema.php
//
// Output: JSON with detailed verification results
//
// ========================================

// Determine if running from CLI or HTTP
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
}

// Load database config
$configPath = __DIR__ . '/../api/config.php';
if (!file_exists($configPath)) {
    $error = [
        'status' => 'ERROR',
        'message' => 'Config file not found',
        'help' => 'Copy api/config.example.php to api/config.php and configure credentials'
    ];
    echo json_encode($error, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit(1);
}

require_once $configPath;

// Expected schema definition
$expectedSchema = [
    'orders' => [
        'description' => 'Customer orders and inquiries',
        'has_active' => false,
        'columns' => [
            'id', 'order_number', 'type', 'name', 'email', 'phone', 'telegram',
            'service', 'subject', 'message', 'amount', 'calculator_data',
            'status', 'telegram_sent', 'telegram_error', 'created_at', 'updated_at'
        ],
        'indexes' => ['order_number', 'phone', 'email', 'status', 'created_at', 'type']
    ],
    'settings' => [
        'description' => 'Application configuration',
        'has_active' => false,
        'columns' => ['id', 'setting_key', 'setting_value', 'updated_at'],
        'indexes' => ['setting_key']
    ],
    'services' => [
        'description' => 'Service offerings and pricing',
        'has_active' => true,
        'columns' => [
            'id', 'name', 'slug', 'icon', 'description', 'features', 'price',
            'category', 'sort_order', 'active', 'featured', 'created_at', 'updated_at'
        ],
        'indexes' => ['active', 'featured', 'sort_order', 'slug', 'category']
    ],
    'portfolio' => [
        'description' => 'Project showcase and case studies',
        'has_active' => true,
        'columns' => [
            'id', 'title', 'description', 'image_url', 'category', 'tags',
            'sort_order', 'active', 'created_at', 'updated_at'
        ],
        'indexes' => ['active', 'category', 'sort_order']
    ],
    'testimonials' => [
        'description' => 'Customer reviews and ratings',
        'has_active' => true,
        'columns' => [
            'id', 'name', 'position', 'avatar', 'text', 'rating',
            'sort_order', 'approved', 'active', 'created_at', 'updated_at'
        ],
        'indexes' => ['active', 'approved', 'rating', 'sort_order']
    ],
    'faq' => [
        'description' => 'Frequently asked questions',
        'has_active' => true,
        'columns' => ['id', 'question', 'answer', 'sort_order', 'active', 'created_at', 'updated_at'],
        'indexes' => ['active', 'sort_order']
    ],
    'content_blocks' => [
        'description' => 'Dynamic content blocks for pages',
        'has_active' => true,
        'columns' => [
            'id', 'block_name', 'title', 'content', 'data', 'page',
            'sort_order', 'active', 'created_at', 'updated_at'
        ],
        'indexes' => ['block_name', 'page', 'active']
    ]
];

$response = [
    'status' => 'OK',
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => DB_NAME,
    'host' => DB_HOST,
    'tables_expected' => count($expectedSchema),
    'tables_found' => 0,
    'tables_verified' => 0,
    'verification' => [],
    'errors' => [],
    'warnings' => []
];

try {
    // Connect to database
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Get list of existing tables
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $response['tables_found'] = count($existingTables);
    
    // Verify each expected table
    foreach ($expectedSchema as $tableName => $tableSpec) {
        $tableResult = [
            'table' => $tableName,
            'description' => $tableSpec['description'],
            'status' => 'OK',
            'exists' => false,
            'columns_expected' => count($tableSpec['columns']),
            'columns_found' => 0,
            'columns_missing' => [],
            'columns_extra' => [],
            'indexes_expected' => count($tableSpec['indexes']),
            'indexes_found' => 0,
            'indexes_missing' => [],
            'has_active_column' => $tableSpec['has_active']
        ];
        
        // Check if table exists
        if (!in_array($tableName, $existingTables)) {
            $tableResult['status'] = 'MISSING';
            $tableResult['exists'] = false;
            $response['errors'][] = "Table '$tableName' does not exist";
        } else {
            $tableResult['exists'] = true;
            
            // Get columns for this table
            $stmt = $pdo->query("SHOW COLUMNS FROM `$tableName`");
            $columns = $stmt->fetchAll();
            $columnNames = array_column($columns, 'Field');
            $tableResult['columns_found'] = count($columnNames);
            
            // Check for missing columns
            foreach ($tableSpec['columns'] as $expectedCol) {
                if (!in_array($expectedCol, $columnNames)) {
                    $tableResult['columns_missing'][] = $expectedCol;
                    $tableResult['status'] = 'INCOMPLETE';
                    $response['errors'][] = "Table '$tableName' missing column '$expectedCol'";
                }
            }
            
            // Check for extra columns (warning only)
            foreach ($columnNames as $actualCol) {
                if (!in_array($actualCol, $tableSpec['columns'])) {
                    $tableResult['columns_extra'][] = $actualCol;
                    $response['warnings'][] = "Table '$tableName' has extra column '$actualCol' (not in expected schema)";
                }
            }
            
            // Get indexes for this table
            $stmt = $pdo->query("SHOW INDEX FROM `$tableName`");
            $indexes = $stmt->fetchAll();
            $indexNames = array_unique(array_column($indexes, 'Key_name'));
            // Filter out PRIMARY key
            $indexNames = array_filter($indexNames, function($name) {
                return $name !== 'PRIMARY';
            });
            $tableResult['indexes_found'] = count($indexNames);
            
            // Check for missing indexes (warning only, as index names may vary)
            $expectedIndexCount = $tableSpec['indexes_expected'];
            if ($tableResult['indexes_found'] < $expectedIndexCount) {
                $response['warnings'][] = "Table '$tableName' has fewer indexes than expected ($tableResult[indexes_found] vs $expectedIndexCount)";
            }
            
            // Verify 'active' column presence matches expectation
            $hasActive = in_array('active', $columnNames);
            if ($hasActive !== $tableSpec['has_active']) {
                $tableResult['status'] = 'INCORRECT';
                if ($tableSpec['has_active']) {
                    $response['errors'][] = "Table '$tableName' should have 'active' column but doesn't";
                } else {
                    $response['errors'][] = "Table '$tableName' has 'active' column but shouldn't";
                }
            }
            
            // Count as verified if no missing columns and active column matches
            if (empty($tableResult['columns_missing']) && $hasActive === $tableSpec['has_active']) {
                $response['tables_verified']++;
            }
        }
        
        $response['verification'][$tableName] = $tableResult;
    }
    
    // Check for unexpected tables (info only)
    $expectedTableNames = array_keys($expectedSchema);
    foreach ($existingTables as $actualTable) {
        if (!in_array($actualTable, $expectedTableNames)) {
            $response['warnings'][] = "Database has unexpected table '$actualTable' (not in expected schema)";
        }
    }
    
    // Set overall status
    if (!empty($response['errors'])) {
        $response['status'] = 'FAILED';
        $response['production_ready'] = false;
    } else {
        $response['status'] = 'OK';
        $response['production_ready'] = ($response['tables_verified'] === $response['tables_expected']);
    }
    
    // Add summary
    $response['summary'] = sprintf(
        "%d of %d tables verified successfully",
        $response['tables_verified'],
        $response['tables_expected']
    );
    
} catch (PDOException $e) {
    $response['status'] = 'ERROR';
    $response['error'] = $e->getMessage();
    $response['production_ready'] = false;
    $response['help'] = [
        'Check database credentials in api/config.php',
        'Ensure MySQL server is running',
        'Verify database exists: CREATE DATABASE IF NOT EXISTS ' . DB_NAME,
        'Verify user has privileges: GRANT ALL ON ' . DB_NAME . '.* TO ' . DB_USER . '@localhost'
    ];
}

// Output result
$json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo $json;

// Exit with appropriate code for CLI
if ($isCli) {
    exit($response['status'] === 'OK' ? 0 : 1);
}
