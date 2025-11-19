<?php
/**
 * Orders Domain Migration Script
 * 
 * Adds order_status_history and order_notes tables, plus archiving support.
 * Safe to run multiple times (idempotent).
 * 
 * Usage: php scripts/migrate-orders-domain.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "=================================\n";
echo "Orders Domain Migration\n";
echo "=================================\n\n";

$db = Capsule::connection()->getPdo();

try {
    // Create order_status_history table
    echo "Creating order_status_history table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS order_status_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            old_status VARCHAR(50) NULL COMMENT 'Previous status (NULL for initial status)',
            new_status VARCHAR(50) NOT NULL COMMENT 'New status',
            changed_by INT NULL COMMENT 'Admin user ID who made the change',
            comment TEXT NULL COMMENT 'Optional comment about the status change',
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            INDEX idx_order_id (order_id),
            INDEX idx_new_status (new_status),
            INDEX idx_changed_by (changed_by),
            INDEX idx_created_at (created_at),
            
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (changed_by) REFERENCES admin_users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ order_status_history table created\n\n";
    
    // Create order_notes table
    echo "Creating order_notes table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS order_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            note TEXT NOT NULL,
            created_by INT NULL COMMENT 'Admin user ID who created the note',
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_order_id (order_id),
            INDEX idx_created_by (created_by),
            INDEX idx_created_at (created_at),
            
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ order_notes table created\n\n";
    
    // Add archived_at column to orders table for soft archiving
    echo "Adding archived_at column to orders table...\n";
    try {
        $db->exec("
            ALTER TABLE orders 
            ADD COLUMN archived_at TIMESTAMP NULL COMMENT 'When order was archived' AFTER status
        ");
        echo "✓ archived_at column added\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "⚠ archived_at column already exists\n\n";
        } else {
            throw $e;
        }
    }
    
    // Add index on archived_at
    echo "Adding index on archived_at...\n";
    try {
        $db->exec("ALTER TABLE orders ADD INDEX idx_archived_at (archived_at)");
        echo "✓ Index added\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "⚠ Index already exists\n\n";
        } else {
            throw $e;
        }
    }
    
    echo "=================================\n";
    echo "✓ Migration completed successfully!\n";
    echo "=================================\n\n";
    
    echo "Next steps:\n";
    echo "1. Run seed script: php scripts/seed-order-status-history.php\n";
    echo "2. Update schema.sql with these changes\n";
    echo "3. Test the new features\n\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
