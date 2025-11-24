#!/usr/bin/env php
<?php
/**
 * Database Provisioning Script v1.0
 * 
 * Automates database setup, user creation, schema import, and seeding.
 * 
 * This script:
 * 1. Creates MySQL database with UTF8MB4 collation
 * 2. Creates application user with proper privileges
 * 3. Imports schema from database/schema.sql
 * 4. Optionally seeds baseline data
 * 5. Verifies schema integrity
 * 
 * Usage:
 *   php scripts/provision-database.php [options]
 * 
 * Options:
 *   --admin-user=USER         MySQL admin username (default: root)
 *   --admin-password=PASS     MySQL admin password (prompts if not provided)
 *   --admin-host=HOST         MySQL admin host (default: localhost)
 *   --create-only             Only create database and user, skip schema import
 *   --import-only             Skip database/user creation, only import schema
 *   --seed                    Seed baseline data after schema import
 *   --force                   Force drop/recreate database if exists
 *   --help                    Show this help message
 * 
 * Environment Variables (read from .env):
 *   DB_ADMIN_USER             MySQL admin username
 *   DB_ADMIN_PASSWORD         MySQL admin password
 *   DB_HOST                   Target database host
 *   DB_DATABASE               Target database name
 *   DB_USERNAME               Application database user
 *   DB_PASSWORD               Application user password
 * 
 * Examples:
 *   # Full provisioning with seeding
 *   php scripts/provision-database.php --seed
 * 
 *   # Create database only
 *   php scripts/provision-database.php --create-only
 * 
 *   # Import schema only (database already exists)
 *   php scripts/provision-database.php --import-only
 * 
 *   # Force recreate database
 *   php scripts/provision-database.php --force --seed
 * 
 * Exit Codes:
 *   0 - Success
 *   1 - Configuration error
 *   2 - Connection error
 *   3 - Schema import error
 *   4 - Verification error
 */

// Ensure CLI execution
if (php_sapi_name() !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    echo "This script can only be run from the command line.\n";
    exit(1);
}

// Change to project root
chdir(__DIR__ . '/..');

// ========================================
// Parse CLI Arguments
// ========================================

$options = [
    'admin_user' => null,
    'admin_password' => null,
    'admin_host' => 'localhost',
    'create_only' => false,
    'import_only' => false,
    'seed' => false,
    'force' => false,
    'help' => false
];

for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    
    if ($arg === '--help' || $arg === '-h') {
        $options['help'] = true;
    } elseif ($arg === '--create-only') {
        $options['create_only'] = true;
    } elseif ($arg === '--import-only') {
        $options['import_only'] = true;
    } elseif ($arg === '--seed') {
        $options['seed'] = true;
    } elseif ($arg === '--force') {
        $options['force'] = true;
    } elseif (strpos($arg, '--admin-user=') === 0) {
        $options['admin_user'] = substr($arg, 13);
    } elseif (strpos($arg, '--admin-password=') === 0) {
        $options['admin_password'] = substr($arg, 17);
    } elseif (strpos($arg, '--admin-host=') === 0) {
        $options['admin_host'] = substr($arg, 13);
    } else {
        echo "❌ Unknown option: $arg\n";
        echo "Run with --help for usage information.\n\n";
        exit(1);
    }
}

// Show help
if ($options['help']) {
    $script = basename(__FILE__);
    echo file_get_contents(__FILE__);
    exit(0);
}

// ========================================
// Banner
// ========================================

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║       Database Provisioning Script v1.0                        ║\n";
echo "║       3D Print Pro - Automated Database Setup                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// ========================================
// Load Environment Configuration
// ========================================

echo "📋 Loading configuration...\n";

// Load .env if available
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Remove quotes
        $value = trim($value, '"\'');
        
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
    echo "   ✓ Loaded .env file\n";
} else {
    echo "   ⚠️  No .env file found, using config.php\n";
    
    // Fallback to config.php
    $configFile = __DIR__ . '/../api/config.php';
    if (file_exists($configFile)) {
        require_once $configFile;
        
        // Map constants to environment variables
        if (defined('DB_HOST')) $_ENV['DB_HOST'] = DB_HOST;
        if (defined('DB_NAME')) $_ENV['DB_DATABASE'] = DB_NAME;
        if (defined('DB_USER')) $_ENV['DB_USERNAME'] = DB_USER;
        if (defined('DB_PASS')) $_ENV['DB_PASSWORD'] = DB_PASS;
        
        echo "   ✓ Loaded config.php\n";
    } else {
        echo "\n❌ No configuration found!\n";
        echo "   Create .env or api/config.php first.\n\n";
        exit(1);
    }
}

// Get application database configuration
$appHost = $_ENV['DB_HOST'] ?? 'localhost';
$appDatabase = $_ENV['DB_DATABASE'] ?? null;
$appUsername = $_ENV['DB_USERNAME'] ?? null;
$appPassword = $_ENV['DB_PASSWORD'] ?? null;

if (!$appDatabase || !$appUsername) {
    echo "\n❌ Missing application database configuration!\n";
    echo "   Required: DB_DATABASE, DB_USERNAME\n";
    echo "   Configure in .env or api/config.php\n\n";
    exit(1);
}

// Get admin credentials
if (!$options['admin_user']) {
    $options['admin_user'] = $_ENV['DB_ADMIN_USER'] ?? 'root';
}

if (!$options['admin_password']) {
    $options['admin_password'] = $_ENV['DB_ADMIN_PASSWORD'] ?? null;
}

// Prompt for admin password if not provided
if (!$options['admin_password'] && !$options['import_only']) {
    echo "\n";
    echo "MySQL admin password is required to create database and user.\n";
    echo "Admin user: {$options['admin_user']}@{$options['admin_host']}\n";
    echo "\n";
    echo "Enter password (input hidden): ";
    
    // Disable echo for password input
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $options['admin_password'] = stream_get_line(STDIN, 1024, PHP_EOL);
    } else {
        system('stty -echo');
        $options['admin_password'] = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
    }
    
    if (!$options['admin_password']) {
        echo "\n❌ Password is required!\n\n";
        exit(1);
    }
}

echo "\n";
echo "Configuration:\n";
echo "  Admin Host:    {$options['admin_host']}\n";
echo "  Admin User:    {$options['admin_user']}\n";
echo "  Target Host:   $appHost\n";
echo "  Database:      $appDatabase\n";
echo "  App User:      $appUsername\n";
echo "\n";

// Validate conflicting options
if ($options['create_only'] && $options['import_only']) {
    echo "❌ Cannot use --create-only and --import-only together!\n\n";
    exit(1);
}

// ========================================
// Step 1: Create Database and User
// ========================================

if (!$options['import_only']) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Step 1: Create Database and User\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    try {
        // Connect as admin
        echo "🔌 Connecting to MySQL as admin...\n";
        $adminDsn = "mysql:host={$options['admin_host']}";
        $adminPdo = new PDO($adminDsn, $options['admin_user'], $options['admin_password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        echo "   ✓ Connected successfully\n\n";
        
        // Check if database exists
        $stmt = $adminPdo->query("SHOW DATABASES LIKE '$appDatabase'");
        $dbExists = $stmt->rowCount() > 0;
        
        if ($dbExists) {
            if ($options['force']) {
                echo "⚠️  Database '$appDatabase' exists. Forcing drop...\n";
                $adminPdo->exec("DROP DATABASE `$appDatabase`");
                echo "   ✓ Database dropped\n";
                $dbExists = false;
            } else {
                echo "ℹ️  Database '$appDatabase' already exists (use --force to recreate)\n";
            }
        }
        
        if (!$dbExists) {
            echo "📦 Creating database '$appDatabase'...\n";
            $adminPdo->exec("CREATE DATABASE `$appDatabase` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "   ✓ Database created with UTF8MB4 collation\n";
        }
        
        // Check if user exists
        $stmt = $adminPdo->query("SELECT User FROM mysql.user WHERE User = '$appUsername'");
        $userExists = $stmt->rowCount() > 0;
        
        if ($userExists) {
            if ($options['force']) {
                echo "⚠️  User '$appUsername' exists. Forcing drop...\n";
                $adminPdo->exec("DROP USER IF EXISTS '$appUsername'@'localhost'");
                $adminPdo->exec("DROP USER IF EXISTS '$appUsername'@'%'");
                echo "   ✓ User dropped\n";
                $userExists = false;
            } else {
                echo "ℹ️  User '$appUsername' already exists (use --force to recreate)\n";
            }
        }
        
        if (!$userExists) {
            echo "👤 Creating application user '$appUsername'...\n";
            
            // Create user for localhost
            $adminPdo->exec("CREATE USER '$appUsername'@'localhost' IDENTIFIED BY '$appPassword'");
            echo "   ✓ User created for localhost\n";
            
            // Grant privileges
            $adminPdo->exec("GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE, CREATE VIEW, SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE, TRIGGER ON `$appDatabase`.* TO '$appUsername'@'localhost'");
            echo "   ✓ Privileges granted (restricted, no GRANT OPTION)\n";
            
            // Flush privileges
            $adminPdo->exec("FLUSH PRIVILEGES");
            echo "   ✓ Privileges flushed\n";
        }
        
        echo "\n✅ Database and user setup complete!\n\n";
        
    } catch (PDOException $e) {
        echo "\n❌ Database creation failed!\n";
        echo "   Error: " . $e->getMessage() . "\n\n";
        exit(2);
    }
}

// Stop here if create-only
if ($options['create_only']) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Provisioning Complete (create-only mode)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    echo "Next steps:\n";
    echo "  1. Import schema: php scripts/provision-database.php --import-only\n";
    echo "  2. Verify schema: php database/verify-schema.php\n\n";
    exit(0);
}

// ========================================
// Step 2: Import Schema
// ========================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Step 2: Import Schema\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$schemaFile = __DIR__ . '/../database/schema.sql';
if (!file_exists($schemaFile)) {
    echo "❌ Schema file not found: $schemaFile\n\n";
    exit(3);
}

echo "📥 Importing schema from database/schema.sql...\n";

try {
    // Connect as application user
    $appDsn = "mysql:host=$appHost;dbname=$appDatabase;charset=utf8mb4";
    $appPdo = new PDO($appDsn, $appUsername, $appPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Read and execute schema
    $sql = file_get_contents($schemaFile);
    
    // Split by semicolons (simple approach)
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            // Skip empty statements and comments
            return !empty($stmt) && strpos($stmt, '--') !== 0;
        }
    );
    
    $successCount = 0;
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $appPdo->exec($statement);
                $successCount++;
            } catch (PDOException $e) {
                // Ignore "table already exists" errors
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate key') === false) {
                    throw $e;
                }
            }
        }
    }
    
    echo "   ✓ Executed $successCount SQL statements\n";
    echo "   ✓ Schema imported successfully\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ Schema import failed!\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
    exit(3);
}

// ========================================
// Step 3: Verify Schema
// ========================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Step 3: Verify Schema\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "🔍 Running schema verification...\n";

// Execute verify-schema.php
ob_start();
require __DIR__ . '/../database/verify-schema.php';
$verifyOutput = ob_get_clean();

$verifyResult = json_decode($verifyOutput, true);

if ($verifyResult && $verifyResult['status'] === 'SUCCESS') {
    echo "   ✓ All " . $verifyResult['summary']['total_tables'] . " tables verified\n";
    echo "   ✓ Schema is valid\n\n";
} else {
    echo "   ⚠️  Schema verification had issues\n";
    if ($verifyResult && isset($verifyResult['issues'])) {
        foreach ($verifyResult['issues'] as $issue) {
            echo "      - {$issue['severity']}: {$issue['message']}\n";
        }
    }
    echo "\n";
}

// ========================================
// Step 4: Seed Data (Optional)
// ========================================

if ($options['seed']) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Step 4: Seed Baseline Data\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $seeders = [
        'database/seed-data.php' => 'Core content (services, testimonials, FAQ)',
        'scripts/seed-forms.php' => 'Dynamic forms',
        'scripts/seed-calculator-settings.php' => 'Calculator configuration',
        'scripts/seed-global-settings.php' => 'Global settings'
    ];
    
    foreach ($seeders as $seederPath => $description) {
        $fullPath = __DIR__ . '/../' . $seederPath;
        
        if (!file_exists($fullPath)) {
            echo "⚠️  Seeder not found: $seederPath\n";
            continue;
        }
        
        echo "🌱 Seeding: $description\n";
        echo "   Running: $seederPath\n";
        
        // Execute seeder
        if (pathinfo($seederPath, PATHINFO_EXTENSION) === 'php' && strpos($seederPath, 'scripts/') === 0) {
            // Execute as script
            $output = [];
            $returnCode = 0;
            exec("php $fullPath 2>&1", $output, $returnCode);
            
            if ($returnCode === 0) {
                echo "   ✓ Seeding complete\n";
            } else {
                echo "   ⚠️  Seeding had issues (exit code: $returnCode)\n";
                foreach ($output as $line) {
                    echo "      $line\n";
                }
            }
        } else {
            // Load and process seed data
            try {
                $seedData = require $fullPath;
                
                if (is_array($seedData)) {
                    $recordCount = 0;
                    foreach ($seedData as $table => $records) {
                        if (is_array($records)) {
                            foreach ($records as $record) {
                                $columns = implode(', ', array_keys($record));
                                $placeholders = implode(', ', array_fill(0, count($record), '?'));
                                
                                $sql = "INSERT IGNORE INTO $table ($columns) VALUES ($placeholders)";
                                $stmt = $appPdo->prepare($sql);
                                $stmt->execute(array_values($record));
                                $recordCount += $stmt->rowCount();
                            }
                        }
                    }
                    echo "   ✓ Inserted $recordCount records\n";
                } else {
                    echo "   ⚠️  Invalid seed data format\n";
                }
            } catch (Exception $e) {
                echo "   ⚠️  Seeding error: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n";
    }
    
    echo "✅ Seeding complete!\n\n";
}

// ========================================
// Summary and Next Steps
// ========================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Database Provisioning Complete!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Database: $appDatabase\n";
echo "User:     $appUsername\n";
echo "Host:     $appHost\n";
echo "\n";

if (!$options['seed']) {
    echo "💡 Tip: Run with --seed flag to populate baseline data\n\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📦 Backup Automation\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$projectRoot = realpath(__DIR__ . '/..');

echo "Add these cron jobs for automated backups:\n\n";
echo "# Daily full backup at 2 AM (keep 30 days)\n";
echo "0 2 * * * cd $projectRoot && php database/backup.php --retention=30 >> logs/backup.log 2>&1\n\n";
echo "# Weekly schema-only backup (keep 12 weeks)\n";
echo "0 3 * * 0 cd $projectRoot && php database/backup.php --schema-only --retention=12 >> logs/backup.log 2>&1\n\n";
echo "# Monthly archive (keep 12 months)\n";
echo "0 4 1 * * cd $projectRoot && php database/backup.php --retention=365 >> logs/backup.log 2>&1\n\n";

echo "Backup location: $projectRoot/storage/backups/\n";
echo "\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📚 Next Steps\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "1. Test database connection:\n";
echo "   php database/verify-schema.php\n\n";

echo "2. Create admin user:\n";
echo "   php scripts/create-admin.php admin@example.com \"Admin User\" \"SecurePassword123\"\n\n";

echo "3. Setup backup automation:\n";
echo "   crontab -e  # Add cron jobs shown above\n\n";

echo "4. Test backup:\n";
echo "   php database/backup.php --verify\n\n";

echo "5. Review documentation:\n";
echo "   docs/DATABASE_OPERATIONS.md - Database management guide\n";
echo "   docs/DEPLOYMENT.md - Full deployment guide\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

exit(0);
