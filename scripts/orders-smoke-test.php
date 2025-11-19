<?php
/**
 * Orders Domain Smoke Test
 * 
 * Tests the orders API, status history, notes, filtering, and exports.
 * 
 * Usage: php scripts/orders-smoke-test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\OrderNote;
use App\Models\AdminUser;
use App\Services\OrderExportService;

echo "=================================\n";
echo "Orders Domain Smoke Test\n";
echo "=================================\n\n";

$passed = 0;
$failed = 0;

function test($description, $callback) {
    global $passed, $failed;
    
    try {
        $result = $callback();
        if ($result) {
            echo "✓ {$description}\n";
            $passed++;
        } else {
            echo "✗ {$description}\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "✗ {$description}: {$e->getMessage()}\n";
        $failed++;
    }
}

echo "1. Model Tests\n";
echo "---------------------------------\n";

test("Order model loads", function() {
    return class_exists('App\Models\Order');
});

test("OrderStatusHistory model loads", function() {
    return class_exists('App\Models\OrderStatusHistory');
});

test("OrderNote model loads", function() {
    return class_exists('App\Models\OrderNote');
});

echo "\n2. Database Structure Tests\n";
echo "---------------------------------\n";

test("orders table has archived_at column", function() {
    $columns = \Illuminate\Database\Capsule\Manager::schema()->getColumnListing('orders');
    return in_array('archived_at', $columns);
});

test("order_status_history table exists", function() {
    return \Illuminate\Database\Capsule\Manager::schema()->hasTable('order_status_history');
});

test("order_notes table exists", function() {
    return \Illuminate\Database\Capsule\Manager::schema()->hasTable('order_notes');
});

echo "\n3. Order Creation and Relationships\n";
echo "---------------------------------\n";

$testOrder = null;

test("Create test order", function() use (&$testOrder) {
    $testOrder = Order::create([
        'order_number' => 'SMOKE-' . time(),
        'type' => 'order',
        'name' => 'Smoke Test Customer',
        'phone' => '+79991234567',
        'email' => 'smoke@test.com',
        'status' => 'new',
        'amount' => 1000.00,
    ]);
    return $testOrder && $testOrder->id > 0;
});

test("Log initial status history", function() use ($testOrder) {
    if (!$testOrder) return false;
    
    $history = OrderStatusHistory::logStatusChange(
        $testOrder->id,
        null,
        'new',
        null,
        'Order created'
    );
    
    return $history && $history->id > 0;
});

test("Order has status history relationship", function() use ($testOrder) {
    if (!$testOrder) return false;
    
    $order = Order::with('statusHistory')->find($testOrder->id);
    return $order->statusHistory->count() > 0;
});

test("Add note to order", function() use ($testOrder) {
    if (!$testOrder) return false;
    
    $note = OrderNote::addNote($testOrder->id, 'Test note from smoke test');
    return $note && $note->id > 0;
});

test("Order has notes relationship", function() use ($testOrder) {
    if (!$testOrder) return false;
    
    $order = Order::with('notes')->find($testOrder->id);
    return $order->notes->count() > 0;
});

echo "\n4. Order Scopes and Filtering\n";
echo "---------------------------------\n";

test("Active scope works", function() use ($testOrder) {
    if (!$testOrder) return false;
    
    $activeOrders = Order::active()->where('id', $testOrder->id)->count();
    return $activeOrders === 1;
});

test("Archive order", function() use ($testOrder) {
    if (!$testOrder) return false;
    
    $testOrder->archive();
    return $testOrder->isArchived();
});

test("Archived scope works", function() use ($testOrder) {
    if (!$testOrder) return false;
    
    $archivedOrders = Order::archived()->where('id', $testOrder->id)->count();
    return $archivedOrders === 1;
});

test("Unarchive order", function() use ($testOrder) {
    if (!$testOrder) return false;
    
    $testOrder->unarchive();
    return !$testOrder->isArchived();
});

test("Search scope works", function() use ($testOrder) {
    if (!$testOrder) return false;
    
    $results = Order::search('Smoke Test Customer')->where('id', $testOrder->id)->count();
    return $results === 1;
});

test("Status scope works", function() use ($testOrder) {
    if (!$testOrder) return false;
    
    $results = Order::status('new')->where('id', $testOrder->id)->count();
    return $results === 1;
});

echo "\n5. Status Changes and History\n";
echo "---------------------------------\n";

test("Change order status", function() use ($testOrder) {
    if (!$testOrder) return false;
    
    $oldStatus = $testOrder->status;
    $testOrder->status = 'processing';
    $testOrder->save();
    
    OrderStatusHistory::logStatusChange(
        $testOrder->id,
        $oldStatus,
        'processing',
        null,
        'Status changed by smoke test'
    );
    
    return $testOrder->status === 'processing';
});

test("Status history is tracked", function() use ($testOrder) {
    if (!$testOrder) return false;
    
    $order = Order::with('statusHistory')->find($testOrder->id);
    return $order->statusHistory->count() >= 2;
});

echo "\n6. Export Service\n";
echo "---------------------------------\n";

test("OrderExportService loads", function() {
    return class_exists('App\Services\OrderExportService');
});

test("Generate CSV export", function() {
    $exportService = new OrderExportService();
    $csv = $exportService->exportCsv(['limit' => 5]);
    return !empty($csv) && strpos($csv, 'Order Number') !== false;
});

test("Generate signed URL", function() {
    $exportService = new OrderExportService();
    $signedUrl = $exportService->generateSignedUrl('csv', [], null, 60);
    return isset($signedUrl['url']) && isset($signedUrl['expires_at']);
});

test("Validate signed URL", function() {
    $exportService = new OrderExportService();
    $signedUrl = $exportService->generateSignedUrl('csv', [], null, 60);
    
    $urlParts = parse_url($signedUrl['url']);
    parse_str($urlParts['query'], $params);
    
    $validation = $exportService->validateSignedUrl($params['token'], $params['sig']);
    return $validation['valid'] === true;
});

echo "\n7. Cleanup\n";
echo "---------------------------------\n";

test("Delete test order", function() use ($testOrder) {
    if (!$testOrder) return true;
    
    $testOrder->delete();
    
    $deleted = Order::find($testOrder->id);
    return $deleted === null;
});

echo "\n=================================\n";
echo "Results: {$passed} passed, {$failed} failed\n";
echo "=================================\n";

exit($failed > 0 ? 1 : 0);
