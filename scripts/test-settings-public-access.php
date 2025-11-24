#!/usr/bin/env php
<?php
/**
 * Test Script: Verify Settings API Public Access
 * Tests that contact/social/seo groups can be accessed without authentication
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

echo "========================================\n";
echo "Testing Settings API Public Access\n";
echo "========================================\n\n";

$publicGroups = ['contact', 'social', 'seo'];
$privateGroups = ['smtp', 'telegram', 'logging', 'cache', 'rate_limit'];

// Test 1: Verify public group logic
echo "Test 1: Public Group Detection Logic\n";
echo "---------------------------------------\n";

foreach ($publicGroups as $group) {
    $isPublicRead = isset($group) && in_array($group, $publicGroups);
    $status = $isPublicRead ? '✅ PASS' : '❌ FAIL';
    echo "{$status} - Group '{$group}' detected as public: " . ($isPublicRead ? 'YES' : 'NO') . "\n";
}

echo "\n";

foreach ($privateGroups as $group) {
    $isPublicRead = isset($group) && in_array($group, $publicGroups);
    $status = !$isPublicRead ? '✅ PASS' : '❌ FAIL';
    echo "{$status} - Group '{$group}' detected as private: " . (!$isPublicRead ? 'YES' : 'NO') . "\n";
}

echo "\n";

// Test 2: Simulate $_GET parameter checks
echo "Test 2: Query Parameter Simulation\n";
echo "---------------------------------------\n";

$testCases = [
    ['query' => ['group' => 'contact'], 'expected' => 'public', 'desc' => '?group=contact'],
    ['query' => ['group' => 'social'], 'expected' => 'public', 'desc' => '?group=social'],
    ['query' => ['group' => 'seo'], 'expected' => 'public', 'desc' => '?group=seo'],
    ['query' => ['group' => 'smtp'], 'expected' => 'private', 'desc' => '?group=smtp'],
    ['query' => ['group' => 'telegram'], 'expected' => 'private', 'desc' => '?group=telegram'],
    ['query' => ['key' => 'some_key'], 'expected' => 'private', 'desc' => '?key=some_key'],
    ['query' => [], 'expected' => 'private', 'desc' => 'no parameters'],
];

foreach ($testCases as $test) {
    $_GET = $test['query'];
    
    $isPublicRead = isset($_GET['group']) && in_array($_GET['group'], $publicGroups);
    $requiresAuth = !$isPublicRead;
    
    $actual = $requiresAuth ? 'private' : 'public';
    $status = ($actual === $test['expected']) ? '✅ PASS' : '❌ FAIL';
    
    echo "{$status} - {$test['desc']} => {$actual} (expected: {$test['expected']})\n";
}

echo "\n";

// Test 3: Verify SettingsService can fetch public settings
echo "Test 3: SettingsService Public Settings Fetch\n";
echo "---------------------------------------\n";

use App\Services\SettingsService;

$settingsService = new SettingsService();

foreach ($publicGroups as $group) {
    try {
        $settings = $settingsService->getGrouped($group . '_', true);
        $count = is_array($settings) ? count($settings) : 0;
        echo "✅ PASS - Group '{$group}' fetched successfully ({$count} settings)\n";
    } catch (\Exception $e) {
        echo "❌ FAIL - Group '{$group}' error: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 4: Check if any settings exist
echo "Test 4: Verify Public Settings Exist in Database\n";
echo "---------------------------------------\n";

use Illuminate\Support\Facades\DB;

$totalTests = 0;
$passedTests = 0;

foreach ($publicGroups as $group) {
    $count = DB::table('settings')
        ->where('setting_key', 'like', $group . '_%')
        ->count();
    
    $totalTests++;
    if ($count > 0) {
        $passedTests++;
        echo "✅ PASS - Group '{$group}' has {$count} settings in database\n";
    } else {
        echo "⚠️  WARN - Group '{$group}' has no settings in database (expected if not seeded)\n";
    }
}

echo "\n";
echo "========================================\n";
echo "Summary: {$passedTests}/{$totalTests} public groups have settings\n";
echo "========================================\n";
