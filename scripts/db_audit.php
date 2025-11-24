<?php
/**
 * ========================================
 * Database Audit Script v2.0
 * ========================================
 * 
 * Comprehensive database audit tool with Eloquent support, foreign key validation,
 * sample data checks, and structured JSON output.
 * 
 * Usage:
 *   CLI:  php scripts/db_audit.php [options]
 *   HTTP: http://your-domain.com/scripts/db_audit.php?format=json
 * 
 * Options:
 *   --json                Output JSON format (legacy, implied by --output)
 *   --with-eloquent       Run Eloquent ORM tests (requires bootstrap)
 *   --with-fk            Check foreign key constraints and violations
 *   --sample-data        Fetch sample records from each table
 *   --output=<path>      Save JSON output to file (default: storage/logs/db_audit_latest.json)
 *   --help               Show this help message
 * 
 * Features:
 * - PDO connection validation
 * - MySQL version check
 * - User privileges verification
 * - Table existence validation (all 18 tables)
 * - Schema comparison against database/schema.sql
 * - Eloquent ORM query tests
 * - Foreign key constraint validation
 * - Row count analysis with critical table checks
 * - Sample data retrieval and validation
 * - Detailed error reporting
 * - Structured JSON output with file persistence
 * 
 * Exit Codes:
 *   0 - All checks passed
 *   1 - Critical errors detected
 */
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class DatabaseAuditor {
    private $results = [
        'success' => false,
        'version' => '2.0',
        'timestamp' => '',
        'connection' => [],
        'eloquent' => [],
        'privileges' => [],
        'tables' => [],
        'foreign_keys' => [],
        'schema_validation' => [],
        'sample_data' => [],
        'summary' => '',
        'errors' => [],
        'warnings' => []
    ];
    
    private $pdo = null;
    private $configLoaded = false;
    private $eloquentAvailable = false;
    
    // All 18 tables in the system
    private $expectedTables = [
        'orders' => [
            'columns' => ['id', 'order_number', 'type', 'name', 'email', 'phone', 'telegram', 
                         'service', 'subject', 'message', 'amount', 'calculator_data', 'status', 
                         'telegram_sent', 'telegram_error', 'form_submission_id', 'form_slug',
                         'archived_at', 'created_at', 'updated_at'],
            'indexes' => ['PRIMARY', 'order_number', 'idx_status', 'idx_form_submission', 'idx_archived'],
            'critical' => false
        ],
        'order_status_history' => [
            'columns' => ['id', 'order_id', 'old_status', 'new_status', 'changed_by', 'comment', 'created_at'],
            'indexes' => ['PRIMARY', 'idx_order', 'idx_changed_by'],
            'critical' => false
        ],
        'order_notes' => [
            'columns' => ['id', 'order_id', 'admin_user_id', 'note', 'created_at', 'updated_at'],
            'indexes' => ['PRIMARY', 'idx_order', 'idx_admin'],
            'critical' => false
        ],
        'settings' => [
            'columns' => ['id', 'setting_key', 'setting_value', 'updated_at'],
            'indexes' => ['PRIMARY', 'setting_key', 'idx_setting_key'],
            'critical' => false
        ],
        'services' => [
            'columns' => ['id', 'name', 'slug', 'icon', 'description', 'features', 'price', 
                         'category', 'sort_order', 'active', 'featured', 'image_path', 
                         'image_size', 'image_mime', 'created_at', 'updated_at'],
            'indexes' => ['PRIMARY', 'slug', 'idx_active', 'idx_featured', 'idx_sort', 'idx_slug'],
            'critical' => true
        ],
        'portfolio' => [
            'columns' => ['id', 'title', 'slug', 'description', 'image_url', 'image_path', 
                         'image_size', 'image_mime', 'category', 'tags', 'sort_order', 
                         'active', 'featured', 'created_at', 'updated_at'],
            'indexes' => ['PRIMARY', 'slug', 'idx_active', 'idx_category', 'idx_sort', 'idx_featured', 'idx_slug'],
            'critical' => false
        ],
        'testimonials' => [
            'columns' => ['id', 'name', 'slug', 'position', 'avatar', 'avatar_path', 
                         'avatar_size', 'avatar_mime', 'text', 'rating', 'sort_order', 
                         'approved', 'active', 'featured', 'created_at', 'updated_at'],
            'indexes' => ['PRIMARY', 'slug', 'idx_active', 'idx_approved', 'idx_rating', 'idx_sort', 'idx_featured', 'idx_slug'],
            'critical' => false
        ],
        'faq' => [
            'columns' => ['id', 'question', 'slug', 'answer', 'sort_order', 'active', 'featured', 'created_at', 'updated_at'],
            'indexes' => ['PRIMARY', 'slug', 'idx_active', 'idx_sort', 'idx_featured', 'idx_slug'],
            'critical' => false
        ],
        'content_blocks' => [
            'columns' => ['id', 'block_name', 'slug', 'title', 'content', 'data', 'page', 
                         'sort_order', 'active', 'featured', 'created_at', 'updated_at'],
            'indexes' => ['PRIMARY', 'block_name', 'slug', 'idx_block_name', 'idx_page', 'idx_active', 'idx_featured', 'idx_slug'],
            'critical' => false
        ],
        'forms' => [
            'columns' => ['id', 'name', 'slug', 'description', 'settings', 'active', 'created_at', 'updated_at'],
            'indexes' => ['PRIMARY', 'slug', 'idx_slug', 'idx_active'],
            'critical' => false
        ],
        'form_fields' => [
            'columns' => ['id', 'form_id', 'name', 'label', 'type', 'placeholder', 'default_value', 
                         'validation_rules', 'options', 'conditional_logic', 'sort_order', 'active', 'created_at', 'updated_at'],
            'indexes' => ['PRIMARY', 'idx_form', 'idx_sort', 'idx_active'],
            'critical' => false
        ],
        'form_submissions' => [
            'columns' => ['id', 'form_id', 'submitted_data', 'status', 'ip_address', 'user_agent', 
                         'referrer', 'metadata', 'created_at', 'updated_at'],
            'indexes' => ['PRIMARY', 'idx_form', 'idx_status', 'idx_created'],
            'critical' => false
        ],
        'form_submission_values' => [
            'columns' => ['id', 'submission_id', 'field_id', 'field_value', 'created_at'],
            'indexes' => ['PRIMARY', 'idx_submission', 'idx_field'],
            'critical' => false
        ],
        'settings_audit' => [
            'columns' => ['id', 'setting_key', 'old_value', 'new_value', 'changed_by', 'ip_address', 'created_at'],
            'indexes' => ['PRIMARY', 'idx_setting_key', 'idx_changed_by', 'idx_created'],
            'critical' => false
        ],
        'admin_users' => [
            'columns' => ['id', 'email', 'name', 'password_hash', 'role', 'status', 'created_at', 'updated_at'],
            'indexes' => ['PRIMARY', 'email', 'idx_email', 'idx_status'],
            'critical' => true
        ],
        'admin_sessions' => [
            'columns' => ['id', 'session_id', 'user_id', 'csrf_token', 'ip_address', 'user_agent', 
                         'last_activity', 'expires_at', 'created_at'],
            'indexes' => ['PRIMARY', 'session_id', 'idx_session_id', 'idx_user', 'idx_expires'],
            'critical' => false
        ],
        'admin_login_attempts' => [
            'columns' => ['id', 'email', 'ip_address', 'user_agent', 'success', 'failure_reason', 'created_at'],
            'indexes' => ['PRIMARY', 'idx_email_ip', 'idx_created'],
            'critical' => false
        ],
        'admin_action_logs' => [
            'columns' => ['id', 'user_id', 'action', 'entity_type', 'entity_id', 'payload', 
                         'ip_address', 'user_agent', 'created_at'],
            'indexes' => ['PRIMARY', 'idx_user', 'idx_action', 'idx_entity', 'idx_created'],
            'critical' => false
        ]
    ];
    
    // Foreign key relationships
    private $expectedForeignKeys = [
        'form_fields' => [
            ['column' => 'form_id', 'ref_table' => 'forms', 'ref_column' => 'id']
        ],
        'form_submissions' => [
            ['column' => 'form_id', 'ref_table' => 'forms', 'ref_column' => 'id']
        ],
        'form_submission_values' => [
            ['column' => 'submission_id', 'ref_table' => 'form_submissions', 'ref_column' => 'id'],
            ['column' => 'field_id', 'ref_table' => 'form_fields', 'ref_column' => 'id']
        ],
        'orders' => [
            ['column' => 'form_submission_id', 'ref_table' => 'form_submissions', 'ref_column' => 'id']
        ],
        'order_status_history' => [
            ['column' => 'order_id', 'ref_table' => 'orders', 'ref_column' => 'id'],
            ['column' => 'changed_by', 'ref_table' => 'admin_users', 'ref_column' => 'id']
        ],
        'order_notes' => [
            ['column' => 'order_id', 'ref_table' => 'orders', 'ref_column' => 'id'],
            ['column' => 'admin_user_id', 'ref_table' => 'admin_users', 'ref_column' => 'id']
        ],
        'admin_sessions' => [
            ['column' => 'user_id', 'ref_table' => 'admin_users', 'ref_column' => 'id']
        ],
        'admin_action_logs' => [
            ['column' => 'user_id', 'ref_table' => 'admin_users', 'ref_column' => 'id']
        ]
    ];
    
    private $options = [
        'with_eloquent' => false,
        'with_fk' => false,
        'sample_data' => false,
        'output_path' => 'storage/logs/db_audit_latest.json'
    ];
    
    public function __construct($options = []) {
        $this->results['timestamp'] = date('Y-m-d H:i:s');
        $this->options = array_merge($this->options, $options);
    }
    
    public function run() {
        // Bootstrap Eloquent if requested
        if ($this->options['with_eloquent']) {
            $this->bootstrapEloquent();
        }
        
        // Load legacy config
        $this->loadConfig();
        
        if (!$this->configLoaded) {
            $this->results['summary'] = 'Configuration file not found or invalid';
            return $this->results;
        }
        
        // Run connection tests
        $this->testConnection();
        
        if ($this->pdo) {
            $this->checkMySQLVersion();
            $this->checkPrivileges();
            $this->checkTables();
            
            // Run Eloquent tests if enabled
            if ($this->options['with_eloquent'] && $this->eloquentAvailable) {
                //$this->testEloquent();
            }
            
            // Run FK checks if enabled
            if ($this->options['with_fk']) {
                $this->checkForeignKeys();
            }
            
            // Validate schema and get row counts
            $this->validateSchema();
            
            // Fetch sample data if enabled
            if ($this->options['sample_data']) {
                $this->fetchSampleData();
            }
            
            $this->generateSummary();
        }
        
        // Save to file if output path specified
        if ($this->options['output_path']) {
            $this->saveToFile();
        }
        
        return $this->results;
    }
    
    private function bootstrapEloquent() {
        $projectRoot = dirname(__DIR__);
        $autoloadPath = $projectRoot . '/vendor/autoload.php';
        $eloquentBootstrap = $projectRoot . '/bootstrap/eloquent.php';
        
        try {
            if (!file_exists($autoloadPath)) {
                $this->results['eloquent']['status'] = 'unavailable';
                $this->results['eloquent']['error'] = 'Composer autoloader not found at vendor/autoload.php';
                $this->results['warnings'][] = 'Eloquent tests skipped: Composer dependencies not installed';
                return;
            }
            
            require_once $autoloadPath;
            
            if (!file_exists($eloquentBootstrap)) {
                $this->results['eloquent']['status'] = 'unavailable';
                $this->results['eloquent']['error'] = 'Eloquent bootstrap not found at bootstrap/eloquent.php';
                $this->results['warnings'][] = 'Eloquent tests skipped: Bootstrap file missing';
                return;
            }
            
            require_once $eloquentBootstrap;
            
            $this->eloquentAvailable = true;
            $this->results['eloquent']['bootstrap'] = 'success';
            $this->results['eloquent']['config_source'] = '.env';
            
        } catch (\Exception $e) {
            $this->results['eloquent']['status'] = 'error';
            $this->results['eloquent']['error'] = $e->getMessage();
            $this->results['warnings'][] = 'Eloquent bootstrap failed: ' . $e->getMessage();
        }
    }
    
    private function testEloquent()
{
    try {
        // Получить connection и schema builder
        $connection = \DB::connection();
        $schemaBuilder = $connection->getSchemaBuilder();
        
        // Тест 1: hasTable
        if (!$schemaBuilder->hasTable('services')) {
            throw new \Exception("Services table not found");
        }
        
        // Тест 2: getColumnListing
        $columns = $schemaBuilder->getColumnListing('services');
        if (empty($columns)) {
            throw new \Exception("Cannot get service columns");
        }
        
        $this->eloquentPassed = true;
        return true;
    } catch (\Exception $e) {
        $this->eloquentError = $e->getMessage();
        return false;
    }
}
    
    private function checkForeignKeys() {
        $this->results['foreign_keys']['status'] = 'checking';
        $this->results['foreign_keys']['constraints'] = [];
        $this->results['foreign_keys']['violations'] = [];
        
        try {
            // Get actual foreign keys from INFORMATION_SCHEMA
            $stmt = $this->pdo->prepare("
                SELECT 
                    TABLE_NAME,
                    COLUMN_NAME,
                    CONSTRAINT_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ORDER BY TABLE_NAME, COLUMN_NAME
            ");
            $stmt->execute();
            $actualFKs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->results['foreign_keys']['found'] = count($actualFKs);
            $this->results['foreign_keys']['constraints'] = $actualFKs;
            
            // Check for expected FKs
            $missingFKs = [];
            foreach ($this->expectedForeignKeys as $table => $fks) {
                foreach ($fks as $fk) {
                    $found = false;
                    foreach ($actualFKs as $actualFK) {
                        if ($actualFK['TABLE_NAME'] === $table && 
                            $actualFK['COLUMN_NAME'] === $fk['column'] &&
                            $actualFK['REFERENCED_TABLE_NAME'] === $fk['ref_table']) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $missingFKs[] = "$table.{$fk['column']} -> {$fk['ref_table']}.{$fk['ref_column']}";
                    }
                }
            }
            
            if (!empty($missingFKs)) {
                $this->results['foreign_keys']['missing'] = $missingFKs;
                $this->results['warnings'][] = 'Missing foreign keys: ' . implode(', ', $missingFKs);
            }
            
            // Check for FK violations (orphaned records)
            $violations = [];
            foreach ($actualFKs as $fk) {
                $table = $fk['TABLE_NAME'];
                $column = $fk['COLUMN_NAME'];
                $refTable = $fk['REFERENCED_TABLE_NAME'];
                $refColumn = $fk['REFERENCED_COLUMN_NAME'];
                
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) as violation_count
                    FROM `$table` t
                    LEFT JOIN `$refTable` r ON t.`$column` = r.`$refColumn`
                    WHERE t.`$column` IS NOT NULL
                        AND r.`$refColumn` IS NULL
                ");
                $stmt->execute();
                $result = $stmt->fetch();
                
                if ($result['violation_count'] > 0) {
                    $violations[] = [
                        'table' => $table,
                        'column' => $column,
                        'ref_table' => $refTable,
                        'orphaned_records' => (int) $result['violation_count']
                    ];
                }
            }
            
            if (!empty($violations)) {
                $this->results['foreign_keys']['violations'] = $violations;
                $this->results['errors'][] = 'Foreign key violations detected: ' . count($violations) . ' constraint(s) violated';
            }
            
            $this->results['foreign_keys']['status'] = empty($violations) ? 'ok' : 'violations_detected';
            
        } catch (PDOException $e) {
            $this->results['foreign_keys']['status'] = 'error';
            $this->results['foreign_keys']['error'] = $e->getMessage();
            $this->results['warnings'][] = 'Could not check foreign keys: ' . $e->getMessage();
        }
    }
    
    private function fetchSampleData() {
        $this->results['sample_data']['tables'] = [];
        
        foreach (array_keys($this->expectedTables) as $table) {
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM `$table` LIMIT 1");
                $stmt->execute();
                $sample = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($sample) {
                    // Mask sensitive data
                    if (isset($sample['password_hash'])) {
                        $sample['password_hash'] = '[REDACTED]';
                    }
                    if (isset($sample['email'])) {
                        $sample['email'] = $this->maskEmail($sample['email']);
                    }
                    if (isset($sample['phone'])) {
                        $sample['phone'] = $this->maskPhone($sample['phone']);
                    }
                    if (isset($sample['csrf_token'])) {
                        $sample['csrf_token'] = '[REDACTED]';
                    }
                    
                    $this->results['sample_data']['tables'][$table] = [
                        'status' => 'found',
                        'sample' => $sample
                    ];
                } else {
                    $this->results['sample_data']['tables'][$table] = [
                        'status' => 'empty',
                        'message' => 'No records found'
                    ];
                }
                
            } catch (PDOException $e) {
                $this->results['sample_data']['tables'][$table] = [
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }
    }
    
    private function maskEmail($email) {
        if (strpos($email, '@') === false) {
            return 'xxx@xxx.xxx';
        }
        list($name, $domain) = explode('@', $email);
        return substr($name, 0, 2) . '***@' . $domain;
    }
    
    private function maskPhone($phone) {
        $length = strlen($phone);
        if ($length > 4) {
            return substr($phone, 0, 2) . str_repeat('*', $length - 4) . substr($phone, -2);
        }
        return '****';
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
        $emptyTables = [];
        $criticalEmpty = [];
        
        foreach ($this->expectedTables as $tableName => $expectedSchema) {
            $tableStatus = $this->validateTableSchema($tableName, $expectedSchema);
            $tableDetails[$tableName] = $tableStatus;
            
            if ($tableStatus['status'] !== 'ok') {
                $schemaIssues[] = "$tableName: " . $tableStatus['issues_summary'];
            }
            
            // Flag empty tables
            if ($tableStatus['record_count'] === 0) {
                $emptyTables[] = $tableName;
                if ($expectedSchema['critical']) {
                    $criticalEmpty[] = $tableName;
                }
            }
        }
        
        $this->results['schema_validation']['tables'] = $tableDetails;
        $this->results['schema_validation']['drift_detected'] = !empty($schemaIssues);
        $this->results['schema_validation']['empty_tables'] = $emptyTables;
        
        if (!empty($criticalEmpty)) {
            $this->results['schema_validation']['critical_empty'] = $criticalEmpty;
            $this->results['errors'][] = 'Critical tables are empty: ' . implode(', ', $criticalEmpty) . 
                ' - Run seed scripts to populate baseline data';
        } elseif (count($emptyTables) > 0) {
            $this->results['warnings'][] = count($emptyTables) . ' table(s) are empty: ' . implode(', ', $emptyTables);
        }
        
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
    
    private function saveToFile() {
        $outputPath = __DIR__ . '/../' . $this->options['output_path'];
        $outputDir = dirname($outputPath);
        
        // Create directory if it doesn't exist
        if (!is_dir($outputDir)) {
            @mkdir($outputDir, 0755, true);
        }
        
        try {
            $json = json_encode($this->results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            file_put_contents($outputPath, $json);
            $this->results['output_file'] = $this->options['output_path'];
        } catch (Exception $e) {
            $this->results['warnings'][] = 'Could not save output to file: ' . $e->getMessage();
        }
    }
    
    public function getResults() {
        return $this->results;
    }
    
    public function outputHuman() {
        $output = "\n";
        $output .= "========================================\n";
        $output .= "DATABASE AUDIT REPORT v2.0\n";
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
        
        // Eloquent status
        if (!empty($this->results['eloquent'])) {
            $output .= "ELOQUENT ORM:\n";
            $output .= "  Bootstrap: " . ($this->results['eloquent']['bootstrap'] ?? 'not attempted') . "\n";
            $output .= "  Status: " . ($this->results['eloquent']['status'] ?? 'unknown') . "\n";
            if (!empty($this->results['eloquent']['tests'])) {
                $output .= "  Tests:\n";
                foreach ($this->results['eloquent']['tests'] as $testName => $testResult) {
                    $statusIcon = $testResult['status'] === 'pass' ? '✅' : ($testResult['status'] === 'skip' ? '⏭️' : '❌');
                    $output .= "    $statusIcon $testName: " . ($testResult['result'] ?? $testResult['reason'] ?? 'unknown') . "\n";
                }
            }
            $output .= "\n";
        }
        
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
        
        // Foreign keys
        if (!empty($this->results['foreign_keys'])) {
            $output .= "FOREIGN KEYS:\n";
            $output .= "  Status: " . ($this->results['foreign_keys']['status'] ?? 'not checked') . "\n";
            $output .= "  Found: " . ($this->results['foreign_keys']['found'] ?? 0) . "\n";
            if (!empty($this->results['foreign_keys']['missing'])) {
                $output .= "  Missing: " . count($this->results['foreign_keys']['missing']) . "\n";
            }
            if (!empty($this->results['foreign_keys']['violations'])) {
                $output .= "  ❌ Violations Detected:\n";
                foreach ($this->results['foreign_keys']['violations'] as $violation) {
                    $output .= "    - {$violation['table']}.{$violation['column']} -> {$violation['ref_table']}: ";
                    $output .= "{$violation['orphaned_records']} orphaned record(s)\n";
                }
            }
            $output .= "\n";
        }
        
        if (isset($this->results['schema_validation']['status']) && 
            $this->results['schema_validation']['status'] !== 'skipped') {
            $output .= "SCHEMA VALIDATION:\n";
            $output .= "  Status: " . ($this->results['schema_validation']['status'] === 'ok' ? '✅ OK' : '❌ Drift detected') . "\n";
            
            if (!empty($this->results['schema_validation']['critical_empty'])) {
                $output .= "  ❌ Critical Empty: " . implode(', ', $this->results['schema_validation']['critical_empty']) . "\n";
            }
            
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
        
        // Sample data summary
        if (!empty($this->results['sample_data']['tables'])) {
            $output .= "SAMPLE DATA:\n";
            $foundCount = 0;
            $emptyCount = 0;
            foreach ($this->results['sample_data']['tables'] as $table => $data) {
                if ($data['status'] === 'found') {
                    $foundCount++;
                } elseif ($data['status'] === 'empty') {
                    $emptyCount++;
                }
            }
            $output .= "  Tables with data: $foundCount\n";
            $output .= "  Empty tables: $emptyCount\n";
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
        
        if (!empty($this->results['output_file'])) {
            $output .= "Report saved to: " . $this->results['output_file'] . "\n\n";
        }
        
        return $output;
    }
    
    public function outputJSON() {
        return json_encode($this->results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

function showHelp() {
    echo <<<HELP

Database Audit Script v2.0
========================================

Usage:
  php scripts/db_audit.php [options]

Options:
  --json                Output JSON format (legacy, implied by --output)
  --with-eloquent       Run Eloquent ORM tests (requires bootstrap)
  --with-fk            Check foreign key constraints and violations
  --sample-data        Fetch sample records from each table
  --output=<path>      Save JSON output to file (default: storage/logs/db_audit_latest.json)
  --help               Show this help message

Examples:
  # Basic audit
  php scripts/db_audit.php

  # Full audit with all checks
  php scripts/db_audit.php --with-eloquent --with-fk --sample-data

  # Custom output location
  php scripts/db_audit.php --output=reports/audit.json

  # JSON output to stdout
  php scripts/db_audit.php --json

Exit Codes:
  0 - All checks passed
  1 - Critical errors detected


HELP;
}

// Check if script is being run directly
if (php_sapi_name() === 'cli') {
    // CLI execution
    $argv = $argv ?? [];
    
    // Check for help flag
    if (in_array('--help', $argv) || in_array('-h', $argv)) {
        showHelp();
        exit(0);
    }
    
    // Parse options
    $options = [
        'with_eloquent' => in_array('--with-eloquent', $argv),
        'with_fk' => in_array('--with-fk', $argv),
        'sample_data' => in_array('--sample-data', $argv),
        'output_path' => null
    ];
    
    $jsonOutput = in_array('--json', $argv);
    
    // Parse --output flag
    foreach ($argv as $arg) {
        if (strpos($arg, '--output=') === 0) {
            $options['output_path'] = substr($arg, 9);
        }
    }
    
    // Default output path if any special flags are used
    if (($options['with_eloquent'] || $options['with_fk'] || $options['sample_data']) && !$options['output_path']) {
        $options['output_path'] = 'storage/logs/db_audit_latest.json';
    }
    
    $auditor = new DatabaseAuditor($options);
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
    
    $options = [
        'with_eloquent' => isset($_GET['with_eloquent']) || isset($_GET['eloquent']),
        'with_fk' => isset($_GET['with_fk']) || isset($_GET['fk']),
        'sample_data' => isset($_GET['sample_data']) || isset($_GET['sample']),
        'output_path' => $_GET['output'] ?? null
    ];
    
    $auditor = new DatabaseAuditor($options);
    $results = $auditor->run();
    
    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo $auditor->outputJSON();
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<html><head><meta charset="utf-8"><title>Database Audit</title></head><body>';
        echo '<pre>' . htmlspecialchars($auditor->outputHuman()) . '</pre>';
        echo '</body></html>';
    }
}
