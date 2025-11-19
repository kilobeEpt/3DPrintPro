#!/usr/bin/env php
<?php
/**
 * Migration System Validation Script
 * 
 * Validates migration and seeder files for syntax errors and completeness
 * without requiring a database connection.
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║       Migration System Validation Script                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$errors = [];
$warnings = [];
$passed = 0;

// Check required directories
echo "🔍 Checking directory structure...\n";
$requiredDirs = [
    __DIR__ . '/../database/migrations',
    __DIR__ . '/../database/seeders',
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        $errors[] = "Missing required directory: " . basename($dir);
    } else {
        echo "  ✓ " . basename($dir) . "\n";
        $passed++;
    }
}

// Check required files
echo "\n🔍 Checking base classes...\n";
$requiredFiles = [
    __DIR__ . '/../database/Migration.php' => 'Migration',
    __DIR__ . '/../database/Seeder.php' => 'Seeder',
    __DIR__ . '/../scripts/migrate' => 'migrate',
    __DIR__ . '/../scripts/seed' => 'seed',
];

foreach ($requiredFiles as $file => $name) {
    if (!file_exists($file)) {
        $errors[] = "Missing required file: $name";
    } else {
        echo "  ✓ $name\n";
        $passed++;
    }
}

// Validate migration files
echo "\n🔍 Validating migration files...\n";
$migrationsPath = __DIR__ . '/../database/migrations';
$migrationFiles = glob($migrationsPath . '/*.php');
sort($migrationFiles);

$expectedMigrations = 19;
if (count($migrationFiles) < $expectedMigrations) {
    $errors[] = "Expected at least $expectedMigrations migrations, found " . count($migrationFiles);
}

foreach ($migrationFiles as $file) {
    $name = basename($file);
    
    // Check file naming convention
    if (!preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_[a-z_]+\.php$/', $name)) {
        $warnings[] = "Migration $name does not follow naming convention";
    }
    
    // Check file is readable
    if (!is_readable($file)) {
        $errors[] = "Migration $name is not readable";
        continue;
    }
    
    // Check syntax
    $output = [];
    $returnVar = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnVar);
    
    if ($returnVar !== 0) {
        $errors[] = "Syntax error in $name: " . implode("\n", $output);
    } else {
        echo "  ✓ $name\n";
        $passed++;
    }
    
    // Check for required methods
    $content = file_get_contents($file);
    if (!preg_match('/public\s+function\s+up\s*\(\s*\)/', $content)) {
        $errors[] = "Migration $name missing up() method";
    }
    if (!preg_match('/public\s+function\s+down\s*\(\s*\)/', $content)) {
        $errors[] = "Migration $name missing down() method";
    }
    if (!preg_match('/class\s+\w+\s+extends\s+Migration/', $content)) {
        $errors[] = "Migration $name does not extend Migration class";
    }
}

// Validate seeder files
echo "\n🔍 Validating seeder files...\n";
$seedersPath = __DIR__ . '/../database/seeders';
$seederFiles = glob($seedersPath . '/*.php');

$expectedSeeders = [
    'DatabaseSeeder.php',
    'OrderTypesSeeder.php',
    'OrderStatusesSeeder.php',
    'CategoriesSeeder.php',
    'MaterialsSeeder.php',
    'DefaultUserSeeder.php',
    'SettingsSeeder.php',
];

$foundSeeders = array_map('basename', $seederFiles);
$missingSeeders = array_diff($expectedSeeders, $foundSeeders);
if (!empty($missingSeeders)) {
    $errors[] = "Missing seeders: " . implode(', ', $missingSeeders);
}

foreach ($seederFiles as $file) {
    $name = basename($file);
    
    // Check file is readable
    if (!is_readable($file)) {
        $errors[] = "Seeder $name is not readable";
        continue;
    }
    
    // Check syntax
    $output = [];
    $returnVar = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnVar);
    
    if ($returnVar !== 0) {
        $errors[] = "Syntax error in $name: " . implode("\n", $output);
    } else {
        echo "  ✓ $name\n";
        $passed++;
    }
    
    // Check for required methods
    $content = file_get_contents($file);
    if (!preg_match('/public\s+function\s+run\s*\(\s*\)/', $content)) {
        $errors[] = "Seeder $name missing run() method";
    }
    if (!preg_match('/class\s+\w+\s+extends\s+Seeder/', $content)) {
        $errors[] = "Seeder $name does not extend Seeder class";
    }
}

// Check script executability
echo "\n🔍 Checking script permissions...\n";
$scripts = [
    __DIR__ . '/migrate',
    __DIR__ . '/seed',
];

foreach ($scripts as $script) {
    if (!is_executable($script)) {
        $warnings[] = basename($script) . " is not executable (run: chmod +x scripts/" . basename($script) . ")";
    } else {
        echo "  ✓ " . basename($script) . " is executable\n";
        $passed++;
    }
}

// Check documentation
echo "\n🔍 Checking documentation...\n";
$docs = [
    __DIR__ . '/../database/README_MIGRATIONS.md',
    __DIR__ . '/../database/MIGRATIONS.md',
];

foreach ($docs as $doc) {
    if (!file_exists($doc)) {
        $warnings[] = "Missing documentation: " . basename($doc);
    } else {
        echo "  ✓ " . basename($doc) . "\n";
        $passed++;
    }
}

// Summary
echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "                        VALIDATION SUMMARY                      \n";
echo "═══════════════════════════════════════════════════════════════\n";

echo "\n✅ Passed: $passed checks\n";

if (!empty($warnings)) {
    echo "\n⚠️  Warnings (" . count($warnings) . "):\n";
    foreach ($warnings as $warning) {
        echo "  • $warning\n";
    }
}

if (!empty($errors)) {
    echo "\n❌ Errors (" . count($errors) . "):\n";
    foreach ($errors as $error) {
        echo "  • $error\n";
    }
    echo "\n";
    exit(1);
} else {
    echo "\n";
    echo "✅ All validation checks passed!\n";
    echo "\n";
    echo "Next steps:\n";
    echo "  1. Configure database in .env file\n";
    echo "  2. Run: php scripts/migrate up\n";
    echo "  3. Run: php scripts/seed\n";
    echo "\n";
    exit(0);
}
