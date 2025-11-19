#!/usr/bin/env php
<?php
/**
 * Comprehensive Migration System Test
 * 
 * Tests all components without requiring database connection
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     Migration System Comprehensive Test Suite                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$testsPassed = 0;
$testsFailed = 0;
$tests = [];

function test($name, $callback) {
    global $tests;
    $tests[] = ['name' => $name, 'callback' => $callback];
}

function pass($message) {
    global $testsPassed;
    echo "  ✅ $message\n";
    $testsPassed++;
    return true;
}

function fail($message) {
    global $testsFailed;
    echo "  ❌ $message\n";
    $testsFailed++;
    return false;
}

// ============================================================================
// Test Suite
// ============================================================================

test('Base Classes Exist', function() {
    $migrationFile = __DIR__ . '/../database/Migration.php';
    $seederFile = __DIR__ . '/../database/Seeder.php';
    
    if (!file_exists($migrationFile)) {
        return fail("Migration.php not found");
    }
    
    if (!file_exists($seederFile)) {
        return fail("Seeder.php not found");
    }
    
    // Check syntax
    exec("php -l " . escapeshellarg($migrationFile) . " 2>&1", $output, $ret);
    if ($ret !== 0) {
        return fail("Migration.php has syntax errors");
    }
    
    exec("php -l " . escapeshellarg($seederFile) . " 2>&1", $output, $ret);
    if ($ret !== 0) {
        return fail("Seeder.php has syntax errors");
    }
    
    return pass("Base classes exist and have valid syntax");
});

test('CLI Scripts Exist and Executable', function() {
    $migrateScript = __DIR__ . '/migrate';
    $seedScript = __DIR__ . '/seed';
    
    if (!file_exists($migrateScript)) {
        return fail("migrate script not found");
    }
    
    if (!file_exists($seedScript)) {
        return fail("seed script not found");
    }
    
    if (!is_executable($migrateScript)) {
        return fail("migrate script not executable");
    }
    
    if (!is_executable($seedScript)) {
        return fail("seed script not executable");
    }
    
    return pass("CLI scripts exist and are executable");
});

test('All 19 Migrations Exist', function() {
    $migrationsPath = __DIR__ . '/../database/migrations';
    $files = glob($migrationsPath . '/*.php');
    
    if (count($files) !== 19) {
        return fail("Expected 19 migrations, found " . count($files));
    }
    
    $expectedFiles = [
        '2025_01_15_000001_create_users_table.php',
        '2025_01_15_000002_create_customers_table.php',
        '2025_01_15_000003_create_categories_table.php',
        '2025_01_15_000004_create_materials_table.php',
        '2025_01_15_000005_create_order_types_table.php',
        '2025_01_15_000006_create_order_statuses_table.php',
        '2025_01_15_000007_create_services_table.php',
        '2025_01_15_000008_create_service_features_table.php',
        '2025_01_15_000009_create_tags_table.php',
        '2025_01_15_000010_create_portfolio_table.php',
        '2025_01_15_000011_create_portfolio_tags_table.php',
        '2025_01_15_000012_create_orders_table.php',
        '2025_01_15_000013_create_order_status_history_table.php',
        '2025_01_15_000014_create_testimonials_table.php',
        '2025_01_15_000015_create_faq_table.php',
        '2025_01_15_000016_create_content_blocks_table.php',
        '2025_01_15_000017_create_content_revisions_table.php',
        '2025_01_15_000018_create_settings_table.php',
        '2025_01_15_000019_create_audit_log_table.php',
    ];
    
    foreach ($expectedFiles as $expected) {
        if (!file_exists($migrationsPath . '/' . $expected)) {
            return fail("Missing migration: $expected");
        }
    }
    
    return pass("All 19 migrations exist");
});

test('Migration Files Have Valid Structure', function() {
    $migrationsPath = __DIR__ . '/../database/migrations';
    $files = glob($migrationsPath . '/*.php');
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $name = basename($file);
        
        // Check extends Migration
        if (!preg_match('/class\s+\w+\s+extends\s+Migration/', $content)) {
            return fail("$name does not extend Migration class");
        }
        
        // Check has up() method
        if (!preg_match('/public\s+function\s+up\s*\(\s*\)/', $content)) {
            return fail("$name missing up() method");
        }
        
        // Check has down() method
        if (!preg_match('/public\s+function\s+down\s*\(\s*\)/', $content)) {
            return fail("$name missing down() method");
        }
        
        // Check uses Schema Builder
        if (!preg_match('/Capsule::schema\(\)/', $content)) {
            return fail("$name does not use Capsule::schema()");
        }
        
        // Check syntax
        exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $ret);
        if ($ret !== 0) {
            return fail("$name has syntax errors");
        }
    }
    
    return pass("All migrations have valid structure and syntax");
});

test('All 7 Seeders Exist', function() {
    $seedersPath = __DIR__ . '/../database/seeders';
    
    $expectedSeeders = [
        'DatabaseSeeder.php',
        'OrderTypesSeeder.php',
        'OrderStatusesSeeder.php',
        'CategoriesSeeder.php',
        'MaterialsSeeder.php',
        'DefaultUserSeeder.php',
        'SettingsSeeder.php',
    ];
    
    foreach ($expectedSeeders as $seeder) {
        if (!file_exists($seedersPath . '/' . $seeder)) {
            return fail("Missing seeder: $seeder");
        }
    }
    
    return pass("All 7 seeders exist");
});

test('Seeder Files Have Valid Structure', function() {
    $seedersPath = __DIR__ . '/../database/seeders';
    $files = glob($seedersPath . '/*.php');
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $name = basename($file);
        
        // Check extends Seeder
        if (!preg_match('/class\s+\w+\s+extends\s+Seeder/', $content)) {
            return fail("$name does not extend Seeder class");
        }
        
        // Check has run() method
        if (!preg_match('/public\s+function\s+run\s*\(\s*\)/', $content)) {
            return fail("$name missing run() method");
        }
        
        // Check syntax
        exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $ret);
        if ($ret !== 0) {
            return fail("$name has syntax errors");
        }
    }
    
    return pass("All seeders have valid structure and syntax");
});

test('Composer Dependencies Available', function() {
    if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
        return fail("Vendor directory not found - run: ./composer install");
    }
    
    $required = [
        'illuminate/database',
        'illuminate/events',
        'vlucas/phpdotenv',
    ];
    
    $composerLock = json_decode(file_get_contents(__DIR__ . '/../composer.lock'), true);
    $installedPackages = array_column($composerLock['packages'], 'name');
    
    foreach ($required as $package) {
        if (!in_array($package, $installedPackages)) {
            return fail("Required package not installed: $package");
        }
    }
    
    return pass("All required Composer dependencies available");
});

test('Documentation Exists', function() {
    $docs = [
        __DIR__ . '/../database/README_MIGRATIONS.md',
        __DIR__ . '/../database/MIGRATIONS.md',
        __DIR__ . '/../database/INTEGRATION_GUIDE.md',
        __DIR__ . '/../MIGRATION_SYSTEM_SUMMARY.md',
        __DIR__ . '/../MIGRATION_DEPLOYMENT_CHECKLIST.md',
    ];
    
    foreach ($docs as $doc) {
        if (!file_exists($doc)) {
            return fail("Missing documentation: " . basename($doc));
        }
    }
    
    return pass("All documentation files exist");
});

test('Bootstrap File Exists', function() {
    $bootstrap = __DIR__ . '/../bootstrap/eloquent.php';
    
    if (!file_exists($bootstrap)) {
        return fail("bootstrap/eloquent.php not found");
    }
    
    // Check syntax
    exec("php -l " . escapeshellarg($bootstrap) . " 2>&1", $output, $ret);
    if ($ret !== 0) {
        return fail("bootstrap/eloquent.php has syntax errors");
    }
    
    return pass("Bootstrap file exists and has valid syntax");
});

test('Environment Configuration Files', function() {
    if (!file_exists(__DIR__ . '/../.env.example')) {
        return fail(".env.example not found");
    }
    
    $envExample = file_get_contents(__DIR__ . '/../.env.example');
    
    $requiredKeys = [
        'DB_CONNECTION',
        'DB_HOST',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
    ];
    
    foreach ($requiredKeys as $key) {
        if (strpos($envExample, $key) === false) {
            return fail(".env.example missing key: $key");
        }
    }
    
    return pass("Environment configuration files present");
});

test('Migration Order is Correct', function() {
    $migrationsPath = __DIR__ . '/../database/migrations';
    $files = glob($migrationsPath . '/*.php');
    sort($files);
    
    // Check order: lookup tables → core tables → junction tables
    $order = [
        'users',           // 1 - First (parent)
        'customers',       // 2 - Second (parent)
        'categories',      // 3 - Lookup
        'materials',       // 4 - Lookup
        'order_types',     // 5 - Lookup
        'order_statuses',  // 6 - Lookup
        'services',        // 7 - References categories, users
        'service_features', // 8 - References services
        'tags',            // 9 - Independent
        'portfolio',       // 10 - References categories, services, users
        'portfolio_tags',  // 11 - Junction (references portfolio, tags)
        'orders',          // 12 - References many tables
        'order_status_history', // 13 - References orders, order_statuses
        'testimonials',    // 14 - References customers, orders, users
        'faq',             // 15 - References categories, users
        'content_blocks',  // 16 - References users
        'content_revisions', // 17 - References content_blocks
        'settings',        // 18 - Independent
        'audit_log',       // 19 - References users
    ];
    
    $expectedOrder = array_map(function($table) {
        return 'create_' . $table . '_table';
    }, $order);
    
    $actualOrder = array_map(function($file) {
        $name = basename($file, '.php');
        preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.+)$/', $name, $matches);
        return $matches[1] ?? '';
    }, $files);
    
    if ($actualOrder !== $expectedOrder) {
        return fail("Migration order may not be optimal for foreign keys");
    }
    
    return pass("Migration order is correct for foreign key dependencies");
});

// ============================================================================
// Run Tests
// ============================================================================

echo "Running " . count($tests) . " tests...\n\n";

foreach ($tests as $test) {
    echo "🧪 {$test['name']}\n";
    $test['callback']();
    echo "\n";
}

// ============================================================================
// Summary
// ============================================================================

echo "═══════════════════════════════════════════════════════════════\n";
echo "                        TEST SUMMARY                            \n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$total = $testsPassed + $testsFailed;
$percentage = $total > 0 ? round(($testsPassed / $total) * 100, 1) : 0;

echo "✅ Passed: $testsPassed / $total ($percentage%)\n";
echo "❌ Failed: $testsFailed / $total\n";
echo "\n";

if ($testsFailed === 0) {
    echo "🎉 All tests passed! Migration system is ready.\n";
    echo "\n";
    echo "Next steps:\n";
    echo "  1. Configure database in .env file\n";
    echo "  2. Run: php scripts/migrate up\n";
    echo "  3. Run: php scripts/seed\n";
    echo "\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Please fix the issues above.\n";
    echo "\n";
    exit(1);
}
