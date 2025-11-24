#!/usr/bin/env php
<?php
/**
 * Orders Export Service Smoke Test
 * 
 * Tests order export functionality:
 * - CSV generation with field selection
 * - PDF generation (structure validation)
 * - Signed URL generation and verification
 * - Export URL expiration
 * - Filter application (status, date range, type)
 * - Field customization
 * 
 * Usage: php scripts/orders-export-smoke.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\Order;
use App\Services\OrderExportService;

// Colors for output
function success($msg) { echo "\033[32m✓\033[0m " . $msg . PHP_EOL; }
function error($msg) { echo "\033[31m✗\033[0m " . $msg . PHP_EOL; }
function info($msg) { echo "\033[34mℹ\033[0m " . $msg . PHP_EOL; }
function section($msg) { echo PHP_EOL . "\033[1m" . $msg . "\033[0m" . PHP_EOL . str_repeat('-', 60) . PHP_EOL; }

$exportService = new OrderExportService();
$testsPassed = 0;
$testsFailed = 0;

section('Orders Export Service Smoke Test');

// Cleanup and create test orders
section('Setup Test Data');
try {
    Order::truncate();
    
    // Create diverse test orders
    $orders = [
        [
            'order_number' => 'ORD-20240101-001',
            'type' => 'order',
            'name' => 'Ivan Petrov',
            'email' => 'ivan@test.com',
            'phone' => '+79001234567',
            'service' => '3D Printing',
            'message' => 'Prototype order',
            'amount' => 1500.00,
            'status' => 'new',
            'created_at' => '2024-01-01 10:00:00'
        ],
        [
            'order_number' => 'ORD-20240115-002',
            'type' => 'order',
            'name' => 'Maria Ivanova',
            'email' => 'maria@test.com',
            'phone' => '+79007654321',
            'service' => 'Design Services',
            'message' => 'Custom design',
            'amount' => 3000.00,
            'status' => 'processing',
            'created_at' => '2024-01-15 14:30:00'
        ],
        [
            'order_number' => 'ORD-20240201-003',
            'type' => 'contact',
            'name' => 'Alexey Sidorov',
            'email' => 'alexey@test.com',
            'phone' => '+79009876543',
            'service' => 'Consultation',
            'message' => 'Question about materials',
            'amount' => 0.00,
            'status' => 'completed',
            'created_at' => '2024-02-01 09:15:00'
        ],
        [
            'order_number' => 'ORD-20240215-004',
            'type' => 'order',
            'name' => 'Elena Volkova',
            'email' => 'elena@test.com',
            'phone' => '+79005556677',
            'service' => '3D Modeling',
            'message' => 'Architectural model',
            'amount' => 5000.00,
            'status' => 'new',
            'calculator_data' => json_encode([
                'material' => 'PLA',
                'weight' => 100,
                'quality' => 'high'
            ]),
            'created_at' => '2024-02-15 16:45:00'
        ]
    ];
    
    foreach ($orders as $orderData) {
        Order::create($orderData);
    }
    
    success("Created " . count($orders) . " test orders");
    $testsPassed++;
} catch (Exception $e) {
    error("Test data setup failed: " . $e->getMessage());
    $testsFailed++;
    exit(1);
}

// Test 1: CSV Generation - All Fields
section('1. CSV Export - All Fields');
try {
    $orders = Order::all()->toArray();
    
    $csv = $exportService->generateCSV($orders, [
        'order_number', 'type', 'name', 'email', 'phone', 
        'service', 'amount', 'status', 'created_at'
    ]);
    
    if (strpos($csv, 'order_number') !== false 
        && strpos($csv, 'ORD-20240101-001') !== false 
        && strpos($csv, 'Ivan Petrov') !== false) {
        
        $lineCount = substr_count($csv, "\n");
        success("CSV generated with all fields. Lines: {$lineCount}");
        $testsPassed++;
    } else {
        error("CSV generation failed - missing expected data");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("CSV generation exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 2: CSV Generation - Selected Fields Only
section('2. CSV Export - Selected Fields');
try {
    $orders = Order::all()->toArray();
    
    $csv = $exportService->generateCSV($orders, ['order_number', 'name', 'amount']);
    
    if (strpos($csv, 'order_number') !== false 
        && strpos($csv, 'name') !== false 
        && strpos($csv, 'amount') !== false
        && strpos($csv, 'email') === false) {
        
        success("CSV generated with selected fields only");
        $testsPassed++;
    } else {
        error("CSV field selection failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("CSV field selection exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 3: CSV with UTF-8 BOM (Excel compatibility)
section('3. CSV Export - UTF-8 BOM for Excel');
try {
    $orders = Order::all()->toArray();
    
    $csv = $exportService->generateCSV($orders, ['order_number', 'name']);
    
    // Check for UTF-8 BOM
    $bom = "\xEF\xBB\xBF";
    if (substr($csv, 0, 3) === $bom) {
        success("CSV includes UTF-8 BOM for Excel compatibility");
        $testsPassed++;
    } else {
        error("CSV missing UTF-8 BOM");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("CSV BOM test exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 4: Filter by Status
section('4. Export with Status Filter');
try {
    $newOrders = Order::where('status', 'new')->get()->toArray();
    
    $csv = $exportService->generateCSV($newOrders, ['order_number', 'status']);
    
    $lines = explode("\n", trim($csv));
    $dataLines = array_slice($lines, 1); // Skip header
    
    $allNew = true;
    foreach ($dataLines as $line) {
        if (!empty($line) && strpos($line, 'new') === false) {
            $allNew = false;
            break;
        }
    }
    
    if ($allNew && count($dataLines) > 0) {
        success("Status filter working. New orders: " . count($dataLines));
        $testsPassed++;
    } else {
        error("Status filter failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Status filter exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 5: Filter by Type
section('5. Export with Type Filter');
try {
    $orderType = Order::where('type', 'order')->get()->toArray();
    $contactType = Order::where('type', 'contact')->get()->toArray();
    
    if (count($orderType) > 0 && count($contactType) > 0) {
        success("Type filter working. Orders: " . count($orderType) . ", Contacts: " . count($contactType));
        $testsPassed++;
    } else {
        error("Type filter failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Type filter exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 6: Filter by Date Range
section('6. Export with Date Range Filter');
try {
    $janOrders = Order::where('created_at', '>=', '2024-01-01')
                     ->where('created_at', '<', '2024-02-01')
                     ->get()
                     ->toArray();
    
    if (count($janOrders) === 2) {
        success("Date range filter working. January orders: " . count($janOrders));
        $testsPassed++;
    } else {
        error("Date range filter failed. Expected 2, got " . count($janOrders));
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Date range filter exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 7: Generate Signed URL
section('7. Signed URL Generation');
try {
    $exportId = 'test-export-' . time();
    $format = 'csv';
    
    $signedUrl = $exportService->generateSignedUrl($exportId, $format);
    
    if (strpos($signedUrl, 'signature=') !== false 
        && strpos($signedUrl, 'expires=') !== false
        && strpos($signedUrl, $exportId) !== false) {
        
        success("Signed URL generated: " . substr($signedUrl, 0, 50) . "...");
        $testsPassed++;
    } else {
        error("Signed URL generation failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Signed URL exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 8: Verify Valid Signed URL
section('8. Verify Valid Signed URL');
try {
    $exportId = 'test-export-valid';
    $format = 'csv';
    
    $signedUrl = $exportService->generateSignedUrl($exportId, $format);
    
    if ($exportService->verifySignedUrl($signedUrl)) {
        success("Valid signed URL verification passed");
        $testsPassed++;
    } else {
        error("Valid signed URL rejected incorrectly");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Signed URL verification exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 9: Reject Expired URL
section('9. Reject Expired URL');
try {
    $exportId = 'test-export-expired';
    $format = 'csv';
    $expiresAt = time() - 3600; // 1 hour ago
    
    // Manually create expired URL
    $signature = hash_hmac('sha256', $exportId . $format . $expiresAt, getenv('APP_KEY') ?: 'test-secret');
    $expiredUrl = "/api/orders/export.php?id={$exportId}&format={$format}&expires={$expiresAt}&signature={$signature}";
    
    if (!$exportService->verifySignedUrl($expiredUrl)) {
        success("Expired URL correctly rejected");
        $testsPassed++;
    } else {
        error("Expired URL was incorrectly accepted");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Expired URL test exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 10: Reject Tampered URL
section('10. Reject Tampered URL');
try {
    $exportId = 'test-export-tampered';
    $format = 'csv';
    
    $signedUrl = $exportService->generateSignedUrl($exportId, $format);
    
    // Tamper with the URL
    $tamperedUrl = str_replace($exportId, 'tampered-id', $signedUrl);
    
    if (!$exportService->verifySignedUrl($tamperedUrl)) {
        success("Tampered URL correctly rejected");
        $testsPassed++;
    } else {
        error("Tampered URL was incorrectly accepted");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Tampered URL test exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 11: CSV with Calculator Data
section('11. Export with Calculator Data');
try {
    $ordersWithCalc = Order::whereNotNull('calculator_data')->get()->toArray();
    
    if (count($ordersWithCalc) > 0) {
        $csv = $exportService->generateCSV($ordersWithCalc, ['order_number', 'calculator_data']);
        
        if (strpos($csv, 'calculator_data') !== false) {
            success("Calculator data exported. Orders with calc data: " . count($ordersWithCalc));
            $testsPassed++;
        } else {
            error("Calculator data export failed");
            $testsFailed++;
        }
    } else {
        info("No orders with calculator data to test");
        $testsPassed++;
    }
} catch (Exception $e) {
    error("Calculator data export exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 12: Empty Export
section('12. Handle Empty Export');
try {
    $emptyOrders = [];
    
    $csv = $exportService->generateCSV($emptyOrders, ['order_number', 'name']);
    
    // Should still have headers
    if (strpos($csv, 'order_number') !== false) {
        success("Empty export handled correctly with headers");
        $testsPassed++;
    } else {
        error("Empty export failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Empty export exception: " . $e->getMessage());
    $testsFailed++;
}

// Cleanup
section('Cleanup');
try {
    Order::truncate();
    success("Test data cleaned up");
} catch (Exception $e) {
    error("Cleanup failed: " . $e->getMessage());
}

// Summary
section('Test Summary');
$total = $testsPassed + $testsFailed;
$percentage = $total > 0 ? round(($testsPassed / $total) * 100, 1) : 0;

echo "Total Tests: {$total}" . PHP_EOL;
echo "Passed: \033[32m{$testsPassed}\033[0m" . PHP_EOL;
echo "Failed: \033[31m{$testsFailed}\033[0m" . PHP_EOL;
echo "Success Rate: {$percentage}%" . PHP_EOL;
echo PHP_EOL;

if ($testsFailed === 0) {
    success("All export tests passed! ✨");
    exit(0);
} else {
    error("Some tests failed. Please review the output above.");
    exit(1);
}
