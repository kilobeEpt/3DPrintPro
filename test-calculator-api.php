#!/usr/bin/env php
<?php
/**
 * Test Calculator Settings API
 * 
 * Quick test to verify the API returns valid JSON
 */

// Simulate GET request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [];

// Capture output
ob_start();

try {
    require __DIR__ . '/api/calculator-settings.php';
    $output = ob_get_clean();
    
    // Try to decode JSON
    $decoded = json_decode($output, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ JSON DECODE ERROR: " . json_last_error_msg() . "\n";
        echo "Raw output:\n" . $output . "\n";
        exit(1);
    }
    
    echo "✅ Valid JSON response\n";
    echo "Success: " . ($decoded['success'] ? 'true' : 'false') . "\n";
    
    if (isset($decoded['data'])) {
        echo "Data keys: " . implode(', ', array_keys($decoded['data'])) . "\n";
        
        if (isset($decoded['data']['materials'])) {
            echo "Materials count: " . count($decoded['data']['materials']) . "\n";
        }
        if (isset($decoded['data']['services'])) {
            echo "Services count: " . count($decoded['data']['services']) . "\n";
        }
    }
    
    if (isset($decoded['error'])) {
        echo "❌ Error in response: " . $decoded['error'] . "\n";
        exit(1);
    }
    
    echo "\n✅ API test passed!\n";
    exit(0);
    
} catch (Exception $e) {
    $output = ob_get_clean();
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Output: " . $output . "\n";
    exit(1);
}
