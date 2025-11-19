#!/usr/bin/env php
<?php
/**
 * Verify Test Setup Script
 * 
 * Checks that all test files and dependencies are correctly set up.
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     Test Setup Verification                                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$errors = [];
$warnings = [];
$passed = 0;

// Check 1: Composer autoload
echo "1. Checking Composer autoload...\n";
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "   ✓ vendor/autoload.php exists\n";
    $passed++;
} else {
    $errors[] = "vendor/autoload.php not found. Run: composer install";
}
echo "\n";

// Check 2: PHPUnit configuration
echo "2. Checking PHPUnit configuration...\n";
if (file_exists(__DIR__ . '/../phpunit.xml')) {
    echo "   ✓ phpunit.xml exists\n";
    $passed++;
} else {
    $errors[] = "phpunit.xml not found";
}
echo "\n";

// Check 3: Test bootstrap
echo "3. Checking test bootstrap...\n";
if (file_exists(__DIR__ . '/../tests/bootstrap.php')) {
    echo "   ✓ tests/bootstrap.php exists\n";
    $passed++;
} else {
    $errors[] = "tests/bootstrap.php not found";
}
echo "\n";

// Check 4: Unit tests
echo "4. Checking unit tests...\n";
$unitTests = [
    'tests/Unit/SettingsServiceTest.php',
    'tests/Unit/FormValidationTest.php',
];

foreach ($unitTests as $test) {
    if (file_exists(__DIR__ . '/../' . $test)) {
        echo "   ✓ {$test}\n";
        $passed++;
    } else {
        $errors[] = "{$test} not found";
    }
}
echo "\n";

// Check 5: Integration tests
echo "5. Checking integration tests...\n";
$integrationTests = [
    'tests/Integration/FormSubmissionTest.php',
];

foreach ($integrationTests as $test) {
    if (file_exists(__DIR__ . '/../' . $test)) {
        echo "   ✓ {$test}\n";
        $passed++;
    } else {
        $errors[] = "{$test} not found";
    }
}
echo "\n";

// Check 6: Smoke tests
echo "6. Checking smoke tests...\n";
$smokeTests = [
    'scripts/form-api-smoke.php',
    'scripts/test-settings-service.php',
    'scripts/eloquent-smoke.php',
];

foreach ($smokeTests as $test) {
    if (file_exists(__DIR__ . '/../' . $test)) {
        echo "   ✓ {$test}\n";
        $passed++;
    } else {
        $warnings[] = "{$test} not found (optional)";
    }
}
echo "\n";

// Check 7: Documentation
echo "7. Checking documentation...\n";
$docs = [
    'docs/TESTING.md',
    'tests/README.md',
    'TEST_IMPLEMENTATION_SUMMARY.md',
];

foreach ($docs as $doc) {
    if (file_exists(__DIR__ . '/../' . $doc)) {
        echo "   ✓ {$doc}\n";
        $passed++;
    } else {
        $warnings[] = "{$doc} not found (optional)";
    }
}
echo "\n";

// Check 8: PHPUnit binary
echo "8. Checking PHPUnit binary...\n";
if (file_exists(__DIR__ . '/../vendor/bin/phpunit')) {
    echo "   ✓ vendor/bin/phpunit exists\n";
    $passed++;
} else {
    $errors[] = "vendor/bin/phpunit not found. Run: composer install";
}
echo "\n";

// Check 9: composer.json scripts
echo "9. Checking composer.json test scripts...\n";
if (file_exists(__DIR__ . '/../composer.json')) {
    $composerData = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);
    
    if (isset($composerData['scripts']['test'])) {
        echo "   ✓ 'composer test' script configured\n";
        $passed++;
    } else {
        $errors[] = "'test' script not found in composer.json";
    }
    
    if (isset($composerData['require-dev']['phpunit/phpunit'])) {
        echo "   ✓ PHPUnit in require-dev\n";
        $passed++;
    } else {
        $errors[] = "PHPUnit not in require-dev. Run: composer require --dev phpunit/phpunit:^9.5";
    }
} else {
    $errors[] = "composer.json not found";
}
echo "\n";

// Check 10: PHP extensions
echo "10. Checking PHP extensions...\n";
$requiredExtensions = ['PDO', 'pdo_sqlite', 'mbstring', 'json'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✓ {$ext} extension loaded\n";
        $passed++;
    } else {
        $errors[] = "{$ext} extension not loaded";
    }
}
echo "\n";

// Summary
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║ Verification Results                                       ║\n";
echo "╠════════════════════════════════════════════════════════════╣\n";
printf("║ ✓ Checks Passed: %-42d ║\n", $passed);
printf("║ ✗ Errors: %-49d ║\n", count($errors));
printf("║ ⚠ Warnings: %-47d ║\n", count($warnings));
echo "╚════════════════════════════════════════════════════════════╝\n\n";

if (count($errors) > 0) {
    echo "❌ ERRORS:\n";
    foreach ($errors as $error) {
        echo "   • {$error}\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠️  WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "   • {$warning}\n";
    }
    echo "\n";
}

if (count($errors) === 0) {
    echo "🎉 All critical checks passed!\n\n";
    echo "Next steps:\n";
    echo "  1. Install dependencies: composer install\n";
    echo "  2. Run tests: composer test\n";
    echo "  3. Run smoke tests: php scripts/form-api-smoke.php\n";
    echo "  4. View documentation: cat docs/TESTING.md\n\n";
    exit(0);
} else {
    echo "❌ Please fix the errors above before running tests.\n\n";
    exit(1);
}
