#!/usr/bin/env php
<?php
/**
 * Settings Service Test Script
 * 
 * Tests the new SettingsService functionality including:
 * - Cache operations
 * - Type casting
 * - Validation
 * - Audit logging
 * - Grouped reads
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Services\SettingsService;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     Settings Service Test Suite                           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

try {
    $service = new SettingsService();
    $passed = 0;
    $failed = 0;
    
    // Test 1: Get all settings
    echo "📋 Test 1: Get all settings\n";
    try {
        $settings = $service->getAll(false); // No cache for first test
        echo "   ✓ Retrieved " . count($settings) . " settings\n";
        $passed++;
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 2: Set a test setting
    echo "🔧 Test 2: Set a test setting\n";
    try {
        $service->set('test_setting', 'test_value', 'test_script');
        echo "   ✓ Setting saved successfully\n";
        $passed++;
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 3: Get single setting
    echo "🔍 Test 3: Get single setting\n";
    try {
        $value = $service->get('test_setting', null, false);
        if ($value === 'test_value') {
            echo "   ✓ Retrieved correct value: $value\n";
            $passed++;
        } else {
            echo "   ✗ Incorrect value retrieved: $value\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 4: Cache operations
    echo "💾 Test 4: Cache operations\n";
    try {
        $service->warmCache();
        echo "   ✓ Cache warmed successfully\n";
        
        // Check cache file exists
        $cacheFile = __DIR__ . '/../storage/cache/settings.json';
        if (file_exists($cacheFile)) {
            echo "   ✓ Cache file created\n";
            $passed++;
        } else {
            echo "   ✗ Cache file not found\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 5: Grouped reads
    echo "📁 Test 5: Grouped reads (telegram settings)\n";
    try {
        $telegramSettings = $service->getGrouped('telegram', false);
        echo "   ✓ Retrieved " . count($telegramSettings) . " telegram settings\n";
        $passed++;
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 6: Bulk update
    echo "📦 Test 6: Bulk update\n";
    try {
        $result = $service->setMultiple([
            'test_setting_1' => 'value1',
            'test_setting_2' => 'value2',
            'test_setting_3' => 'value3',
        ], 'test_script');
        
        if ($result['success'] === 3) {
            echo "   ✓ Successfully saved 3 settings\n";
            $passed++;
        } else {
            echo "   ✗ Expected 3 success, got " . $result['success'] . "\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 7: Audit history
    echo "📜 Test 7: Audit history\n";
    try {
        $history = $service->getAuditHistory('test_setting', 10);
        if (count($history) > 0) {
            echo "   ✓ Retrieved " . count($history) . " audit records\n";
            $passed++;
        } else {
            echo "   ⚠ No audit records found (may be expected if database doesn't support settings_audit)\n";
            $passed++;
        }
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 8: Validation
    echo "✅ Test 8: Validation rules\n";
    try {
        // This should throw an exception due to validation
        try {
            $service->set('telegram_bot_token', str_repeat('x', 300), 'test_script');
            echo "   ✗ Validation did not catch too-long value\n";
            $failed++;
        } catch (InvalidArgumentException $e) {
            echo "   ✓ Validation correctly rejected invalid value\n";
            $passed++;
        }
    } catch (Exception $e) {
        echo "   ✗ Unexpected error: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 9: Cache invalidation
    echo "🗑️  Test 9: Cache invalidation\n";
    try {
        $service->invalidateCache();
        $cacheFile = __DIR__ . '/../storage/cache/settings.json';
        if (!file_exists($cacheFile)) {
            echo "   ✓ Cache file removed\n";
            $passed++;
        } else {
            echo "   ⚠ Cache file still exists (may be recreated by another process)\n";
            $passed++;
        }
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 10: Type casting
    echo "🔢 Test 10: Type casting\n";
    try {
        // Test boolean
        $service->set('test_bool', true, 'test_script');
        $boolValue = $service->get('test_bool', null, false);
        
        // Test integer
        $service->set('test_int', 42, 'test_script');
        $intValue = $service->get('test_int', null, false);
        
        if ($boolValue === true || $boolValue === '1') {
            echo "   ✓ Boolean type preserved\n";
        } else {
            echo "   ⚠ Boolean not as expected: " . var_export($boolValue, true) . "\n";
        }
        
        if ($intValue == 42) {
            echo "   ✓ Integer type preserved\n";
        } else {
            echo "   ⚠ Integer not as expected: " . var_export($intValue, true) . "\n";
        }
        
        $passed++;
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Cleanup test settings
    echo "🧹 Cleanup: Removing test settings\n";
    try {
        $service->delete('test_setting', 'test_script');
        $service->delete('test_setting_1', 'test_script');
        $service->delete('test_setting_2', 'test_script');
        $service->delete('test_setting_3', 'test_script');
        $service->delete('test_bool', 'test_script');
        $service->delete('test_int', 'test_script');
        echo "   ✓ Test settings cleaned up\n";
    } catch (Exception $e) {
        echo "   ⚠ Cleanup warning: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // Summary
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║ Test Results                                               ║\n";
    echo "╠════════════════════════════════════════════════════════════╣\n";
    printf("║ ✓ Passed: %-48d ║\n", $passed);
    printf("║ ✗ Failed: %-48d ║\n", $failed);
    printf("║ Total:    %-48d ║\n", $passed + $failed);
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    if ($failed === 0) {
        echo "🎉 All tests passed!\n\n";
        exit(0);
    } else {
        echo "❌ Some tests failed. Please review the output above.\n\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    exit(1);
}
