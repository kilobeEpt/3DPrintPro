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
            form_submission_id INTEGER NULL,
            form_slug VARCHAR(255) NULL,
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
    
    echo "Creating forms table...\n";
    $db->statement("
        CREATE TABLE IF NOT EXISTS forms (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            settings TEXT,
            notification_email VARCHAR(255),
            success_message TEXT,
            redirect_url VARCHAR(500),
            sort_order INTEGER DEFAULT 0,
            active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "Creating form_fields table...\n";
    $db->statement("
        CREATE TABLE IF NOT EXISTS form_fields (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            form_id INTEGER NOT NULL,
            name VARCHAR(255) NOT NULL,
            label VARCHAR(255) NOT NULL,
            type VARCHAR(50) DEFAULT 'text',
            placeholder VARCHAR(255),
            default_value TEXT,
            validation_rules TEXT,
            options TEXT,
            help_text VARCHAR(500),
            sort_order INTEGER DEFAULT 0,
            required INTEGER DEFAULT 0,
            active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
        )
    ");
    
    echo "Creating form_submissions table...\n";
    $db->statement("
        CREATE TABLE IF NOT EXISTS form_submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            form_id INTEGER NOT NULL,
            form_slug VARCHAR(255) NOT NULL,
            submitted_data TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            ip_address VARCHAR(45),
            user_agent TEXT,
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
        )
    ");
    
    echo "Creating form_submission_values table...\n";
    $db->statement("
        CREATE TABLE IF NOT EXISTS form_submission_values (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            form_submission_id INTEGER NOT NULL,
            form_field_id INTEGER NULL,
            field_name VARCHAR(255) NOT NULL,
            field_value TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (form_submission_id) REFERENCES form_submissions(id) ON DELETE CASCADE,
            FOREIGN KEY (form_field_id) REFERENCES form_fields(id) ON DELETE SET NULL
        )
    ");
    
    echo "Creating settings_audit table...\n";
    $db->statement("
        CREATE TABLE IF NOT EXISTS settings_audit (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key VARCHAR(100) NOT NULL,
            old_value TEXT,
            new_value TEXT,
            changed_by VARCHAR(255),
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "Creating admin_users table...\n";
    $db->statement("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'admin',
            status VARCHAR(20) DEFAULT 'active',
            last_login_at DATETIME NULL,
            last_login_ip VARCHAR(45) NULL,
            failed_login_attempts INTEGER DEFAULT 0,
            locked_until DATETIME NULL,
            remember_token VARCHAR(100) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "Creating admin_sessions table...\n";
    $db->statement("
        CREATE TABLE IF NOT EXISTS admin_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id VARCHAR(128) NOT NULL UNIQUE,
            user_id INTEGER NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            csrf_token VARCHAR(64) NULL,
            expires_at DATETIME NOT NULL,
            last_activity_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
        )
    ");
    
    echo "Creating admin_login_attempts table...\n";
    $db->statement("
        CREATE TABLE IF NOT EXISTS admin_login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            success INTEGER NOT NULL DEFAULT 0,
            failure_reason VARCHAR(255) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "Creating admin_action_logs table...\n";
    $db->statement("
        CREATE TABLE IF NOT EXISTS admin_action_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            action VARCHAR(100) NOT NULL,
            entity_type VARCHAR(100) NULL,
            entity_id INTEGER NULL,
            payload TEXT,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
        )
    ");
    
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
    echo "  Orders: " . $db->table('orders')->count() . "\n";
    echo "  Forms: " . $db->table('forms')->count() . "\n";
    echo "  Form Fields: " . $db->table('form_fields')->count() . "\n";
    echo "  Form Submissions: " . $db->table('form_submissions')->count() . "\n";
    echo "  Settings Audit: " . $db->table('settings_audit')->count() . "\n";
    echo "  Admin Users: " . $db->table('admin_users')->count() . "\n";
    echo "  Admin Sessions: " . $db->table('admin_sessions')->count() . "\n";
    echo "  Admin Login Attempts: " . $db->table('admin_login_attempts')->count() . "\n";
    echo "  Admin Action Logs: " . $db->table('admin_action_logs')->count() . "\n\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
