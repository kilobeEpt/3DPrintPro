# Eloquent Migration Guide

This guide explains how to gradually migrate existing endpoints from the legacy Database class to Eloquent ORM.

## Overview

The Eloquent setup allows **coexistence** with the legacy Database class. You can:

1. Keep existing endpoints using Database class
2. Create new endpoints with Eloquent
3. Gradually refactor old endpoints one at a time

## Migration Strategy

### Phase 1: New Features Use Eloquent

All new API endpoints and features should use Eloquent:

```php
<?php
// Example: new endpoint using Eloquent
require_once __DIR__ . '/helpers/security_headers.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\Service;

SecurityHeaders::apply();

$services = Service::active()->featured()->ordered()->get();
ApiResponse::success(['services' => $services]);
```

### Phase 2: Refactor High-Traffic Endpoints

Identify and refactor your most frequently used endpoints first.

#### Before (Legacy Database class):

```php
<?php
require_once __DIR__ . '/db.php';

$db = new Database();
$services = $db->getRecords('services', ['active' => 1], 'sort_order');

echo json_encode(['success' => true, 'data' => $services]);
```

#### After (Eloquent):

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\Service;

$services = Service::active()->ordered()->get();

echo json_encode(['success' => true, 'data' => $services]);
```

### Phase 3: Complete Migration

Eventually all endpoints can use Eloquent, and the Database class can be deprecated.

## Side-by-Side Comparison

### Getting Records

**Legacy:**
```php
$db = new Database();
$services = $db->getRecords('services', ['active' => 1], 'sort_order');
```

**Eloquent:**
```php
$services = Service::active()->ordered()->get();
```

### Getting Single Record

**Legacy:**
```php
$service = $db->getRecordById('services', $id);
```

**Eloquent:**
```php
$service = Service::find($id);
// or with 404 error
$service = Service::findOrFail($id);
```

### Creating Record

**Legacy:**
```php
$id = $db->insertRecord('services', [
    'name' => 'New Service',
    'slug' => 'new-service',
    'active' => 1
]);
```

**Eloquent:**
```php
$service = Service::create([
    'name' => 'New Service',
    'slug' => 'new-service',
    'active' => true
]);
$id = $service->id;
```

### Updating Record

**Legacy:**
```php
$db->updateRecord('services', $id, [
    'name' => 'Updated Name',
    'active' => 0
]);
```

**Eloquent:**
```php
$service = Service::find($id);
$service->name = 'Updated Name';
$service->active = false;
$service->save();

// or mass update
Service::where('id', $id)->update([
    'name' => 'Updated Name',
    'active' => false
]);
```

### Deleting Record

**Legacy:**
```php
$db->deleteRecord('services', $id);
```

**Eloquent:**
```php
Service::destroy($id);
// or
$service = Service::find($id);
$service->delete();
```

### Complex Queries

**Legacy:**
```php
$services = $db->getRecords('services', [
    'active' => 1,
    'featured' => 1
], 'sort_order', 10, 0);
```

**Eloquent:**
```php
$services = Service::where('active', true)
    ->where('featured', true)
    ->orderBy('sort_order')
    ->limit(10)
    ->get();

// or using scopes
$services = Service::active()->featured()->ordered()->limit(10)->get();
```

### Counting Records

**Legacy:**
```php
$count = $db->getCount('services', ['active' => 1]);
```

**Eloquent:**
```php
$count = Service::active()->count();
```

## Hybrid Approach (Transition Period)

During migration, you can use both systems in the same file:

```php
<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\Service;

$db = new Database();

// Use legacy for complex operations you haven't migrated yet
$stats = $db->getPDO()->query('SELECT COUNT(*) as total FROM orders')->fetch();

// Use Eloquent for new/simple queries
$services = Service::active()->get();

// Both work independently
```

## Benefits of Migration

### Performance
- **Query optimization**: Eloquent's query builder is optimized
- **Lazy loading**: Only fetch data when needed
- **Eager loading**: Reduce N+1 query problems (with relationships)

### Code Quality
- **Type safety**: Models provide type hints and autocomplete
- **Reusability**: Query scopes can be reused across endpoints
- **Testability**: Models are easier to test and mock
- **Readability**: More expressive, self-documenting code

### Developer Experience
- **Less boilerplate**: No manual JSON encoding/decoding
- **Automatic timestamps**: Created/updated timestamps managed automatically
- **Events**: Hook into model lifecycle (creating, created, updating, etc.)
- **Relationships**: Define relationships once, use everywhere

## Migration Checklist

For each endpoint you migrate:

- [ ] Add vendor autoload and bootstrap requires
- [ ] Import necessary models
- [ ] Replace Database class instantiation with model usage
- [ ] Convert where conditions to Eloquent methods
- [ ] Test GET operations
- [ ] Test POST/PUT operations
- [ ] Test DELETE operations
- [ ] Verify JSON response format matches
- [ ] Check error handling
- [ ] Update any frontend code if response structure changed
- [ ] Test with real data
- [ ] Deploy and monitor

## Common Pitfalls

### 1. Forgetting to require bootstrap

```php
// ❌ Wrong - Model not available
require_once __DIR__ . '/../vendor/autoload.php';
use App\Models\Service;

// ✅ Correct
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';
use App\Models\Service;
```

### 2. Boolean vs Integer

SQLite/MySQL store booleans as 0/1, but Eloquent casts them:

```php
// ❌ Wrong - numeric comparison
$service->active == 1

// ✅ Correct - boolean comparison
$service->active === true
```

### 3. JSON Handling

Eloquent auto-casts JSON fields:

```php
// ❌ Wrong - manual JSON decode
$features = json_decode($service->features);

// ✅ Correct - already an array
$features = $service->features;
```

### 4. Mass Assignment

Models protect against mass assignment. Define fillable fields:

```php
// In Model class
protected $fillable = ['name', 'description', 'active'];

// Then you can use create()
Service::create($request->all());
```

## Testing After Migration

After migrating an endpoint:

1. **Functional test**: Does it return expected data?
2. **Performance test**: Compare response times
3. **Edge case test**: Null values, empty results, large datasets
4. **Error test**: Invalid IDs, missing params
5. **Integration test**: Frontend still works

## Need Help?

- Check [ELOQUENT_SETUP.md](ELOQUENT_SETUP.md) for setup details
- Run smoke test: `php scripts/eloquent-smoke.php`
- Refer to [Laravel Eloquent docs](https://laravel.com/docs/8.x/eloquent)
- Check existing model implementations in `app/Models/`

## Example: Full Endpoint Migration

See `api/services.php` (legacy) vs. a new Eloquent version:

```php
<?php
// api/services-eloquent.php (example - not in repo)
require_once __DIR__ . '/helpers/security_headers.php';
require_once __DIR__ . '/helpers/rate_limiter.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/admin_auth.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\Service;

SecurityHeaders::apply();
SecurityHeaders::handlePreflight();

$method = $_SERVER['REQUEST_METHOD'];
$rateLimiter = new RateLimiter();

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $service = Service::find($_GET['id']);
                
                if ($service) {
                    ApiResponse::success(['service' => $service]);
                } else {
                    ApiResponse::notFound('Service not found');
                }
            } else {
                $query = Service::query();
                
                if (isset($_GET['active'])) {
                    $query->where('active', $_GET['active'] === 'true');
                }
                
                if (isset($_GET['featured'])) {
                    $query->where('featured', $_GET['featured'] === 'true');
                }
                
                $services = $query->ordered()->get();
                
                ApiResponse::success([
                    'services' => $services,
                    'total' => $services->count()
                ]);
            }
            break;
            
        case 'POST':
            AdminAuth::requireAuth();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $service = Service::create($data);
            
            ApiResponse::created(['service' => $service]);
            break;
            
        case 'PUT':
            AdminAuth::requireAuth();
            
            $id = $_GET['id'] ?? null;
            $service = Service::findOrFail($id);
            
            $data = json_decode(file_get_contents('php://input'), true);
            $service->update($data);
            
            ApiResponse::success(['service' => $service]);
            break;
            
        case 'DELETE':
            AdminAuth::requireAuth();
            
            $id = $_GET['id'] ?? null;
            Service::destroy($id);
            
            ApiResponse::success(['message' => 'Service deleted']);
            break;
            
        default:
            ApiResponse::methodNotAllowed();
    }
    
} catch (Exception $e) {
    ApiResponse::error($e->getMessage());
}
```

This example shows a complete refactored endpoint using Eloquent while maintaining the same API contract.
