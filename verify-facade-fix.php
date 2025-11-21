#!/usr/bin/env php
<?php
/**
 * Quick verification that Facade root is properly set
 * 
 * This simple script tests that DB and Schema Facades work without errors.
 * Run: php verify-facade-fix.php
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/eloquent.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Testing Facade Support...\n";

try {
    // Test 1: DB::table()
    echo "1. Testing DB::table()... ";
    $count = DB::table('admin_users')->count();
    echo "✓ Works (found {$count} users)\n";
    
    // Test 2: DB::select()
    echo "2. Testing DB::select()... ";
    $result = DB::select('SELECT 1 as test');
    echo "✓ Works\n";
    
    // Test 3: Schema::hasTable()
    echo "3. Testing Schema::hasTable()... ";
    $exists = Schema::hasTable('admin_users');
    echo "✓ Works (table " . ($exists ? "exists" : "missing") . ")\n";
    
    // Test 4: Schema::getColumnListing()
    echo "4. Testing Schema::getColumnListing()... ";
    $columns = Schema::getColumnListing('admin_users');
    echo "✓ Works (found " . count($columns) . " columns)\n";
    
    echo "\n✅ All Facade tests passed!\n";
    echo "The Facade root is properly configured.\n";
    exit(0);
    
} catch (\RuntimeException $e) {
    if (strpos($e->getMessage(), 'facade root has not been set') !== false) {
        echo "\n❌ FAILED: Facade root not set\n";
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
    throw $e;
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
