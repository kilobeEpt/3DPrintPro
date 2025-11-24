#!/usr/bin/env php
<?php
/**
 * SSE Endpoint Headers Test
 * 
 * Tests that the SSE endpoint returns correct Content-Type and headers
 * without any early output or buffer issues.
 */

echo "=== SSE Endpoint Headers Test ===\n\n";

$sseEndpoint = __DIR__ . '/api/updates/stream.php';

// Check if file exists
if (!file_exists($sseEndpoint)) {
    echo "❌ FAILED: SSE endpoint file not found: {$sseEndpoint}\n";
    exit(1);
}

echo "✓ SSE endpoint file exists\n";

// Start output buffering to capture the script output
ob_start();

// Capture headers that would be sent
$headers = [];
header_register_callback(function() use (&$headers) {
    $headers = headers_list();
});

// Execute the script in a controlled way by including it
// We'll interrupt it early before the infinite loop
try {
    // Set a flag to detect if we're in test mode
    define('SSE_TEST_MODE', true);
    
    // We need to wrap this carefully to avoid the infinite loop
    $code = file_get_contents($sseEndpoint);
    
    // Check for early output before headers
    $beforeHeaders = substr($code, 0, strpos($code, 'header('));
    if (preg_match('/echo|print|var_dump|print_r/', $beforeHeaders)) {
        echo "❌ FAILED: Found output statements before header() calls\n";
        exit(1);
    }
    
    echo "✓ No early output before headers\n";
    
    // Check header calls in the file
    preg_match_all("/header\('([^']+)'\)/", $code, $matches);
    $expectedHeaders = [
        'Content-Type: text/event-stream',
        'Cache-Control: no-cache',
        'Connection: keep-alive',
        'X-Accel-Buffering: no'
    ];
    
    $foundHeaders = $matches[1] ?? [];
    
    echo "\nHeaders defined in SSE endpoint:\n";
    foreach ($foundHeaders as $header) {
        echo "  - {$header}\n";
    }
    
    echo "\nValidating required headers:\n";
    $allPresent = true;
    foreach ($expectedHeaders as $expected) {
        if (in_array($expected, $foundHeaders)) {
            echo "  ✓ {$expected}\n";
        } else {
            echo "  ❌ MISSING: {$expected}\n";
            $allPresent = false;
        }
    }
    
    // Check that bootstrap.php is NOT loaded (to avoid SecurityHeaders conflict)
    if (strpos($code, "require_once __DIR__ . '/../bootstrap.php'") !== false) {
        echo "\n❌ WARNING: SSE endpoint still loads bootstrap.php which calls SecurityHeaders::apply()\n";
        echo "   This will override the Content-Type header with application/json\n";
        $allPresent = false;
    } else {
        echo "\n✓ SSE endpoint does not load bootstrap.php (correct)\n";
    }
    
    // Check that it loads vendor/autoload.php and eloquent.php directly
    if (strpos($code, "vendor/autoload.php") !== false && strpos($code, "bootstrap/eloquent.php") !== false) {
        echo "✓ SSE endpoint loads dependencies directly (correct)\n";
    } else {
        echo "❌ WARNING: SSE endpoint may not be loading dependencies correctly\n";
    }
    
    if ($allPresent) {
        echo "\n✅ SUCCESS: All SSE headers are correctly configured\n";
        exit(0);
    } else {
        echo "\n❌ FAILED: Some headers are missing or incorrect\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    exit(1);
} finally {
    ob_end_clean();
}
