# Eloquent ORM Quick Reference

A cheat sheet for common Eloquent operations in the 3D Print Pro project.

## Setup (in any PHP file)

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/eloquent.php';

use App\Models\Service;
use App\Models\Order;
use App\Models\Setting;
```

## Basic Queries

```php
// Get all records
$services = Service::all();

// Get with conditions
$services = Service::where('active', true)->get();

// Get single record by ID
$service = Service::find(1);
$service = Service::findOrFail(1);  // Throws exception if not found

// Get first matching record
$service = Service::where('slug', 'printing')->first();

// Count records
$count = Service::count();
$activeCount = Service::where('active', true)->count();
```

## Using Query Scopes

```php
// Models have built-in scopes

// Service scopes
$active = Service::active()->get();
$featured = Service::featured()->get();
$printing = Service::category('printing')->get();
$ordered = Service::ordered()->get();

// Chain multiple scopes
$services = Service::active()->featured()->ordered()->get();

// Order scopes
$newOrders = Order::status('new')->get();
$contacts = Order::type('contact')->get();
$pending = Order::pendingTelegram()->get();

// Testimonial scopes
$approved = Testimonial::approved()->get();
$highRated = Testimonial::minRating(4)->get();
```

## Creating Records

```php
// Create and save
$service = new Service;
$service->name = '3D Printing';
$service->slug = '3d-printing';
$service->active = true;
$service->save();

// Create with mass assignment
$service = Service::create([
    'name' => '3D Printing',
    'slug' => '3d-printing',
    'active' => true
]);

// First or create
$service = Service::firstOrCreate(
    ['slug' => '3d-printing'],  // Search criteria
    ['name' => '3D Printing']   // Additional attributes if creating
);
```

## Updating Records

```php
// Find and update
$service = Service::find(1);
$service->name = 'Updated Name';
$service->save();

// Mass update
Service::where('category', 'old')->update(['category' => 'new']);

// Update or create
Service::updateOrCreate(
    ['slug' => '3d-printing'],      // Search criteria
    ['name' => 'Updated Name']      // Values to update/create
);
```

## Deleting Records

```php
// Find and delete
$service = Service::find(1);
$service->delete();

// Delete by ID
Service::destroy(1);
Service::destroy([1, 2, 3]);  // Delete multiple

// Delete with conditions
Service::where('active', false)->delete();
```

## Working with JSON Fields

```php
// JSON fields are automatically cast to arrays
$service = Service::find(1);

// Get JSON field as array
$features = $service->features;  // Already an array

// Update JSON field
$service->features = ['Feature 1', 'Feature 2', 'Feature 3'];
$service->save();

// Query JSON field (MySQL 5.7+)
$services = Service::whereJsonContains('features', 'Fast')->get();
```

## Ordering Results

```php
// Order by column
$services = Service::orderBy('name')->get();
$services = Service::orderBy('created_at', 'desc')->get();

// Order by multiple columns
$services = Service::orderBy('category')
                   ->orderBy('name')
                   ->get();

// Using scope
$services = Service::ordered()->get();  // Orders by sort_order
```

## Limiting & Pagination

```php
// Limit results
$services = Service::limit(10)->get();

// Offset and limit
$services = Service::offset(10)->limit(10)->get();

// Take (alias for limit)
$services = Service::take(5)->get();

// Simple pagination
$services = Service::paginate(15);  // Returns paginator object

// Chunking (for large datasets)
Service::chunk(100, function($services) {
    foreach ($services as $service) {
        // Process each service
    }
});
```

## Selecting Specific Columns

```php
// Select specific columns
$services = Service::select('id', 'name', 'price')->get();

// Select with alias
$services = Service::select('name as service_name')->get();

// Add columns to selection
$services = Service::select('id', 'name')
                   ->addSelect('price')
                   ->get();
```

## Aggregates

```php
// Count
$count = Service::count();

// Sum
$total = Order::sum('amount');

// Average
$avg = Order::avg('amount');

// Min/Max
$min = Order::min('amount');
$max = Order::max('amount');
```

## Raw Queries

```php
use Illuminate\Database\Capsule\Manager as Capsule;

// Raw select
$results = Capsule::select('SELECT * FROM services WHERE active = ?', [1]);

// Query builder
$services = Capsule::table('services')
    ->where('active', 1)
    ->get();

// Helper functions
$services = eloquent_table('services')->where('active', 1)->get();
```

## Settings Helper Methods

```php
use App\Models\Setting;

// Get setting
$chatId = Setting::get('telegram_chat_id');
$chatId = Setting::get('telegram_chat_id', 'default');  // With default

// Set setting
Setting::set('telegram_chat_id', '123456789');

// Set complex value (auto-JSON encodes)
Setting::set('site_config', ['key' => 'value']);

// Get all settings
$settings = Setting::getAll();  // Returns associative array
```

## Model Properties

### Common Properties (All Models)

```php
$model->id              // Primary key
$model->created_at      // Carbon datetime
$model->updated_at      // Carbon datetime
```

### Service

```php
$service->name
$service->slug
$service->icon
$service->description
$service->features      // Array (JSON)
$service->price
$service->category
$service->sort_order
$service->active        // Boolean
$service->featured      // Boolean
```

### Order

```php
$order->order_number
$order->type            // 'order' or 'contact'
$order->name
$order->email
$order->phone
$order->telegram
$order->service
$order->subject
$order->message
$order->amount          // Decimal
$order->calculator_data // Array (JSON)
$order->status          // 'new', 'processing', 'completed', 'cancelled'
$order->telegram_sent   // Boolean
$order->telegram_error
```

### Portfolio

```php
$portfolio->title
$portfolio->description
$portfolio->image_url
$portfolio->category
$portfolio->tags        // Array (JSON)
$portfolio->sort_order
$portfolio->active      // Boolean
```

## Constants

```php
// Order types
Order::TYPE_ORDER       // 'order'
Order::TYPE_CONTACT     // 'contact'

// Order statuses
Order::STATUS_NEW           // 'new'
Order::STATUS_PROCESSING    // 'processing'
Order::STATUS_COMPLETED     // 'completed'
Order::STATUS_CANCELLED     // 'cancelled'
```

## Advanced: Collections

Eloquent returns Collections, which have many useful methods:

```php
$services = Service::all();

// Filter collection
$active = $services->filter(function($service) {
    return $service->active;
});

// Map collection
$names = $services->map(function($service) {
    return $service->name;
});

// Pluck values
$ids = $services->pluck('id');
$namesBySlug = $services->pluck('name', 'slug');

// Collection methods
$services->count()
$services->first()
$services->last()
$services->isEmpty()
$services->isNotEmpty()
$services->toArray()
$services->toJson()
```

## Error Handling

```php
try {
    $service = Service::findOrFail($id);
} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    // Record not found
    ApiResponse::notFound('Service not found');
}

try {
    $service = Service::create($data);
} catch (\Exception $e) {
    // Database error
    ApiResponse::error($e->getMessage());
}
```

## Performance Tips

```php
// ✅ Good: Select only needed columns
$services = Service::select('id', 'name')->get();

// ✅ Good: Use chunk for large datasets
Service::chunk(100, function($services) { /* ... */ });

// ✅ Good: Use exists() instead of count()
if (Service::where('slug', $slug)->exists()) { /* ... */ }

// ❌ Avoid: Loading all records when you need count
$count = Service::all()->count();  // Bad
$count = Service::count();         // Good
```

## See Also

- [Full Documentation](ELOQUENT_SETUP.md)
- [Migration Guide](MIGRATION_GUIDE.md)
- [Laravel Eloquent Docs](https://laravel.com/docs/8.x/eloquent)
