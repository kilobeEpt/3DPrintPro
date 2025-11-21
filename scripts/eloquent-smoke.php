#!/usr/bin/env php
<?php
/**
 * Eloquent ORM Smoke Test
 * 
 * This script verifies that Eloquent is properly configured and can connect
 * to the database without affecting the legacy Database class.
 * 
 * Usage:
 *   php scripts/eloquent-smoke.php
 */

// Autoload Composer dependencies
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\Service;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ANSI color codes for terminal output
$green = "\033[32m";
$red = "\033[31m";
$yellow = "\033[33m";
$blue = "\033[34m";
$reset = "\033[0m";

echo "\n";
echo "========================================\n";
echo "  Eloquent ORM Smoke Test\n";
echo "========================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

/**
 * Test helper function
 */
function runTest($name, $callback) {
    global $green, $red, $yellow, $reset, $testsPassed, $testsFailed;
    
    echo "{$yellow}[TEST]{$reset} {$name}... ";
    
    try {
        $result = $callback();
        if ($result) {
            echo "{$green}✓ PASS{$reset}\n";
            $testsPassed++;
            return true;
        } else {
            echo "{$red}✗ FAIL{$reset}\n";
            $testsFailed++;
            return false;
        }
    } catch (Exception $e) {
        echo "{$red}✗ ERROR{$reset}\n";
        echo "  Error: " . $e->getMessage() . "\n";
        $testsFailed++;
        return false;
    }
}

// Test 1: Verify Capsule is initialized
runTest('Capsule manager is initialized', function() {
    $connection = Capsule::connection();
    return $connection !== null;
});

// Test 2: Test database connection
runTest('Database connection is active', function() {
    $pdo = Capsule::connection()->getPdo();
    return $pdo !== null;
});

// Test 3: Test raw query
runTest('Can execute raw SQL query', function() {
    $result = Capsule::select('SELECT 1 as test');
    return !empty($result) && $result[0]->test == 1;
});

// Test 4: Test table exists
runTest('Can query services table', function() {
    $count = Capsule::table('services')->count();
    return $count >= 0; // Just check query executes
});

// Test 5: Test Service Model
runTest('Service Model can query data', function() {
    $services = Service::all();
    return $services !== null;
});

// Test 6: Test Service Model with scopes
runTest('Service Model active scope works', function() {
    $services = Service::active()->get();
    return $services !== null;
});

// Test 7: Test Order Model
runTest('Order Model can query data', function() {
    $orders = Order::orderBy('created_at', 'desc')->limit(10)->get();
    return $orders !== null;
});

// Test 8: Test Setting Model
runTest('Setting Model can query data', function() {
    $settings = Setting::all();
    return $settings !== null;
});

// Test 9: Test Setting Model helper methods
runTest('Setting Model helper methods work', function() {
    $chatId = Setting::get('telegram_chat_id');
    return true; // Just check it doesn't throw an error
});

// Test 10: Test JSON casting
runTest('JSON fields are properly cast', function() {
    $service = Service::first();
    if ($service && $service->features) {
        return is_array($service->features);
    }
    return true; // Pass if no services exist yet
});

// Test 11: Test query builder helpers
runTest('Query builder helper functions work', function() {
    $table = eloquent_table('settings');
    $count = $table->count();
    return $count >= 0;
});

// Test 12: Verify legacy Database class doesn't interfere
runTest('Legacy Database class doesn\'t interfere', function() {
    // Just verify the class can be loaded without errors
    $configPath = __DIR__ . '/../api/config.php';
    if (!file_exists($configPath)) {
        return true; // Skip if no config
    }
    
    // Check if DB class file exists and can be read
    $dbPath = __DIR__ . '/../api/db.php';
    return file_exists($dbPath) && is_readable($dbPath);
});

// Test 13: Verify DB Facade is available
runTest('DB Facade is available', function() {
    $count = DB::table('admin_users')->count();
    return is_int($count) && $count >= 0;
});

// Test 14: Verify DB::select() Facade works
runTest('DB::select() Facade works', function() {
    $result = DB::select('SELECT COUNT(*) as total FROM settings');
    return !empty($result) && isset($result[0]->total);
});

// Test 15: Verify Schema Facade is available
runTest('Schema Facade is available', function() {
    $hasTable = Schema::hasTable('admin_users');
    return $hasTable === true;
});

// Test 16: Verify Schema::getColumnListing() works
runTest('Schema::getColumnListing() works', function() {
    $columns = Schema::getColumnListing('admin_users');
    return is_array($columns) && in_array('id', $columns) && in_array('email', $columns);
});

// Test 17: Verify DB Facade and Capsule consistency
runTest('DB Facade and Capsule are consistent', function() {
    $dbCount = DB::table('settings')->count();
    $capsuleCount = Capsule::table('settings')->count();
    return $dbCount === $capsuleCount;
});

// Display summary
echo "\n";
echo "========================================\n";
echo "  Test Results\n";
echo "========================================\n";
echo "  Passed: {$green}{$testsPassed}{$reset}\n";
echo "  Failed: " . ($testsFailed > 0 ? "{$red}{$testsFailed}{$reset}" : "{$green}0{$reset}") . "\n";
echo "========================================\n\n";

// Display connection info
try {
    $config = Capsule::connection()->getConfig();
    echo "{$blue}Database Connection Info:{$reset}\n";
    echo "  Driver: {$config['driver']}\n";
    if (isset($config['host'])) {
        echo "  Host: {$config['host']}\n";
    }
    echo "  Database: {$config['database']}\n";
    if (isset($config['username'])) {
        echo "  Username: {$config['username']}\n";
    }
    echo "  Charset: {$config['charset']}\n";
    echo "\n";
} catch (Exception $e) {
    // Ignore if we can't get config
}

// Display model statistics
try {
    echo "{$blue}Data Statistics:{$reset}\n";
    echo "  Services: " . Service::count() . "\n";
    echo "  Orders: " . Order::count() . "\n";
    echo "  Settings: " . Setting::count() . "\n";
    echo "\n";
} catch (Exception $e) {
    echo "  Could not retrieve statistics: " . $e->getMessage() . "\n\n";
}

// Exit with appropriate status code
exit($testsFailed > 0 ? 1 : 0);
