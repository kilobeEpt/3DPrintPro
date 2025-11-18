# Eloquent ORM Setup Documentation

## Overview

This document describes the Eloquent ORM integration for the 3D Print Pro platform. Eloquent provides a modern, elegant ActiveRecord implementation for working with your database, offering powerful features like model relationships, query scopes, JSON casting, and event observers.

## What Was Added

### 1. Composer Dependencies

The following packages were added via Composer:

- `illuminate/database` ^8.83 - Eloquent ORM and Query Builder
- `illuminate/events` ^8.83 - Event dispatcher for model events
- `illuminate/support` ^8.83 - Helper functions and collections
- `illuminate/cache` ^8.83 - Caching infrastructure (for future use)
- `vlucas/phpdotenv` ^5.4 - Environment variable management

### 2. Directory Structure

```
project/
├── app/
│   ├── Models/
│   │   ├── BaseModel.php      # Base model with common functionality
│   │   ├── Service.php         # Service model
│   │   ├── Order.php           # Order model
│   │   └── Setting.php         # Setting model
│   └── Services/               # For future service classes
├── bootstrap/
│   └── eloquent.php            # Eloquent initialization and configuration
├── scripts/
│   ├── eloquent-smoke.php      # Smoke test script
│   └── setup-test-db.php       # Test database setup script
├── .env.example                # Environment configuration template
├── .env                        # Your environment configuration (gitignored)
└── composer.json               # PHP dependencies
```

### 3. Configuration Files

#### `.env.example` / `.env`

Environment-based configuration for database credentials and application settings. Copy `.env.example` to `.env` and update with your actual credentials.

**For Production (MySQL):**
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ch167436_3dprint
DB_USERNAME=ch167436_3dprint
DB_PASSWORD=your_actual_password
```

**For Development/Testing (SQLite):**
```env
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database/test.sqlite
```

## Installation & Setup

### Step 1: Install Composer Dependencies

From the project root:

```bash
composer install
```

This will download all required packages into the `vendor/` directory.

### Step 2: Configure Environment

1. Copy the example environment file:
```bash
cp .env.example .env
```

2. Edit `.env` with your database credentials:
```bash
nano .env  # or use your preferred editor
```

3. Update the database configuration section with your actual credentials.

### Step 3: Verify Installation

Run the smoke test to verify everything is working:

```bash
php scripts/eloquent-smoke.php
```

You should see all tests pass with green checkmarks.

## Usage

### Using Eloquent in Your PHP Files

To use Eloquent in any PHP file, include both the Composer autoloader and the Eloquent bootstrap:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/eloquent.php';

use App\Models\Service;
use App\Models\Order;

// Now you can use Eloquent models
$services = Service::active()->get();
$order = Order::find(1);
```

### Model Examples

#### Querying Services

```php
use App\Models\Service;

// Get all active services
$services = Service::active()->get();

// Get featured services, ordered by sort_order
$featured = Service::active()->featured()->ordered()->get();

// Find a service by ID
$service = Service::find(1);

// Get services by category
$printing = Service::where('category', 'printing')->get();

// Count active services
$count = Service::active()->count();
```

#### Working with Orders

```php
use App\Models\Order;

// Get recent orders
$recentOrders = Order::orderBy('created_at', 'desc')->limit(10)->get();

// Get orders by status
$newOrders = Order::status(Order::STATUS_NEW)->get();

// Get orders by type
$contactForms = Order::type(Order::TYPE_CONTACT)->get();

// Find orders pending Telegram notification
$pending = Order::pendingTelegram()->get();

// Create a new order
$order = Order::create([
    'order_number' => 'ORD-' . time(),
    'type' => Order::TYPE_ORDER,
    'name' => 'John Doe',
    'phone' => '+1234567890',
    'amount' => 150.00,
    'status' => Order::STATUS_NEW,
]);
```

#### Settings Management

```php
use App\Models\Setting;

// Get a setting
$chatId = Setting::get('telegram_chat_id');
$chatId = Setting::get('telegram_chat_id', 'default_value');

// Set a setting
Setting::set('telegram_chat_id', '123456789');

// Set a complex setting (auto-JSON encodes)
Setting::set('site_config', [
    'maintenance_mode' => false,
    'max_upload_size' => 10
]);

// Get all settings
$allSettings = Setting::getAll();
```

### Raw Query Builder

For complex queries, you can use the query builder directly:

```php
use Illuminate\Database\Capsule\Manager as Capsule;

// Raw query
$results = Capsule::select('SELECT * FROM services WHERE active = ?', [1]);

// Query builder
$services = Capsule::table('services')
    ->where('active', 1)
    ->orderBy('sort_order')
    ->get();

// Helper function (from bootstrap)
$services = eloquent_table('services')->where('active', 1)->get();
```

## Model Features

### BaseModel

All models extend `BaseModel` which provides:

- **Automatic timestamps**: `created_at` and `updated_at` are managed automatically
- **Active scope**: `->active()` filters for active records
- **Ordered scope**: `->ordered()` sorts by `sort_order`

### JSON Casting

Models automatically cast JSON fields to arrays:

```php
$service = Service::find(1);

// features is stored as JSON in database, but accessed as array
$features = $service->features;  // array
foreach ($features as $feature) {
    echo $feature . "\n";
}

// Update JSON field
$service->features = ['New Feature 1', 'New Feature 2'];
$service->save();
```

### Query Scopes

Models include useful query scopes:

```php
// Service scopes
Service::active()           // Only active services
Service::featured()         // Only featured services  
Service::category('name')   // Services in a category
Service::ordered()          // Ordered by sort_order

// Order scopes
Order::status('new')        // Orders with specific status
Order::type('order')        // Orders of specific type
Order::pendingTelegram()    // Orders pending Telegram notification
```

## Coexistence with Legacy Code

The Eloquent setup is designed to work **alongside** your existing Database class without conflicts:

1. **Both can coexist**: You can use Eloquent in new code while legacy code continues using the Database class
2. **No automatic loading**: Eloquent is not auto-loaded via Composer; you must explicitly require the bootstrap
3. **Fallback configuration**: If no `.env` exists, Eloquent will use the legacy `api/config.php` constants
4. **Independent connections**: Each system maintains its own PDO connection

### Migration Strategy

You can gradually migrate to Eloquent:

1. **Phase 1**: Use Eloquent in new features/endpoints
2. **Phase 2**: Refactor high-traffic endpoints one at a time
3. **Phase 3**: Eventually deprecate the Database class

## Bootstrap Details

The `bootstrap/eloquent.php` file:

1. Loads environment variables from `.env` (if exists)
2. Falls back to legacy config constants if no `.env`
3. Configures database connection (MySQL or SQLite)
4. Sets up event dispatcher for model events
5. Makes Capsule available globally
6. Boots Eloquent ORM

### Configuration Priority

1. `.env` file (highest priority)
2. Legacy `api/config.php` constants
3. Default values (fallback)

## Helper Functions

The bootstrap provides helper functions:

```php
// Get the Capsule instance
$capsule = eloquent_capsule();

// Get database connection
$connection = eloquent_connection();

// Quick table query
$results = eloquent_table('services')->where('active', 1)->get();
```

## Testing & Verification

### Smoke Test

The `scripts/eloquent-smoke.php` script runs comprehensive tests:

```bash
php scripts/eloquent-smoke.php
```

Tests include:
- Database connection
- Model queries
- Query scopes
- JSON casting
- Helper functions
- Legacy code compatibility

### Test Database Setup

For testing with SQLite:

```bash
# Update .env to use SQLite
DB_CONNECTION=sqlite
DB_DATABASE=/home/engine/project/database/test.sqlite

# Setup test database
php scripts/setup-test-db.php

# Run smoke test
php scripts/eloquent-smoke.php
```

## Performance Considerations

1. **Query optimization**: Use `select()` to fetch only needed columns
2. **Eager loading**: Use `with()` to avoid N+1 queries (when you add relationships)
3. **Chunking**: Use `chunk()` for large datasets
4. **Caching**: Implement query caching for frequently accessed data

Example:

```php
// Good: Select specific columns
$services = Service::select('id', 'name', 'price')->active()->get();

// Good: Process large datasets in chunks
Service::chunk(100, function($services) {
    foreach ($services as $service) {
        // Process service
    }
});
```

## Troubleshooting

### "Class not found" errors

Make sure you've required the autoloader and bootstrap:
```php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/eloquent.php';
```

### Database connection errors

1. Check your `.env` file exists and has correct credentials
2. Verify database server is running
3. Check firewall/network access
4. Look at error logs in `logs/` directory

### "Could not find driver" error

Install the required PHP PDO extension:

```bash
# For MySQL
sudo apt-get install php-mysql

# For SQLite
sudo apt-get install php-sqlite3
```

### Legacy Database class conflicts

If you get PDO connection errors when both systems are used:
- Each system creates its own connection
- Make sure `api/config.php` has correct MySQL credentials
- Use `.env` for Eloquent configuration

## Next Steps

1. **Add more models**: Create models for `portfolio`, `testimonials`, `faq`, `content_blocks`
2. **Add relationships**: Define relationships between models (e.g., Order belongsTo Service)
3. **Add observers**: Use model observers for events (e.g., send Telegram on Order created)
4. **Add migrations**: Create migration files for schema changes
5. **Add seeders**: Create seed files for test data

## Resources

- [Laravel Eloquent Documentation](https://laravel.com/docs/8.x/eloquent)
- [Laravel Query Builder](https://laravel.com/docs/8.x/queries)
- [Illuminate/Database on GitHub](https://github.com/illuminate/database)

## Support

For issues or questions about the Eloquent integration, refer to:
- This documentation
- Smoke test results: `php scripts/eloquent-smoke.php`
- Laravel Eloquent documentation (version 8.x)
