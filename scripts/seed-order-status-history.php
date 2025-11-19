<?php
/**
 * Seed Order Status History
 * 
 * Backfills status history for existing orders.
 * Safe to run multiple times (checks for existing history).
 * 
 * Usage: php scripts/seed-order-status-history.php [--dry-run]
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$dryRun = in_array('--dry-run', $argv);

echo "=================================\n";
echo "Seed Order Status History\n";
echo $dryRun ? "(DRY RUN MODE)\n" : "";
echo "=================================\n\n";

$db = Capsule::connection()->getPdo();

try {
    // Get all orders without status history
    $orders = $db->query("
        SELECT o.id, o.order_number, o.status, o.created_at
        FROM orders o
        LEFT JOIN order_status_history h ON o.id = h.order_id
        WHERE h.id IS NULL
        ORDER BY o.created_at ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $count = count($orders);
    echo "Found {$count} orders without status history\n\n";
    
    if ($count === 0) {
        echo "✓ No orders to process\n";
        exit(0);
    }
    
    if ($dryRun) {
        echo "Would create initial status history for:\n";
        foreach (array_slice($orders, 0, 5) as $order) {
            echo "  - Order #{$order['order_number']} (status: {$order['status']})\n";
        }
        if ($count > 5) {
            echo "  ... and " . ($count - 5) . " more\n";
        }
        echo "\nRun without --dry-run to apply changes\n";
        exit(0);
    }
    
    $stmt = $db->prepare("
        INSERT INTO order_status_history 
        (order_id, old_status, new_status, comment, created_at)
        VALUES (?, NULL, ?, ?, ?)
    ");
    
    $processed = 0;
    foreach ($orders as $order) {
        $stmt->execute([
            $order['id'],
            $order['status'],
            'Initial status on order creation',
            $order['created_at']
        ]);
        $processed++;
        
        if ($processed % 100 === 0) {
            echo "Processed {$processed}/{$count} orders...\n";
        }
    }
    
    echo "\n=================================\n";
    echo "✓ Created initial status history for {$processed} orders\n";
    echo "=================================\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
