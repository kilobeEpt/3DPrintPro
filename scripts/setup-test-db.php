#!/usr/bin/env php
<?php
/**
 * Setup Test Database
 * 
 * Creates a SQLite database with the basic schema for testing Eloquent.
 * This is a simplified version for smoke testing only.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "Setting up test database...\n";

try {
    $dbPath = __DIR__ . '/../database/test.sqlite';
    
    // Create the database file if it doesn't exist
    if (!file_exists($dbPath)) {
        touch($dbPath);
    }
    
    // Get connection
    $db = Capsule::connection();
    
    // Create tables
    echo "Creating services table...\n";
    $db->statement("
        CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            icon VARCHAR(255),
            description TEXT,
            features TEXT,
            price VARCHAR(100),
            category VARCHAR(100),
            sort_order INTEGER DEFAULT 0,
            active INTEGER DEFAULT 1,
            featured INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "Creating orders table...\n";
    $db->statement("
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_number VARCHAR(50) NOT NULL UNIQUE,
            type VARCHAR(20) DEFAULT 'contact',
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255),
            phone VARCHAR(20) NOT NULL,
            telegram VARCHAR(100),
            service VARCHAR(255),
            subject VARCHAR(255),
            message TEXT,
            amount DECIMAL(10, 2) DEFAULT 0,
            calculator_data TEXT,
            status VARCHAR(20) DEFAULT 'new',
            telegram_sent INTEGER DEFAULT 0,
            telegram_error TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "Creating settings table...\n";
    $db->statement("
        CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert sample data
    echo "Inserting sample data...\n";
    
    // Check if data already exists
    $serviceCount = $db->table('services')->count();
    if ($serviceCount === 0) {
        $db->table('services')->insert([
            [
                'name' => '3D Печать',
                'slug' => '3d-printing',
                'icon' => 'printer',
                'description' => 'Высококачественная 3D печать',
                'features' => json_encode(['Быстро', 'Качественно', 'Недорого']),
                'price' => 'от 100₽',
                'category' => 'printing',
                'sort_order' => 1,
                'active' => 1,
                'featured' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => '3D Моделирование',
                'slug' => '3d-modeling',
                'icon' => 'cube',
                'description' => 'Создание 3D моделей',
                'features' => json_encode(['Точность', 'Детализация']),
                'price' => 'от 500₽',
                'category' => 'modeling',
                'sort_order' => 2,
                'active' => 1,
                'featured' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
    
    $settingCount = $db->table('settings')->count();
    if ($settingCount === 0) {
        $db->table('settings')->insert([
            'setting_key' => 'telegram_chat_id',
            'setting_value' => '',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
    
    echo "\n✓ Test database setup complete!\n";
    echo "  Database: {$dbPath}\n";
    echo "  Services: " . $db->table('services')->count() . "\n";
    echo "  Settings: " . $db->table('settings')->count() . "\n";
    echo "  Orders: " . $db->table('orders')->count() . "\n\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
