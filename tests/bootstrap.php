<?php
/**
 * PHPUnit Bootstrap
 * 
 * Initializes the test environment with:
 * - Composer autoloader
 * - Eloquent ORM with SQLite in-memory database
 * - Database schema creation
 * - Test data seeding
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

// Create Capsule instance
$capsule = new Capsule;

// Add connection using SQLite in-memory database
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
]);

// Set up the event dispatcher
$capsule->setEventDispatcher(new Dispatcher(new Container));

// Make this Capsule instance available globally
$capsule->setAsGlobal();

// Boot Eloquent
$capsule->bootEloquent();

// Create database schema
createTestSchema();

/**
 * Create test database schema
 */
function createTestSchema()
{
    $db = Capsule::connection()->getPdo();
    
    // Settings table
    $db->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key VARCHAR(255) NOT NULL UNIQUE,
            setting_value TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Settings audit table
    $db->exec("
        CREATE TABLE IF NOT EXISTS settings_audit (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key VARCHAR(255) NOT NULL,
            old_value TEXT,
            new_value TEXT,
            changed_by VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Forms table
    $db->exec("
        CREATE TABLE IF NOT EXISTS forms (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            settings TEXT,
            notification_email VARCHAR(255),
            success_message TEXT,
            redirect_url VARCHAR(255),
            sort_order INTEGER DEFAULT 0,
            active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Form fields table
    $db->exec("
        CREATE TABLE IF NOT EXISTS form_fields (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            form_id INTEGER NOT NULL,
            name VARCHAR(255) NOT NULL,
            label VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL,
            placeholder VARCHAR(255),
            default_value TEXT,
            validation_rules TEXT,
            options TEXT,
            help_text TEXT,
            sort_order INTEGER DEFAULT 0,
            required BOOLEAN DEFAULT 0,
            active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (form_id) REFERENCES forms (id) ON DELETE CASCADE
        )
    ");
    
    // Form submissions table
    $db->exec("
        CREATE TABLE IF NOT EXISTS form_submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            form_id INTEGER NOT NULL,
            submitted_data TEXT,
            ip_address VARCHAR(45),
            user_agent VARCHAR(255),
            status VARCHAR(50) DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (form_id) REFERENCES forms (id) ON DELETE CASCADE
        )
    ");
    
    // Form submission values table
    $db->exec("
        CREATE TABLE IF NOT EXISTS form_submission_values (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            form_submission_id INTEGER NOT NULL,
            form_field_id INTEGER,
            field_name VARCHAR(255),
            field_value TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (form_submission_id) REFERENCES form_submissions (id) ON DELETE CASCADE,
            FOREIGN KEY (form_field_id) REFERENCES form_fields (id) ON DELETE CASCADE
        )
    ");
    
    // Orders table (for integration tests)
    $db->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            form_submission_id INTEGER,
            form_slug VARCHAR(255),
            customer_name VARCHAR(255) NOT NULL,
            customer_email VARCHAR(255) NOT NULL,
            customer_phone VARCHAR(50),
            customer_district VARCHAR(255),
            message TEXT,
            calculator_data TEXT,
            status VARCHAR(50) DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (form_submission_id) REFERENCES form_submissions (id) ON DELETE SET NULL
        )
    ");
    
    // Admin users table
    $db->exec("
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
    
    // Admin sessions table
    $db->exec("
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
    
    // Admin login attempts table
    $db->exec("
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
    
    // Admin action logs table
    $db->exec("
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
    
    // Create indexes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_settings_key ON settings (setting_key)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_settings_audit_key ON settings_audit (setting_key)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_forms_slug ON forms (slug)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_form_fields_form_id ON form_fields (form_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_form_submissions_form_id ON form_submissions (form_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_form_submission_values_submission ON form_submission_values (form_submission_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_orders_status ON orders (status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_admin_users_email ON admin_users (email)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_admin_sessions_session_id ON admin_sessions (session_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_admin_login_attempts_email ON admin_login_attempts (email)");
}

/**
 * Seed test data
 * 
 * @param array $data Array of table => rows
 */
function seedTestData(array $data)
{
    foreach ($data as $table => $rows) {
        foreach ($rows as $row) {
            Capsule::table($table)->insert($row);
        }
    }
}

/**
 * Clean all test data
 */
function cleanTestData()
{
    $tables = [
        'form_submission_values',
        'form_submissions',
        'form_fields',
        'forms',
        'settings_audit',
        'settings',
        'orders',
        'admin_action_logs',
        'admin_sessions',
        'admin_login_attempts',
        'admin_users',
    ];
    
    foreach ($tables as $table) {
        Capsule::table($table)->truncate();
    }
}
