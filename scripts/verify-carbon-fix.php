#!/usr/bin/env php
<?php

/**
 * Carbon Fix Verification Script
 * 
 * Verifies that all Carbon issues have been fixed:
 * 1. Syntax validation
 * 2. Carbon import verification
 * 3. Runtime Carbon::now() test
 * 4. Admin user operations test
 */

echo "🔍 Carbon Fix Verification\n";
echo str_repeat("=", 60) . "\n\n";

$errors = [];
$warnings = [];

// Files to check
$filesToCheck = [
    'app/Services/AdminAuthService.php',
    'app/Services/SettingsService.php',
    'app/Http/Controllers/Api/AdminUserController.php',
    'app/Models/AdminUser.php',
    'app/Models/AdminSession.php',
    'app/Models/AdminLoginAttempt.php',
    'app/Models/AdminActionLog.php',
    'app/Models/Order.php',
    'app/Models/FormSubmission.php',
    'app/Models/SettingsAudit.php',
];

echo "1️⃣  Checking file syntax...\n";
foreach ($filesToCheck as $file) {
    $path = __DIR__ . '/../' . $file;
    
    if (!file_exists($path)) {
        $warnings[] = "File not found: $file";
        echo "   ⚠️  $file - Not found\n";
        continue;
    }
    
    // Check syntax
    $output = [];
    $returnCode = 0;
    exec("php -l " . escapeshellarg($path) . " 2>&1", $output, $returnCode);
    
    if ($returnCode !== 0) {
        $errors[] = "Syntax error in $file: " . implode("\n", $output);
        echo "   ❌ $file - Syntax error\n";
    } else {
        echo "   ✅ $file - Valid syntax\n";
    }
}

echo "\n2️⃣  Checking Carbon imports...\n";
foreach ($filesToCheck as $file) {
    $path = __DIR__ . '/../' . $file;
    
    if (!file_exists($path)) {
        continue;
    }
    
    $content = file_get_contents($path);
    
    // Check if file uses Carbon
    $usesCarbon = preg_match('/\bCarbon::now\(\)/', $content);
    
    if ($usesCarbon) {
        // Check for proper import
        $hasImport = preg_match('/use\s+Illuminate\\\\Support\\\\Carbon;/', $content);
        
        if ($hasImport) {
            echo "   ✅ $file - Has Carbon import\n";
        } else {
            $errors[] = "$file uses Carbon but missing import";
            echo "   ❌ $file - Missing Carbon import\n";
        }
        
        // Check for old now() calls
        if (preg_match('/\bnow\(\)/', $content)) {
            $errors[] = "$file still has now() calls";
            echo "   ❌ $file - Still has now() calls\n";
        }
    } else {
        echo "   ⏭️  $file - No Carbon usage\n";
    }
}

echo "\n3️⃣  Testing runtime Carbon operations...\n";
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../bootstrap/eloquent.php';
    
    // Test Carbon import
    $now = \Illuminate\Support\Carbon::now();
    echo "   ✅ Carbon::now() works: " . $now->toDateTimeString() . "\n";
    
    // Test in a model context
    $testDate = \Illuminate\Support\Carbon::now()->subDays(5);
    echo "   ✅ Carbon date manipulation works: " . $testDate->toDateString() . "\n";
    
} catch (Exception $e) {
    $errors[] = "Runtime test failed: " . $e->getMessage();
    echo "   ❌ Runtime test failed: " . $e->getMessage() . "\n";
}

echo "\n4️⃣  Testing admin model operations...\n";
try {
    // Test AdminUser model
    $user = \App\Models\AdminUser::first();
    if ($user) {
        echo "   ✅ AdminUser model works\n";
        
        // Test timestamp access
        if ($user->created_at) {
            echo "   ✅ Timestamp access works: " . $user->created_at->toDateString() . "\n";
        }
    } else {
        $warnings[] = "No admin users found in database (expected for fresh install)";
        echo "   ⚠️  No admin users found (fresh install?)\n";
    }
    
    // Test AdminSession model
    $session = \App\Models\AdminSession::first();
    if ($session) {
        echo "   ✅ AdminSession model works\n";
    } else {
        echo "   ⚠️  No admin sessions found\n";
    }
    
} catch (Exception $e) {
    $errors[] = "Model test failed: " . $e->getMessage();
    echo "   ❌ Model test failed: " . $e->getMessage() . "\n";
}

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 Summary\n";
echo str_repeat("=", 60) . "\n";
echo "Files checked: " . count($filesToCheck) . "\n";
echo "Errors: " . count($errors) . "\n";
echo "Warnings: " . count($warnings) . "\n";

if (!empty($errors)) {
    echo "\n❌ ERRORS:\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠️  WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "   - $warning\n";
    }
}

if (empty($errors)) {
    echo "\n✅ All checks passed!\n";
    echo "Carbon imports and usage are correct.\n";
    exit(0);
} else {
    echo "\n❌ Some checks failed. Please review errors above.\n";
    exit(1);
}
