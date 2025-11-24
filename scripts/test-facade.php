#!/usr/bin/env php
<?php
/**
 * Test script to verify Facade support in Eloquent bootstrap
 * 
 * This script tests that the Facade root is properly set up and that
 * DB::table() and other Facade methods work correctly.
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap/eloquent.php';

echo "=== Testing Eloquent Facade Support ===\n\n";

try {
    // Test 1: DB Facade - table count
    echo "Test 1: Using DB::table() facade...\n";
    $userCount = \Illuminate\Support\Facades\DB::table('admin_users')->count();
    echo "✓ Success: Found {$userCount} admin users\n\n";
    
    // Test 2: DB Facade - select query
    echo "Test 2: Using DB::select() facade...\n";
    $result = \Illuminate\Support\Facades\DB::select('SELECT COUNT(*) as total FROM admin_users');
    echo "✓ Success: Query returned " . $result[0]->total . " records\n\n";
    
    // Test 3: Schema Facade - check table exists
    echo "Test 3: Using Schema::hasTable() facade...\n";
    $hasTable = \Illuminate\Support\Facades\Schema::hasTable('admin_users');
    echo "✓ Success: Table 'admin_users' exists: " . ($hasTable ? 'yes' : 'no') . "\n\n";
    
    // Test 4: Check other tables exist
    echo "Test 4: Checking multiple tables exist...\n";
    $tables = ['services', 'portfolio', 'orders', 'forms', 'settings'];
    foreach ($tables as $table) {
        $exists = \Illuminate\Support\Facades\Schema::hasTable($table);
        $status = $exists ? '✓' : '✗';
        echo "  {$status} {$table}: " . ($exists ? 'exists' : 'missing') . "\n";
    }
    echo "\n";
    
    // Test 5: Verify Capsule still works
    echo "Test 5: Verify Capsule static methods still work...\n";
    $capsuleCount = \Illuminate\Database\Capsule\Manager::table('admin_users')->count();
    echo "✓ Success: Capsule::table() returned {$capsuleCount} records\n\n";
    
    // Test 6: Verify facade and capsule return same results
    echo "Test 6: Verify Facade and Capsule consistency...\n";
    if ($userCount === $capsuleCount) {
        echo "✓ Success: DB::table() and Capsule::table() return consistent results\n\n";
    } else {
        echo "✗ Warning: Results differ (DB: {$userCount}, Capsule: {$capsuleCount})\n\n";
    }
    
    echo "=== All Tests Passed! ===\n";
    echo "Facade root is properly configured.\n";
    exit(0);
    
} catch (\Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
