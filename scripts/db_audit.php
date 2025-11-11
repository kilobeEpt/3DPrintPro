<?php
/**
 * ========================================
 * Database Audit Script
 * ========================================
 * 
 * CLI/HTTP-neutral audit tool for diagnosing database connectivity
 * and schema validation issues.
 * 
 * Usage:
 *   CLI:  php scripts/db_audit.php [--json]
 *   HTTP: http://your-domain.com/scripts/db_audit.php?format=json
 * 
 * Features:
 * - PDO connection validation
 * - MySQL version check
 * - User privileges verification
 * - Table existence validation
 * - Schema comparison against database/schema.sql
 * - Detailed error reporting
 * - Structured JSON output
 */

class DatabaseAuditor {
    private $results = [
        'success' => false,
        'timestamp' => '',
        'connection' => [],
        'privileges' => [],
        'tables' => [],
        'schema_validation' => [],
        'summary' => '',
        'errors' => [],
        'warnings' => []
    ];
    
    private $pdo = null;
    private $configLoaded = false;
    
    private $expectedTables = [
        'orders' => [
            'columns' => ['id', 'order_number', 'type', 'name', 'email', 'phone', 'telegram', 
                         'service', 'subject', 'message', 'amount', 'calculator_data', 'status', 
                         'telegram_sent', 'telegram_error', 'created_at', 'updated_at'],
            'indexes' => ['PRIMARY', 'order_number', 'idx_order_number', 'idx_phone', 'idx_email', 
                         'idx_status', 'idx_created_at']
        ],
        'settings' => [
            'columns' => ['id', 'setting_key', 'setting_value', 'updated_at'],
            'indexes' => ['PRIMARY', 'setting_key', 'idx_setting_key']
        ],
        'services' => [
            'columns' => ['id', 'name', 'slug', 'icon', 'description', 'features', 'price', 
                         'category', 'sort_order', 'active', 'featured', 'created_at', 'updated_at'],
            'indexes' => ['PRIMARY', 'slug', 'idx_active', 'idx_featured', 'idx_sort', 'idx_slug']
        ],
        'portfolio' => [
            'columns' => ['id', 'title', 'description', 'image_url', 'category', 'tags', 
                         'sort_order', 'active', 'created_at', 'updated_at'],
            'indexes' => ['PRIMARY', 'idx_active', 'idx_category', 'idx_sort']
        ],
        'testimonials' => [
            'columns' => ['id', 'name', 'position', 'avatar', 'text', 'rating', 'sort_order', 
                         'approved', 'active', 'created_at', 'updated_at'],
            'indexes' => ['PRIMARY', 'idx_active', 'idx_approved', 'idx_rating', 'idx_sort']
        ],
        'faq' => [
            'columns' => ['id', 'question', 'answer', 'sort_order', 'active', 'created_at', 'updated_at'],
            'indexes' => ['PRIMARY', 'idx_active', 'idx_sort']
        ],
        'content_blocks' => [
            'columns' => ['id', 'block_name', 'title', 'content', 'data', 'page', 'sort_order', 
                         'active', 'created_at', 'updated_at'],
            'indexes' => ['PRIMARY', 'block_name', 'idx_block_name', 'idx_page', 'idx_active']
        ]
    ];
    
    public function __construct() {
        $this->results['timestamp'] = date('Y-m-d H:i:s');
    }
    
    public function run() {
        $this->loadConfig();
        
        if (!$this->configLoaded) {
            $this->results['summary'] = 'Configuration file not found or invalid';
            return $this->results;
        }
        
        $this->testConnection();
        
        if ($this->pdo) {
            $this->checkMySQLVersion();
            $this->checkPrivileges();
            $this->checkTables();
            $this->validateSchema();
            $this->generateSummary();
        }
        
        return $this->results;
    }
    
    private function loadConfig() {
        $configPath = __DIR__ . '/../api/config.php';
        $configExamplePath = __DIR__ . '/../api/config.example.php';
        
        if (file_exists($configPath)) {
            require_once $configPath;
            $this->configLoaded = true;
            $this->results['connection']['config_source'] = 'api/config.php';
        } elseif (file_exists($configExamplePath)) {
            require_once $configExamplePath;
            $this->configLoaded = false;
            $this->results['errors'][] = 'Using config.example.php - production config.php not found';
            $this->results['warnings'][] = 'Please copy config.example.php to config.php and configure credentials';
            $this->results['connection']['config_source'] = 'api/config.example.php (EXAMPLE ONLY)';
        } else {
            $this->configLoaded = false;
            $this->results['errors'][] = 'No configuration file found (config.php or config.example.php)';
            $this->results['connection']['status'] = 'failed';
            $this->results['connection']['error'] = 'Configuration file missing';
        }
    }
    
    private function testConnection() {
        if (!$this->configLoaded) {
            return;
        }
        
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            $this->results['connection']['status'] = 'connected';
            $this->results['connection']['host'] = DB_HOST;
            $this->results['connection']['database'] = DB_NAME;
            $this->results['connection']['user'] = DB_USER;
            $this->results['connection']['charset'] = DB_CHARSET;
            
        } catch (PDOException $e) {
            $this->results['connection']['status'] = 'failed';
            $this->results['connection']['error'] = $e->getMessage();
            $this->results['connection']['error_code'] = $e->getCode();
            
            $errorMsg = 'Database connection failed: ' . $e->getMessage();
            
            if (strpos($e->getMessage(), 'Access denied') !== false) {
                $errorMsg .= ' - Check DB_USER and DB_PASS in config.php';
            } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
                $errorMsg .= ' - Database "' . DB_NAME . '" does not exist. Create it first.';
            } elseif (strpos($e->getMessage(), 'Connection refused') !== false) {
                $errorMsg .= ' - MySQL server is not running or not accessible at ' . DB_HOST;
            }
            
            $this->results['errors'][] = $errorMsg;
        }
    }
    
    private function checkMySQLVersion() {
        try {
            $stmt = $this->pdo->query('SELECT VERSION() as version');
            $result = $stmt->fetch();
            $version = $result['version'];
            
            $this->results['connection']['mysql_version'] = $version;
            
            $majorVersion = (int) explode('.', $version)[0];
            if ($majorVersion < 8) {
                $this->results['warnings'][] = "MySQL version $version detected. MySQL 8.0+ recommended for optimal performance.";
            }
            
        } catch (PDOException $e) {
            $this->results['warnings'][] = 'Could not determine MySQL version: ' . $e->getMessage();
        }
    }
    
    private function checkPrivileges() {
        try {
            $stmt = $this->pdo->query('SHOW GRANTS FOR CURRENT_USER()');
            $grants = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $requiredPrivileges = ['SELECT', 'INSERT', 'UPDATE', 'DELETE'];
            $grantedPrivileges = [];
            $missingPrivileges = [];
            
            $allGrantsText = implode(' ', $grants);
            
            foreach ($requiredPrivileges as $privilege) {
                if (stripos($allGrantsText, "ALL PRIVILEGES") !== false || 
                    stripos($allGrantsText, $privilege) !== false) {
                    $grantedPrivileges[] = $privilege;
                } else {
                    $missingPrivileges[] = $privilege;
                }
            }
            
            $this->results['privileges']['status'] = empty($missingPrivileges) ? 'ok' : 'insufficient';
            $this->results['privileges']['granted'] = $grantedPrivileges;
            
            if (!empty($missingPrivileges)) {
                $this->results['privileges']['missing'] = $missingPrivileges;
                $this->results['errors'][] = 'Missing required privileges: ' . implode(', ', $missingPrivileges);
            }
            
            if (stripos($allGrantsText, 'CREATE') !== false) {
                $this->results['privileges']['can_create_tables'] = true;
            } else {
                $this->results['privileges']['can_create_tables'] = false;
                $this->results['warnings'][] = 'CREATE privilege not granted - cannot create new tables';
            }
            
        } catch (PDOException $e) {
            $this->results['privileges']['status'] = 'error';
            $this->results['privileges']['error'] = $e->getMessage();
            $this->results['warnings'][] = 'Could not check privileges: ' . $e->getMessage();
        }
    }
    
    private function checkTables() {
        try {
            $stmt = $this->pdo->query('SHOW TABLES');
            $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $expectedTableNames = array_keys($this->expectedTables);
            $missingTables = array_diff($expectedTableNames, $existingTables);
            $extraTables = array_diff($existingTables, $expectedTableNames);
            
            $this->results['tables']['expected'] = count($expectedTableNames);
            $this->results['tables']['found'] = count($existingTables);
            $this->results['tables']['existing_tables'] = $existingTables;
            
            if (empty($missingTables)) {
                $this->results['tables']['status'] = 'ok';
            } else {
                $this->results['tables']['status'] = 'missing_tables';
                $this->results['tables']['missing'] = array_values($missingTables);
                $this->results['errors'][] = 'Missing tables: ' . implode(', ', $missingTables) . 
                    ' - Run database/schema.sql to create them';
            }
            
            if (!empty($extraTables)) {
                $this->results['tables']['extra'] = array_values($extraTables);
                $this->results['warnings'][] = 'Extra tables found (not in schema): ' . implode(', ', $extraTables);
            }
            
        } catch (PDOException $e) {
            $this->results['tables']['status'] = 'error';
            $this->results['tables']['error'] = $e->getMessage();
            $this->results['errors'][] = 'Could not list tables: ' . $e->getMessage();
        }
    }
    
    private function validateSchema() {
        if (isset($this->results['tables']['status']) && $this->results['tables']['status'] !== 'ok') {
            $this->results['schema_validation']['status'] = 'skipped';
            $this->results['schema_validation']['reason'] = 'Not all tables exist';
            return;
        }
        
        $schemaIssues = [];
        $tableDetails = [];
        
        foreach ($this->expectedTables as $tableName => $expectedSchema) {
            $tableStatus = $this->validateTableSchema($tableName, $expectedSchema);
            $tableDetails[$tableName] = $tableStatus;
            
            if ($tableStatus['status'] !== 'ok') {
                $schemaIssues[] = "$tableName: " . $tableStatus['issues_summary'];
            }
        }
        
        $this->results['schema_validation']['tables'] = $tableDetails;
        $this->results['schema_validation']['drift_detected'] = !empty($schemaIssues);
        
        if (empty($schemaIssues)) {
            $this->results['schema_validation']['status'] = 'ok';
        } else {
            $this->results['schema_validation']['status'] = 'drift_detected';
            $this->results['schema_validation']['issues'] = $schemaIssues;
            $this->results['errors'][] = 'Schema drift detected in ' . count($schemaIssues) . ' table(s)';
        }
    }
    
    private function validateTableSchema($tableName, $expectedSchema) {
        $status = [
            'status' => 'ok',
            'columns' => [],
            'indexes' => [],
            'issues' => [],
            'issues_summary' => ''
        ];
        
        try {
            $stmt = $this->pdo->query("DESCRIBE `$tableName`");
            $actualColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $missingColumns = array_diff($expectedSchema['columns'], $actualColumns);
            $extraColumns = array_diff($actualColumns, $expectedSchema['columns']);
            
            $status['columns']['expected'] = count($expectedSchema['columns']);
            $status['columns']['found'] = count($actualColumns);
            
            if (!empty($missingColumns)) {
                $status['status'] = 'schema_mismatch';
                $status['columns']['missing'] = array_values($missingColumns);
                $status['issues'][] = 'Missing columns: ' . implode(', ', $missingColumns);
            }
            
            if (!empty($extraColumns)) {
                $status['columns']['extra'] = array_values($extraColumns);
                $status['issues'][] = 'Extra columns: ' . implode(', ', $extraColumns);
            }
            
            $stmt = $this->pdo->query("SHOW INDEXES FROM `$tableName`");
            $actualIndexes = array_unique($stmt->fetchAll(PDO::FETCH_COLUMN, 2));
            
            $expectedIndexNames = $expectedSchema['indexes'];
            $missingIndexes = array_diff($expectedIndexNames, $actualIndexes);
            
            $status['indexes']['expected'] = count($expectedIndexNames);
            $status['indexes']['found'] = count($actualIndexes);
            
            if (!empty($missingIndexes)) {
                $status['indexes']['missing'] = array_values($missingIndexes);
                $status['issues'][] = 'Missing indexes: ' . implode(', ', $missingIndexes);
            }
            
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM `$tableName`");
            $result = $stmt->fetch();
            $status['record_count'] = (int) $result['count'];
            
            if (!empty($status['issues'])) {
                $status['issues_summary'] = implode('; ', $status['issues']);
            }
            
        } catch (PDOException $e) {
            $status['status'] = 'error';
            $status['error'] = $e->getMessage();
            $status['issues'][] = 'Could not validate schema: ' . $e->getMessage();
            $status['issues_summary'] = 'Validation error';
        }
        
        return $status;
    }
    
    private function generateSummary() {
        $errorCount = count($this->results['errors']);
        $warningCount = count($this->results['warnings']);
        
        if ($errorCount === 0 && $warningCount === 0) {
            $this->results['success'] = true;
            $this->results['summary'] = '✅ All checks passed successfully. Database is fully operational.';
        } elseif ($errorCount === 0 && $warningCount > 0) {
            $this->results['success'] = true;
            $this->results['summary'] = "⚠️  Database is operational but has $warningCount warning(s).";
        } else {
            $this->results['success'] = false;
            $this->results['summary'] = "❌ Database audit failed with $errorCount error(s) and $warningCount warning(s).";
        }
    }
    
    public function getResults() {
        return $this->results;
    }
    
    public function outputHuman() {
        $output = "\n";
        $output .= "========================================\n";
        $output .= "DATABASE AUDIT REPORT\n";
        $output .= "========================================\n";
        $output .= "Timestamp: " . $this->results['timestamp'] . "\n\n";
        
        $output .= "CONNECTION:\n";
        if (isset($this->results['connection']['status'])) {
            $status = $this->results['connection']['status'];
            $output .= "  Status: " . ($status === 'connected' ? '✅ Connected' : '❌ Failed') . "\n";
            
            if ($status === 'connected') {
                $output .= "  Host: " . $this->results['connection']['host'] . "\n";
                $output .= "  Database: " . $this->results['connection']['database'] . "\n";
                $output .= "  User: " . $this->results['connection']['user'] . "\n";
                $output .= "  MySQL Version: " . ($this->results['connection']['mysql_version'] ?? 'unknown') . "\n";
            } else {
                $output .= "  Error: " . ($this->results['connection']['error'] ?? 'unknown') . "\n";
            }
        }
        $output .= "\n";
        
        if (isset($this->results['privileges']['status'])) {
            $output .= "PRIVILEGES:\n";
            $output .= "  Status: " . ($this->results['privileges']['status'] === 'ok' ? '✅ OK' : '❌ Insufficient') . "\n";
            if (!empty($this->results['privileges']['granted'])) {
                $output .= "  Granted: " . implode(', ', $this->results['privileges']['granted']) . "\n";
            }
            if (!empty($this->results['privileges']['missing'])) {
                $output .= "  Missing: " . implode(', ', $this->results['privileges']['missing']) . "\n";
            }
            $output .= "\n";
        }
        
        if (isset($this->results['tables']['status'])) {
            $output .= "TABLES:\n";
            $output .= "  Expected: " . $this->results['tables']['expected'] . "\n";
            $output .= "  Found: " . $this->results['tables']['found'] . "\n";
            $output .= "  Status: " . ($this->results['tables']['status'] === 'ok' ? '✅ OK' : '❌ Missing tables') . "\n";
            
            if (!empty($this->results['tables']['missing'])) {
                $output .= "  Missing: " . implode(', ', $this->results['tables']['missing']) . "\n";
            }
            $output .= "\n";
        }
        
        if (isset($this->results['schema_validation']['status']) && 
            $this->results['schema_validation']['status'] !== 'skipped') {
            $output .= "SCHEMA VALIDATION:\n";
            $output .= "  Status: " . ($this->results['schema_validation']['status'] === 'ok' ? '✅ OK' : '❌ Drift detected') . "\n";
            
            if (!empty($this->results['schema_validation']['issues'])) {
                $output .= "  Issues:\n";
                foreach ($this->results['schema_validation']['issues'] as $issue) {
                    $output .= "    - $issue\n";
                }
            }
            
            if (!empty($this->results['schema_validation']['tables'])) {
                $output .= "\n  Table Details:\n";
                foreach ($this->results['schema_validation']['tables'] as $tableName => $details) {
                    $statusIcon = $details['status'] === 'ok' ? '✅' : '❌';
                    $output .= "    $statusIcon $tableName: ";
                    $output .= $details['columns']['found'] . " columns, ";
                    $output .= $details['indexes']['found'] . " indexes, ";
                    $output .= $details['record_count'] . " records\n";
                    
                    if (!empty($details['issues'])) {
                        foreach ($details['issues'] as $issue) {
                            $output .= "       ⚠️  $issue\n";
                        }
                    }
                }
            }
            $output .= "\n";
        }
        
        if (!empty($this->results['errors'])) {
            $output .= "ERRORS:\n";
            foreach ($this->results['errors'] as $error) {
                $output .= "  ❌ $error\n";
            }
            $output .= "\n";
        }
        
        if (!empty($this->results['warnings'])) {
            $output .= "WARNINGS:\n";
            foreach ($this->results['warnings'] as $warning) {
                $output .= "  ⚠️  $warning\n";
            }
            $output .= "\n";
        }
        
        $output .= "========================================\n";
        $output .= "SUMMARY: " . $this->results['summary'] . "\n";
        $output .= "========================================\n\n";
        
        return $output;
    }
    
    public function outputJSON() {
        return json_encode($this->results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

// Check if script is being run directly
if (php_sapi_name() === 'cli') {
    // CLI execution
    $jsonOutput = in_array('--json', $argv ?? []);
    
    $auditor = new DatabaseAuditor();
    $results = $auditor->run();
    
    if ($jsonOutput) {
        echo $auditor->outputJSON();
    } else {
        echo $auditor->outputHuman();
    }
    
    exit($results['success'] ? 0 : 1);
    
} elseif (isset($_SERVER['REQUEST_METHOD'])) {
    // HTTP execution
    $format = $_GET['format'] ?? 'html';
    
    $auditor = new DatabaseAuditor();
    $results = $auditor->run();
    
    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo $auditor->outputJSON();
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo $auditor->outputHuman();
    }
}
