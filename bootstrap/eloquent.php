<?php
/**
 * Eloquent ORM Bootstrap
 * 
 * This file initializes the Eloquent ORM database connection using Laravel's
 * Capsule manager. It reads configuration from .env file (if available) or
 * falls back to the legacy config.php constants.
 * 
 * Usage:
 *   require_once __DIR__ . '/../vendor/autoload.php';
 *   // Eloquent is now ready to use via Capsule or model classes
 */

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

// Only initialize if not already done
if (!class_exists('Capsule') || !Capsule::connection()) {
    try {
        // Load environment variables if .env exists
        $dotenvPath = __DIR__ . '/..';
        $envLoaded = false;
        if (file_exists($dotenvPath . '/.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable($dotenvPath);
            $dotenv->load();
            $envLoaded = true;
        }
        
        // Fallback to legacy config if available and env vars not set
        $configPath = __DIR__ . '/../api/config.php';
        if (file_exists($configPath) && !$envLoaded && !getenv('DB_CONNECTION')) {
            require_once $configPath;
        }
        
        // Helper to get env variable (checks $_ENV, $_SERVER, and getenv())
        $env = function($key, $default = null) {
            return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
        };
        
        // Get database configuration from env or legacy constants
        $driver = $env('DB_CONNECTION', 'mysql');
        
        $dbConfig = [
            'driver'    => $driver,
            'charset'   => $env('DB_CHARSET', defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4'),
            'collation' => $env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix'    => $env('DB_PREFIX', ''),
            'strict'    => true,
            'engine'    => null,
        ];
        
        // SQLite specific configuration
        if ($driver === 'sqlite') {
            $dbConfig['database'] = $env('DB_DATABASE', ':memory:');
        } else {
            // MySQL/MariaDB configuration
            $dbConfig['host'] = $env('DB_HOST', defined('DB_HOST') ? DB_HOST : 'localhost');
            $dbConfig['port'] = $env('DB_PORT', 3306);
            $dbConfig['database'] = $env('DB_DATABASE', defined('DB_NAME') ? DB_NAME : '');
            $dbConfig['username'] = $env('DB_USERNAME', defined('DB_USER') ? DB_USER : '');
            $dbConfig['password'] = $env('DB_PASSWORD', defined('DB_PASS') ? DB_PASS : '');
        }
        
        // Initialize Capsule manager
        $capsule = new Capsule;
        $capsule->addConnection($dbConfig);
        
        // Set up event dispatcher for model events (observers, etc.)
        $capsule->setEventDispatcher(new Dispatcher(new Container));
        
        // Make Capsule available globally
        $capsule->setAsGlobal();
        
        // Boot Eloquent
        $capsule->bootEloquent();
        
    } catch (\Exception $e) {
        // In production, log the error. In development, show it.
        $isDebug = ($_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? getenv('APP_DEBUG')) === 'true' 
                   || (defined('APP_DEBUG') && APP_DEBUG);
        
        if ($isDebug) {
            throw $e;
        } else {
            error_log('Eloquent Bootstrap Error: ' . $e->getMessage());
            // Don't halt execution - allow legacy code to continue
        }
    }
}

/**
 * Helper function to get the Capsule instance
 * 
 * @return \Illuminate\Database\Capsule\Manager
 */
function eloquent_capsule() {
    return Capsule::getInstance();
}

/**
 * Helper function to get the database connection
 * 
 * @param string|null $connection
 * @return \Illuminate\Database\Connection
 */
function eloquent_connection($connection = null) {
    return Capsule::connection($connection);
}

/**
 * Helper function for quick database queries
 * 
 * @param string $table
 * @return \Illuminate\Database\Query\Builder
 */
function eloquent_table($table) {
    return Capsule::table($table);
}
