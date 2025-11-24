# Database & Data Synchronization Analysis

**Version:** 1.0  
**Last Updated:** 2024  
**Purpose:** Comprehensive system architecture documentation covering folder structure, database initialization, API routing, admin UI data flow, and public frontend data synchronization.

---

## Table of Contents

1. [Project Structure & Entry Points](#1-project-structure--entry-points)
2. [Database Initialization Flow](#2-database-initialization-flow)
3. [API Routing Architecture](#3-api-routing-architecture)
4. [Admin UI Data Flow](#4-admin-ui-data-flow)
5. [Public Frontend Data Synchronization](#5-public-frontend-data-synchronization)
6. [Troubleshooting Tools & Smoke Tests](#6-troubleshooting-tools--smoke-tests)
7. [Quick Reference](#7-quick-reference)

---

## 1. Project Structure & Entry Points

### 1.1 Root Folder Structure

```
/home/engine/project/
├── admin/                  # Admin panel pages and assets
├── api/                    # API front controllers and helpers
├── app/                    # Application layer (Models, Controllers, Services)
├── bootstrap/              # Initialization scripts
├── css/                    # Public stylesheets
├── database/               # Database schema and utilities
├── deploy/                 # Deployment configurations
├── docs/                   # Documentation
├── includes/               # Shared includes (headers, footers)
├── js/                     # Public JavaScript modules
├── scripts/                # CLI utilities and seeders
├── storage/                # Writable storage (cache, uploads, backups, logs)
├── tests/                  # PHPUnit tests
├── vendor/                 # Composer dependencies
├── *.html                  # Public HTML pages
├── .env.example            # Environment configuration template
├── composer.json           # Composer dependencies
├── phpunit.xml             # PHPUnit configuration
└── README.md               # Project documentation
```

### 1.2 Public Pages (Entry Points)

All public-facing HTML pages are located in the project root:

| File | Purpose | Key JavaScript Modules |
|------|---------|----------------------|
| `index.html` | Homepage with calculator and services | calculator.js, database.js, content-loader.js |
| `services.html` | Services catalog | database.js, content-loader.js |
| `portfolio.html` | Portfolio gallery | database.js, content-loader.js |
| `about.html` | About page | settings-loader.js |
| `contact.html` | Contact form | validators.js, telegram.js |
| `blog.html` | Blog/news page | database.js |
| `why-us.html` | Why choose us | settings-loader.js |
| `districts.html` | Service districts | settings-loader.js |

**Bootstrap Pattern:**
```javascript
// Each public page loads in this order:
// 1. cache-manager.js (IndexedDB with TTL)
// 2. sync-client.js (SSE connection)
// 3. api-client.js (fetch wrapper)
// 4. database.js (API-first with cache)
// 5. content-loader.js (bootstrap API)

// On DOMContentLoaded:
contentLoader.bootstrapPage(['services', 'portfolio', 'testimonials']);
```

### 1.3 Admin Panel Entry Points

Located in `/admin/`, each PHP file is a dedicated admin page:

| File | Purpose | JavaScript Module | API Endpoints |
|------|---------|------------------|---------------|
| `index.php` | Dashboard | dashboard.js | /api/admin/stats.php |
| `services.php` | Manage services | services.js | /api/services.php |
| `portfolio.php` | Manage portfolio | portfolio.js | /api/portfolio.php |
| `testimonials.php` | Manage testimonials | testimonials.js | /api/testimonials.php |
| `faq.php` | Manage FAQ | faq.js | /api/faq.php |
| `content.php` | Manage content blocks | content.js | /api/content.php |
| `forms.php` | Form builder | forms.js | /api/forms.php, /api/form-fields.php |
| `submissions.php` | Form submissions | submissions.js | /api/form-submissions.php |
| `orders.php` | Order management | orders.js, order-detail.js | /api/orders.php, /api/orders/export.php |
| `settings.php` | Global settings | settings.js | /api/settings.php |
| `calculator-settings.php` | Calculator config | calculator-settings.js | /api/calculator-settings.php |
| `audit.php` | Audit logs | audit.js | /api/admin/audit-logs.php |
| `users.php` | User management | users.js | /api/admin/users.php |
| `login.php` | Login page | (inline) | /admin/login-handler.php |

**Admin Bootstrap Pattern:**
```php
<?php
// Each admin page follows this pattern:
define('ADMIN_INIT', true);
require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/includes/csrf.php';

$pageTitle = 'Page Title';
$pageScripts = 'modules/module-name.js';

include __DIR__ . '/includes/header.php';
// ... page content ...
include __DIR__ . '/includes/footer.php';
?>
```

### 1.4 API Front Controllers

Located in `/api/`, each PHP file handles HTTP requests for a resource:

| File | HTTP Methods | Controller | Purpose |
|------|-------------|-----------|---------|
| `services.php` | GET, POST, PUT, DELETE | ServiceController | CRUD for services |
| `portfolio.php` | GET, POST, PUT, DELETE | PortfolioController | CRUD for portfolio |
| `testimonials.php` | GET, POST, PUT, DELETE | TestimonialController | CRUD for testimonials |
| `faq.php` | GET, POST, PUT, DELETE | FAQController | CRUD for FAQ |
| `content.php` | GET, POST, PUT, DELETE | ContentBlockController | CRUD for content blocks |
| `orders.php` | GET, POST, PATCH | OrderController | Order management with v2.0 features |
| `forms.php` | GET, POST, PUT, DELETE | FormController | Form builder CRUD |
| `form-fields.php` | GET, POST, PUT, DELETE | FormFieldController | Field management |
| `form-submissions.php` | GET, PATCH | FormSubmissionController | View/process submissions |
| `settings.php` | GET, POST | SettingsService (direct) | Global settings |
| `calculator-settings.php` | GET, POST | CalculatorSettingsController | Calculator configuration |
| `admin/users.php` | GET, POST, PUT, DELETE | AdminUserController | User management |
| `admin/audit-logs.php` | GET, DELETE | AuditLogController | Audit logs |
| `orders/export.php` | GET | OrderExportService (direct) | CSV/PDF exports |
| `updates/stream.php` | GET (SSE) | SSEBroadcaster (direct) | Real-time updates |
| `email-test.php` | POST | (direct) | Test email configuration |
| `telegram-test.php` | POST | (direct) | Test Telegram integration |

**Standard Front Controller Pattern:**
```php
<?php
/**
 * Resource API Endpoint
 * Handles CRUD operations for [resource].
 * Uses Eloquent ORM via [Controller].
 */

require_once __DIR__ . '/bootstrap.php';

use App\Http\Controllers\Api\ServiceController;

$controller = new ServiceController();
$controller->handle();
```

---

## 2. Database Initialization Flow

### 2.1 Configuration Loading

The database is initialized via a multi-layer configuration system:

```
┌─────────────────────────────────────────────────────────────┐
│  1. Load .env file (if exists)                             │
│     ├── DB_CONNECTION=mysql                                 │
│     ├── DB_HOST=localhost                                   │
│     ├── DB_PORT=3306                                        │
│     ├── DB_DATABASE=3dprint                                 │
│     ├── DB_USERNAME=app_user                                │
│     └── DB_PASSWORD=secret                                  │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  2. Fallback to api/config.php (legacy)                    │
│     ├── define('DB_HOST', 'localhost');                     │
│     ├── define('DB_NAME', '3dprint');                       │
│     ├── define('DB_USER', 'app_user');                      │
│     └── define('DB_PASS', 'secret');                        │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  3. bootstrap/eloquent.php processes configuration          │
│     ├── Creates Illuminate\Container\Container             │
│     ├── Sets up Facade::setFacadeApplication($container)    │
│     ├── Initializes Capsule Manager with DB config         │
│     ├── Attaches Event Dispatcher                          │
│     ├── Calls $capsule->setAsGlobal()                      │
│     └── Calls $capsule->bootEloquent()                     │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  4. Eloquent & Facades now available globally               │
│     ├── DB::table('users')->get()                          │
│     ├── Schema::hasTable('users')                          │
│     ├── Capsule::table('users')->get()                     │
│     └── App\Models\User::all()                             │
└─────────────────────────────────────────────────────────────┘
```

### 2.2 Bootstrap Files

#### **bootstrap/eloquent.php**

**Location:** `/bootstrap/eloquent.php`  
**Purpose:** Initialize Eloquent ORM with full Facade support  
**Dependencies:** Composer autoloader, vlucas/phpdotenv, illuminate/database

**Key Features:**
- Loads `.env` file if available using Dotenv
- Falls back to `api/config.php` constants if no `.env`
- Creates Container for Dependency Injection
- Sets up Facade application root (required for DB/Schema facades)
- Initializes Capsule Manager with database configuration
- Attaches Event Dispatcher for model events
- Makes Capsule globally available
- Provides helper functions: `eloquent_capsule()`, `eloquent_connection()`, `eloquent_table()`

**Usage:**
```php
// In any file needing database access:
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/eloquent.php';

// Use Facades (recommended):
use Illuminate\Support\Facades\DB;
$users = DB::table('admin_users')->get();

// Or use Capsule:
use Illuminate\Database\Capsule\Manager as Capsule;
$users = Capsule::table('admin_users')->get();

// Or use Eloquent Models:
use App\Models\AdminUser;
$users = AdminUser::all();
```

#### **api/bootstrap.php**

**Location:** `/api/bootstrap.php`  
**Purpose:** Bootstrap API environment with all dependencies  
**Dependencies:** Composer autoloader, bootstrap/eloquent.php, API helpers

**Responsibilities:**
1. Load Composer autoloader (`vendor/autoload.php`)
2. Load Eloquent ORM (`bootstrap/eloquent.php`)
3. Load API helpers:
   - `helpers/security_headers.php` - SecurityHeaders class
   - `helpers/rate_limiter.php` - RateLimiter class
   - `helpers/response.php` - ApiResponse, ApiLogger
   - `helpers/admin_auth.php` - Auth helpers (requireAdminAuth, verifyCsrfToken, etc.)
4. Apply security headers for all API requests
5. Set up global exception handler
6. Configure error reporting based on DEBUG_MODE

**Usage:**
```php
// Every API endpoint starts with:
require_once __DIR__ . '/bootstrap.php';

// Now you have access to:
// - Eloquent ORM (DB, Schema facades)
// - API helpers (ApiResponse, SecurityHeaders, RateLimiter)
// - Admin auth helpers (requireAdminAuth(), verifyCsrfToken())
```

### 2.3 Eloquent Models

Located in `/app/Models/`, all models extend `BaseModel` (which extends Eloquent's `Model`):

**Content Models:**
- `Service` - Services catalog
- `Portfolio` - Portfolio projects
- `Testimonial` - Customer testimonials
- `FAQ` - Frequently asked questions
- `ContentBlock` - Dynamic content blocks

**Forms System:**
- `Form` - Form definitions
- `FormField` - Form field configurations
- `FormSubmission` - Form submission records
- `FormSubmissionValue` - Individual field values

**Orders Domain:**
- `Order` - Order records
- `OrderStatusHistory` - Status change tracking
- `OrderNote` - Internal notes on orders

**RBAC & Admin:**
- `AdminUser` - Admin user accounts
- `AdminSession` - Active admin sessions
- `AdminLoginAttempt` - Login attempt tracking
- `AdminActionLog` - Audit trail

**Settings:**
- `Setting` - Global key-value settings
- `SettingsAudit` - Settings change history

**Key Features:**
- JSON casting for complex fields (settings, validation_rules, options, submitted_data, etc.)
- Automatic timestamps (created_at, updated_at)
- Query scopes for common filters (active(), featured(), ordered(), etc.)
- Relationships defined (hasMany, belongsTo, etc.)
- Mass assignment protection with $fillable

### 2.4 Services Layer

Located in `/app/Services/`, these classes provide business logic:

| Service | Purpose | Key Methods |
|---------|---------|-------------|
| `SettingsService` | Global settings management | get(), set(), setMultiple(), getGrouped(), getAuditHistory() |
| `AdminAuthService` | Authentication & sessions | authenticate(), validateSession(), destroySession(), logAction() |
| `MediaUploadService` | File upload handling | upload($file, $type), delete($path), exists($path), getUrl($path) |
| `ContentCacheService` | HTTP cache headers & snapshots | generateETag(), setCacheHeaders(), invalidateCache(), storeSnapshot() |
| `SSEBroadcaster` | Server-Sent Events | broadcast($type, $data), broadcastContentUpdate(), getRecentEvents() |
| `OrderExportService` | CSV/PDF exports | generateCSV(), generatePDF(), generateSignedUrl() |
| `FormulaValidatorService` | Calculator formula validation | validate($formula), evaluate($formula, $vars), testCalculation() |

### 2.5 Database Provisioning

**Script:** `scripts/provision-database.php`  
**Purpose:** Automated database setup from scratch

**Workflow:**
```bash
# Full provisioning with seed data:
php scripts/provision-database.php --seed

# Create database and user only:
php scripts/provision-database.php --create-only

# Import schema only (assumes DB exists):
php scripts/provision-database.php --import-only

# Force overwrite existing database:
php scripts/provision-database.php --force --seed
```

**Steps Performed:**
1. **Read Configuration:** Load from `.env` or prompt for credentials
2. **Create Database:** Connect as admin, create database with UTF8MB4 collation
3. **Create User:** Create restricted application user with limited privileges
4. **Import Schema:** Execute `database/schema.sql` (18 tables)
5. **Verify Schema:** Check all tables exist and have expected structure
6. **Seed Data (optional):** Run seeders:
   - `seed-forms.php` - Default contact/order forms
   - `seed-calculator-settings.php` - Calculator configuration
   - `seed-global-settings.php` - Default global settings
7. **Output Report:** Display success message and next steps

**Exit Codes:**
- `0` - Success
- `1` - Configuration error
- `2` - Connection error
- `3` - Schema import error
- `4` - Verification error

---

## 3. API Routing Architecture

### 3.1 Request Flow Overview

```
┌─────────────────────────────────────────────────────────────┐
│  HTTP Request: GET /api/services.php?active=1               │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  1. Front Controller: /api/services.php                     │
│     ├── require_once __DIR__ . '/bootstrap.php';            │
│     ├── use App\Http\Controllers\Api\ServiceController;     │
│     ├── $controller = new ServiceController();              │
│     └── $controller->handle();                              │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  2. BaseApiController Constructor                           │
│     ├── Initialize RateLimiter (profile-based)              │
│     ├── Initialize ContentCacheService                      │
│     ├── Initialize SSEBroadcaster                           │
│     ├── Parse request method ($_SERVER['REQUEST_METHOD'])   │
│     ├── Parse query parameters ($_GET)                      │
│     └── Parse input data (JSON body or $_POST)              │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  3. BaseApiController::handle()                             │
│     ├── Apply rate limiting (applyRateLimit())              │
│     ├── Route to method: GET → index()                      │
│     │                     POST → store()                     │
│     │                     PUT → update()                     │
│     │                     DELETE → destroy()                 │
│     │                     PATCH → patch() [special cases]    │
│     └── Catch exceptions → ApiResponse::serverError()       │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  4. ServiceController::index() [example]                    │
│     ├── Check if cache still valid (ETag, Last-Modified)    │
│     ├── If valid → ApiResponse::notModified() [304]         │
│     ├── Build query: Service::query()                       │
│     ├── Apply filters: ->where('active', 1)                 │
│     ├── Apply pagination: paginate()                        │
│     ├── Set cache headers: setCacheHeaders()                │
│     └── Return data: ApiResponse::success($data, $meta)     │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  5. ApiResponse::success($data, $meta)                      │
│     ├── Set headers: Content-Type: application/json         │
│     ├── Build response: {data: ..., meta: {...}}           │
│     ├── JSON encode                                         │
│     ├── Log response                                        │
│     └── echo $json; exit;                                   │
└─────────────────────────────────────────────────────────────┘
```

### 3.2 BaseApiController

**Location:** `/app/Http/Controllers/Api/BaseApiController.php`  
**Purpose:** Abstract base class for all API controllers providing shared functionality

**Traits Used:**
- `PaginationTrait` - `paginate($query, $limit, $offset)` with page/limit/offset support
- `ValidatesRequests` - Field validation rules (required, email, length, pattern, etc.)
- `ManagesSlugs` - Slug generation with transliteration and deduplication

**Key Properties:**
- `$rateLimiter` - RateLimiter instance (profile-based)
- `$cacheService` - ContentCacheService for HTTP cache headers
- `$sseBroadcaster` - SSEBroadcaster for real-time events
- `$method` - HTTP request method (GET, POST, PUT, DELETE, PATCH)
- `$input` - Parsed request input (JSON body or POST data)
- `$query` - Query parameters ($_GET)

**Key Methods:**

| Method | Purpose | When Called |
|--------|---------|-------------|
| `handle()` | Main request handler | From front controller |
| `index()` | List resources (GET) | GET requests (no ID) |
| `show($id)` | Get single resource (GET) | GET requests with ID |
| `store()` | Create resource (POST) | POST requests |
| `update($id)` | Update resource (PUT) | PUT requests |
| `destroy($id)` | Delete resource (DELETE) | DELETE requests |
| `patch()` | Partial update (PATCH) | PATCH requests (special cases) |
| `requireAuth()` | Check admin authentication | Before write operations |
| `applyRateLimit($endpoint)` | Rate limit enforcement | At start of handle() |
| `input($key, $default)` | Get input value | To access POST/JSON data |
| `query($key, $default)` | Get query parameter | To access $_GET data |
| `parseInput()` | Parse request body | In constructor |

**Cache & Invalidation:**
```php
// In GET requests:
protected function setCacheHeaders($model)
{
    $this->cacheService->setCacheHeaders($model);
    return $this->checkCacheValid($model);
}

// In write operations:
protected function invalidateResourceCache($resource)
{
    $this->cacheService->invalidateCache($resource);
    $this->sseBroadcaster->broadcastCacheInvalidation($resource);
}

// In CREATE/UPDATE/DELETE:
protected function broadcastContentChange($entityType, $entityId, $action)
{
    $this->sseBroadcaster->broadcastContentUpdate($entityType, $entityId, $action);
}
```

**Rate Limiting:**
```php
protected function getRateLimitProfile()
{
    if ($this->method === 'GET') {
        return \RateLimiter::PROFILE_API_READ;  // 100/min
    }
    return \RateLimiter::PROFILE_API_WRITE;     // 30/min
}
```

### 3.3 Controller Examples

#### ServiceController (Simple CRUD)

**Location:** `/app/Http/Controllers/Api/ServiceController.php`

```php
class ServiceController extends BaseApiController
{
    protected $model = Service::class;
    protected $resourceName = 'services';
    
    public function index()
    {
        // Build query
        $query = Service::query();
        
        // Apply filters
        if ($this->query('active')) {
            $query->where('active', 1);
        }
        
        if ($slug = $this->query('slug')) {
            $query->where('slug', $slug);
        }
        
        // Apply ordering
        $query->ordered();
        
        // Check cache
        if ($this->setCacheHeaders($query)) {
            return; // 304 Not Modified
        }
        
        // Paginate
        $result = $this->paginate($query);
        
        return ApiResponse::success($result['data'], $result['meta']);
    }
    
    public function store()
    {
        $this->requireAuth(); // Enforce admin auth
        verifyCsrfToken();    // CSRF protection
        
        // Validate
        $errors = $this->validateInput([
            'name' => 'required|maxLength:255',
            'description' => 'required',
            'price' => 'required|numeric',
        ]);
        
        if (!empty($errors)) {
            return ApiResponse::validationError($errors);
        }
        
        // Generate unique slug
        $slug = $this->generateUniqueSlug(
            Service::class,
            $this->input('name')
        );
        
        // Create
        $service = Service::create([
            'name' => $this->input('name'),
            'slug' => $slug,
            'description' => $this->input('description'),
            'price' => $this->input('price'),
            'active' => $this->input('active', true),
        ]);
        
        // Invalidate cache & broadcast
        $this->invalidateResourceCache('services');
        $this->broadcastContentChange('service', $service->id, 'created');
        
        // Log action
        logAdminAction('create', 'service', $service->id);
        
        return ApiResponse::created($service);
    }
}
```

#### OrderController (Complex with v2.0 Features)

**Location:** `/app/Http/Controllers/Api/OrderController.php`

**Advanced Features:**
- Status history tracking
- Internal notes
- Archiving (soft delete)
- Advanced filtering (status, type, date range, search)
- Export URL generation
- Notification triggers (Telegram, email)

```php
public function patch()
{
    $this->requireAuth();
    verifyCsrfToken();
    
    $id = $this->query('id');
    $action = $this->query('action');
    
    switch ($action) {
        case 'status':
            return $this->updateStatus($id);
        case 'archive':
            return $this->archiveOrder($id);
        case 'unarchive':
            return $this->unarchiveOrder($id);
        case 'note':
            return $this->addNote($id);
        default:
            return ApiResponse::badRequest('Invalid action');
    }
}

private function updateStatus($id)
{
    $order = Order::findOrFail($id);
    $newStatus = $this->input('status');
    $comment = $this->input('comment');
    
    // Log status change
    OrderStatusHistory::logStatusChange(
        $order,
        $newStatus,
        getAuthenticatedUser(),
        $comment
    );
    
    // Update order
    $order->status = $newStatus;
    $order->save();
    
    // Send notifications
    $this->sendStatusChangeNotifications($order, $newStatus);
    
    // Invalidate cache
    $this->invalidateResourceCache('orders');
    
    // Log action
    logAdminAction('status_change', 'order', $id, [
        'old_status' => $order->getOriginal('status'),
        'new_status' => $newStatus,
    ]);
    
    return ApiResponse::success($order->load(['statusHistory', 'notes']));
}
```

### 3.4 Response Format

All API responses use standardized format via `ApiResponse` helper:

**Success Response:**
```json
{
  "data": [...],
  "meta": {
    "total": 50,
    "page": 1,
    "limit": 20,
    "pages": 3
  }
}
```

**Error Response:**
```json
{
  "error": "Validation failed",
  "errors": {
    "name": "Name is required",
    "email": "Invalid email format"
  }
}
```

**HTTP Status Codes:**
- `200 OK` - Success
- `201 Created` - Resource created
- `304 Not Modified` - Cache hit
- `400 Bad Request` - Validation error
- `401 Unauthorized` - Not authenticated
- `403 Forbidden` - Not authorized
- `404 Not Found` - Resource not found
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

---

## 4. Admin UI Data Flow

### 4.1 Admin Page Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  Browser loads /admin/services.php                          │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  1. PHP Server-Side Rendering                               │
│     ├── session-config.php → Start session                  │
│     ├── admin-auth.php → Validate session, load user        │
│     ├── csrf.php → Generate CSRF token                      │
│     ├── header.php → Render header, sidebar, meta           │
│     ├── Page content → HTML structure with data attributes  │
│     └── footer.php → Load scripts in order                  │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  2. JavaScript Loading Order (from footer.php)              │
│     ├── admin-api-client.js → AdminApiClient class          │
│     ├── admin-main.js → Shared UI (sidebar, notifications)  │
│     └── modules/services.js → Page-specific module          │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  3. Module Initialization (DOMContentLoaded)                │
│     ├── const module = new ServicesModule();                │
│     ├── module.init() → Attach event listeners              │
│     └── module.loadServices() → Fetch from API              │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  4. API Request via AdminApiClient                          │
│     ├── window.adminApi.get('/api/services.php?active=1')   │
│     ├── Adds CSRF token from meta tag or ADMIN_SESSION      │
│     ├── Adds credentials: 'include' for cookies             │
│     ├── Retries on network errors (exponential backoff)     │
│     └── Returns promise with {data, meta} or throws error   │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  5. Response Handling in Module                             │
│     ├── Parse response data                                 │
│     ├── Update UI: renderServices(data)                     │
│     ├── Show notification: window.showNotification()        │
│     └── Update pagination/stats                             │
└─────────────────────────────────────────────────────────────┘
```

### 4.2 admin-main.js (Shared UI Components)

**Location:** `/admin/js/admin-main.js`  
**Purpose:** Bootstrap admin panel and provide shared UI functionality

**Key Features:**
- Sidebar collapse/expand with localStorage persistence
- User menu dropdown
- Quick settings dropdown
- Toast notifications (`window.showNotification()`)
- Modal utilities (`AdminMain.openModal()`, `AdminMain.closeModal()`)
- Theme management
- Orders badge counter
- Session sync warnings

**Global Functions:**
```javascript
// Show notification toast (available globally)
window.showNotification(message, type = 'success', duration = 5000);
// Types: success, error, warning, info

// Modal management
AdminMain.openModal(modalId);
AdminMain.closeModal(modalId);
AdminMain.confirmAction(message, onConfirm);

// Theme
AdminMain.setTheme(theme); // 'light', 'dark', 'auto'
AdminMain.toggleTheme();
```

**Initialization:**
```javascript
// Automatically runs on DOMContentLoaded:
const adminMain = new AdminMain();
adminMain.init();

// Modules can access via:
window.AdminMain = adminMain;
```

### 4.3 admin-api-client.js (API Communication)

**Location:** `/admin/js/admin-api-client.js`  
**Purpose:** Centralized API client for admin panel with CSRF, retry, error handling

**Global Instance:**
```javascript
// Available globally as:
window.adminApi = new AdminApiClient('/api');
```

**Methods:**

| Method | Signature | Purpose |
|--------|-----------|---------|
| `get(url, options)` | Returns Promise<{data, meta}> | GET request |
| `post(url, data, options)` | Returns Promise<{data}> | POST request |
| `put(url, data, options)` | Returns Promise<{data}> | PUT request |
| `delete(url, options)` | Returns Promise<{data}> | DELETE request |
| `patch(url, data, options)` | Returns Promise<{data}> | PATCH request |

**Features:**
- Automatic CSRF token injection (from meta tag or ADMIN_SESSION)
- Credential handling (`credentials: 'include'`)
- Retry logic with exponential backoff (3 attempts, 1s/2s/4s delays)
- Global error handling
- Response validation
- Loading state management

**Usage Example:**
```javascript
// GET request
try {
    const { data, meta } = await window.adminApi.get('/api/services.php', {
        params: { active: 1, limit: 20 }
    });
    console.log('Services:', data);
    console.log('Total:', meta.total);
} catch (error) {
    window.showNotification(error.message, 'error');
}

// POST request
try {
    const newService = await window.adminApi.post('/api/services.php', {
        name: 'New Service',
        description: 'Description',
        price: 1000,
        active: true
    });
    window.showNotification('Service created!', 'success');
} catch (error) {
    if (error.errors) {
        // Validation errors
        Object.entries(error.errors).forEach(([field, msg]) => {
            showFieldError(field, msg);
        });
    }
}

// DELETE request
try {
    await window.adminApi.delete(`/api/services.php?id=${id}`);
    window.showNotification('Service deleted', 'success');
    reloadServices();
} catch (error) {
    window.showNotification('Failed to delete', 'error');
}
```

### 4.4 Module Pattern (services.js Example)

**Location:** `/admin/js/modules/services.js`  
**Purpose:** Manage services CRUD interface

**Class Structure:**
```javascript
class ServicesModule {
    constructor() {
        this.services = [];
        this.currentService = null;
        this.currentPage = 1;
        this.totalPages = 1;
    }
    
    // ========================================
    // Initialization
    // ========================================
    
    init() {
        console.log('🔧 Initializing Services module...');
        this.attachEventListeners();
        this.loadServices();
    }
    
    attachEventListeners() {
        // New button
        document.getElementById('newServiceBtn')
            .addEventListener('click', () => this.openNewModal());
        
        // Form submit
        document.getElementById('serviceForm')
            .addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveService();
            });
        
        // Search
        document.getElementById('searchInput')
            .addEventListener('input', (e) => this.handleSearch(e.target.value));
        
        // Pagination
        document.querySelectorAll('.pagination-btn')
            .forEach(btn => btn.addEventListener('click', (e) => {
                this.currentPage = parseInt(e.target.dataset.page);
                this.loadServices();
            }));
    }
    
    // ========================================
    // Data Loading
    // ========================================
    
    async loadServices(filters = {}) {
        try {
            this.showLoading();
            
            const params = {
                page: this.currentPage,
                limit: 20,
                ...filters
            };
            
            const { data, meta } = await window.adminApi.get('/api/services.php', { params });
            
            this.services = data;
            this.totalPages = meta.pages;
            
            this.renderServices();
            this.renderPagination(meta);
            
        } catch (error) {
            window.showNotification('Failed to load services', 'error');
            console.error('Load error:', error);
        } finally {
            this.hideLoading();
        }
    }
    
    // ========================================
    // Rendering
    // ========================================
    
    renderServices() {
        const tbody = document.getElementById('servicesTableBody');
        
        if (this.services.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center">
                        <div class="empty-state">
                            <i class="fas fa-inbox fa-3x"></i>
                            <p>No services found</p>
                            <button onclick="servicesModule.openNewModal()" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add First Service
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = this.services.map(service => `
            <tr>
                <td>${this.escapeHtml(service.name)}</td>
                <td>${this.escapeHtml(service.price)} ₽</td>
                <td>
                    <span class="badge badge-${service.active ? 'success' : 'secondary'}">
                        ${service.active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>${this.formatDate(service.created_at)}</td>
                <td>
                    <div class="btn-group">
                        <button onclick="servicesModule.edit(${service.id})" 
                                class="btn btn-sm btn-secondary" 
                                title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="servicesModule.delete(${service.id})" 
                                class="btn btn-sm btn-danger" 
                                title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
    
    // ========================================
    // CRUD Operations
    // ========================================
    
    async saveService() {
        try {
            const formData = this.getFormData();
            
            // Validate
            const errors = this.validateForm(formData);
            if (Object.keys(errors).length > 0) {
                this.showValidationErrors(errors);
                return;
            }
            
            // Create or update
            if (this.currentService) {
                await window.adminApi.put(`/api/services.php?id=${this.currentService.id}`, formData);
                window.showNotification('Service updated successfully', 'success');
            } else {
                await window.adminApi.post('/api/services.php', formData);
                window.showNotification('Service created successfully', 'success');
            }
            
            // Reload and close modal
            this.loadServices();
            AdminMain.closeModal('serviceModal');
            
        } catch (error) {
            if (error.errors) {
                this.showValidationErrors(error.errors);
            } else {
                window.showNotification(error.message || 'Failed to save service', 'error');
            }
        }
    }
    
    async delete(id) {
        const confirmed = await AdminMain.confirmAction(
            'Are you sure you want to delete this service?',
            () => this.performDelete(id)
        );
    }
    
    async performDelete(id) {
        try {
            await window.adminApi.delete(`/api/services.php?id=${id}`);
            window.showNotification('Service deleted successfully', 'success');
            this.loadServices();
        } catch (error) {
            window.showNotification('Failed to delete service', 'error');
        }
    }
    
    // ========================================
    // Utilities
    // ========================================
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('ru-RU');
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.servicesModule = new ServicesModule();
    window.servicesModule.init();
});
```

### 4.5 Admin Module Reference

All modules follow the same pattern with variations for their specific domain:

| Module | Location | API Endpoints | Special Features |
|--------|----------|---------------|------------------|
| `dashboard.js` | `/admin/js/modules/dashboard.js` | /api/admin/stats.php | Chart.js widgets, real-time stats |
| `services.js` | `/admin/js/modules/services.js` | /api/services.php | CRUD, slug preview |
| `portfolio.js` | `/admin/js/modules/portfolio.js` | /api/portfolio.php | Image upload, featured toggle |
| `testimonials.js` | `/admin/js/modules/testimonials.js` | /api/testimonials.php` | Avatar upload, rating stars |
| `faq.js` | `/admin/js/modules/faq.js` | /api/faq.php | Drag-drop ordering |
| `content.js` | `/admin/js/modules/content.js` | /api/content.php | WYSIWYG editor, blocks |
| `forms.js` | `/admin/js/modules/forms.js` | /api/forms.php, /api/form-fields.php | Drag-drop field builder, conditional logic editor |
| `submissions.js` | `/admin/js/modules/submissions.js` | /api/form-submissions.php | Filtering, bulk actions |
| `orders.js` | `/admin/js/modules/orders.js` | /api/orders.php, /api/orders/export.php | Advanced filters, status workflow, export |
| `order-detail.js` | `/admin/js/modules/order-detail.js` | /api/orders.php (PATCH) | Status history, notes, attachments |
| `settings.js` | `/admin/js/modules/settings.js` | /api/settings.php | Tabbed interface, test buttons, audit history |
| `calculator-settings.js` | `/admin/js/modules/calculator-settings.js` | /api/calculator-settings.php | Formula validator, test calculations |
| `audit.js` | `/admin/js/modules/audit.js` | /api/admin/audit-logs.php | Filtering, CSV export, cleanup |
| `users.js` | `/admin/js/modules/users.js` | /api/admin/users.php | Role management, password reset |

---

## 5. Public Frontend Data Synchronization

### 5.1 Frontend Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│  Page Load: index.html, services.html, etc.                 │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  1. Load Core Scripts (in <head> or before closing </body>)│
│     ├── js/cache-manager.js → IndexedDB caching             │
│     ├── js/sync-client.js → SSE connection                  │
│     ├── js/api-client.js → Fetch wrapper                    │
│     ├── js/database.js → API-first data layer               │
│     └── js/content-loader.js → Bootstrap API                │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  2. Initialize Cache & SSE (database.js)                    │
│     ├── window.cacheManager = new CacheManager()            │
│     ├── window.syncClient = new SyncClient('/api/updates')  │
│     ├── syncClient.connect() → Start SSE stream             │
│     └── Listen for 'invalidate' events → clear cache        │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  3. Bootstrap Page Data (DOMContentLoaded)                  │
│     ├── contentLoader.bootstrapPage(['services', ...])      │
│     ├── Fetch from cache (IndexedDB) - ~2ms                 │
│     ├── If cached & fresh → use immediately                 │
│     ├── If stale → fetch from API in background             │
│     └── Dispatch 'content-ready' event                      │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  4. Render Content (main.js or page-specific scripts)       │
│     ├── Listen for 'content-ready' event                    │
│     ├── Get data: window.database.getServices()             │
│     ├── Render DOM: updateServicesGrid(services)            │
│     └── Show skeleton → real content transition             │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  5. Real-Time Updates (SSE)                                 │
│     ├── SSE: event 'invalidate' {resource: 'services'}      │
│     ├── syncClient emits 'content-invalidated'              │
│     ├── contentLoader.reloadResource('services')            │
│     ├── Fetch fresh data from API                           │
│     ├── Update cache                                        │
│     ├── Dispatch 'content-reloaded'                         │
│     └── Re-render: updateServicesGrid(services)             │
└─────────────────────────────────────────────────────────────┘
```

### 5.2 cache-manager.js (IndexedDB Caching)

**Location:** `/js/cache-manager.js`  
**Purpose:** Client-side caching with TTL using IndexedDB

**Key Features:**
- IndexedDB storage (persistent across sessions)
- TTL-based expiration (default 5 minutes)
- Resource-keyed (`services:list`, `portfolio:list`, etc.)
- Auto-cleanup on page load
- Versioning support

**API:**
```javascript
class CacheManager {
    constructor(dbName = '3dprint-cache', version = 1) { }
    
    // Store data with TTL
    async set(key, data, ttl = 300000) { }
    
    // Retrieve data (returns null if expired)
    async get(key) { }
    
    // Check if key exists and is fresh
    async has(key) { }
    
    // Delete specific key
    async delete(key) { }
    
    // Clear all cache
    async clear() { }
    
    // Cleanup expired entries
    async cleanup() { }
}

// Global instance
window.cacheManager = new CacheManager();
```

**Usage:**
```javascript
// Store data
await cacheManager.set('services:list', services, 300000); // 5min TTL

// Retrieve data
const services = await cacheManager.get('services:list');
if (services) {
    console.log('Cache hit:', services);
} else {
    console.log('Cache miss - fetch from API');
}

// Check freshness
const isFresh = await cacheManager.has('services:list');

// Clear specific resource
await cacheManager.delete('services:list');

// Clear all
await cacheManager.clear();
```

### 5.3 sync-client.js (SSE Client)

**Location:** `/js/sync-client.js`  
**Purpose:** Server-Sent Events client for real-time cache invalidation

**Key Features:**
- Auto-connect to `/api/updates/stream.php`
- Automatic reconnection on disconnect (exponential backoff)
- Event handling for `init`, `invalidate`, `content_changed`, `heartbeat`, `timeout`
- Event emitter pattern for decoupling

**API:**
```javascript
class SyncClient {
    constructor(endpoint = '/api/updates/stream.php') { }
    
    // Connect to SSE stream
    connect() { }
    
    // Disconnect
    disconnect() { }
    
    // Subscribe to events
    on(event, callback) { }
    
    // Emit event to listeners
    emit(event, data) { }
}

// Global instance
window.syncClient = new SyncClient('/api/updates/stream.php');
```

**Events:**

| Event | Payload | When Fired |
|-------|---------|------------|
| `connected` | `{clientId, timestamp}` | Initial connection |
| `invalidate` | `{resource, timestamp}` | Cache invalidation |
| `content_changed` | `{resource, action, entity_id, timestamp}` | CRUD operation |
| `heartbeat` | `{timestamp}` | Every 30 seconds |
| `timeout` | `{message}` | Connection timeout (5min) |
| `error` | `{error}` | Connection error |
| `reconnecting` | `{attempt, delay}` | Reconnection attempt |

**Usage:**
```javascript
// Connect
syncClient.connect();

// Listen for cache invalidation
syncClient.on('invalidate', async (data) => {
    console.log('Cache invalidated:', data.resource);
    await cacheManager.delete(`${data.resource}:list`);
    await contentLoader.reloadResource(data.resource);
});

// Listen for content changes
syncClient.on('content_changed', (data) => {
    console.log(`${data.resource} ${data.action}:`, data.entity_id);
    // Optionally reload specific item
});

// Disconnect when leaving page
window.addEventListener('beforeunload', () => {
    syncClient.disconnect();
});
```

### 5.4 api-client.js (Public API Client)

**Location:** `/js/api-client.js`  
**Purpose:** Centralized API communication for public pages

**Key Features:**
- Base URL configuration (from CONFIG.apiBaseUrl or `/api`)
- Retry logic with exponential backoff
- Network connectivity monitoring
- Offline/online event emitters
- Request/response logging

**API:**
```javascript
class APIClient {
    constructor(baseUrl = null) { }
    
    // HTTP methods
    async get(endpoint, options = {}) { }
    async post(endpoint, data, options = {}) { }
    async put(endpoint, data, options = {}) { }
    async delete(endpoint, options = {}) { }
    
    // Connectivity
    async checkConnectivity() { }
    isOnline() { }
    
    // Event listeners
    on(event, callback) { }  // 'online', 'offline'
}

// Global instance
window.apiClient = new APIClient('/api');
```

**Usage:**
```javascript
// GET request
try {
    const { data, meta } = await apiClient.get('/services.php', {
        params: { active: 1 }
    });
    console.log('Services:', data);
} catch (error) {
    if (!apiClient.isOnline) {
        showOfflineMessage();
    } else {
        showErrorMessage(error.message);
    }
}

// POST request (contact form)
try {
    const response = await apiClient.post('/contact-form.php', {
        name: 'John',
        email: 'john@example.com',
        message: 'Hello'
    });
    showSuccessMessage('Message sent!');
} catch (error) {
    showErrorMessage('Failed to send message');
}

// Listen for connectivity changes
apiClient.on('offline', () => {
    showOfflineIndicator();
});

apiClient.on('online', () => {
    hideOfflineIndicator();
    reloadData();
});
```

### 5.5 database.js (Unified Data Layer)

**Location:** `/js/database.js`  
**Purpose:** API-first data layer with automatic caching and synchronization

**Key Features:**
- Unified interface for all content resources
- Automatic cache management (IndexedDB)
- Real-time synchronization via SSE
- ETag/Last-Modified support for 304 responses
- Graceful degradation to stale cache on network errors

**Initialization:**
```javascript
// Automatically initialized when loaded:
window.database = Database.getInstance();
window.database.init();

// Sets up:
// - window.cacheManager
// - window.syncClient
// - Event listeners for cache invalidation
```

**Resource Methods:**
```javascript
class Database {
    // Services
    async getServices(filters = {}) { }
    async getService(id) { }
    async getServiceBySlug(slug) { }
    
    // Portfolio
    async getPortfolio(filters = {}) { }
    async getPortfolioItem(id) { }
    async getPortfolioBySlug(slug) { }
    
    // Testimonials
    async getTestimonials(filters = {}) { }
    async getTestimonial(id) { }
    
    // FAQ
    async getFAQ(filters = {}) { }
    
    // Content Blocks
    async getContentBlocks(filters = {}) { }
    async getContentBlock(identifier) { }
    
    // Settings
    async getSettings(groups = ['contact', 'social', 'seo']) { }
}
```

**Usage:**
```javascript
// Get services (from cache or API)
const services = await database.getServices({ active: 1 });

// Get single service
const service = await database.getService(123);

// Get by slug
const service = await database.getServiceBySlug('3d-printing');

// Portfolio
const portfolio = await database.getPortfolio({ featured: 1 });

// Settings
const settings = await database.getSettings(['contact', 'social']);
console.log(settings.contact_phone);
console.log(settings.social_telegram);
```

**Caching Strategy:**
```javascript
async getServices(filters = {}) {
    const cacheKey = 'services:list';
    
    // 1. Try cache first
    const cached = await cacheManager.get(cacheKey);
    if (cached) {
        console.log('Cache hit:', cacheKey);
        
        // Still fetch in background to check for updates
        this.fetchServicesInBackground(filters, cacheKey);
        
        return cached;
    }
    
    // 2. Cache miss - fetch from API
    console.log('Cache miss:', cacheKey);
    return await this.fetchServices(filters, cacheKey);
}

async fetchServices(filters, cacheKey) {
    try {
        const { data } = await apiClient.get('/services.php', { params: filters });
        
        // Store in cache
        await cacheManager.set(cacheKey, data, 300000); // 5min
        
        return data;
    } catch (error) {
        // Try stale cache as fallback
        const stale = await cacheManager.get(cacheKey, true);
        if (stale) {
            console.warn('Using stale cache due to error:', error);
            return stale;
        }
        throw error;
    }
}
```

### 5.6 content-loader.js (Bootstrap API)

**Location:** `/js/content-loader.js`  
**Purpose:** High-level API for bootstrapping page data and handling reloads

**Key Features:**
- Multi-resource bootstrapping (`bootstrapPage(['services', 'portfolio'])`)
- Skeleton state management (loading indicators)
- Auto-reload on cache invalidation
- Event dispatching (`content-ready`, `content-reloaded`, `content-reload-needed`)
- Error state management

**API:**
```javascript
class ContentLoader {
    // Bootstrap multiple resources
    async bootstrapPage(resources = []) { }
    
    // Reload single resource
    async reloadResource(resource) { }
    
    // Reload all page resources
    async reloadAll() { }
    
    // Show skeleton for resource
    showSkeleton(resource) { }
    
    // Hide skeleton for resource
    hideSkeleton(resource) { }
    
    // Show error state
    showError(resource, message) { }
}

// Global instance
window.contentLoader = new ContentLoader();
```

**Usage:**
```javascript
// In page script:
document.addEventListener('DOMContentLoaded', async () => {
    // Bootstrap page with multiple resources
    await contentLoader.bootstrapPage(['services', 'portfolio', 'testimonials']);
    
    // Listen for ready event
    window.addEventListener('content-ready', (e) => {
        const { resource, data } = e.detail;
        console.log(`${resource} ready:`, data);
        
        // Render content
        if (resource === 'services') {
            renderServices(data);
        }
    });
    
    // Listen for reloads (from SSE invalidation)
    window.addEventListener('content-reloaded', (e) => {
        const { resource, data } = e.detail;
        console.log(`${resource} reloaded:`, data);
        
        // Update DOM
        if (resource === 'services') {
            updateServices(data);
        }
    });
});

// Manual reload
async function refreshServices() {
    await contentLoader.reloadResource('services');
}
```

**Data Attributes:**

HTML elements can use `data-content` attribute for automatic skeleton management:

```html
<!-- Services grid with skeleton -->
<div class="services-grid" data-content="services">
    <!-- Will show skeleton during loading -->
    <div class="skeleton-card"></div>
    <div class="skeleton-card"></div>
</div>

<!-- Portfolio with skeleton -->
<div class="portfolio-grid" data-content="portfolio">
    <!-- Will show skeleton during loading -->
</div>
```

### 5.7 settings-loader.js (Auto-Population)

**Location:** `/js/settings-loader.js`  
**Purpose:** Automatically load and populate settings on public pages

**Key Features:**
- Auto-fetches public settings (contact, social, SEO)
- Auto-populates DOM elements with `data-contact` and `data-social` attributes
- Updates meta tags dynamically
- localStorage caching (5min TTL)

**Data Attributes:**
```html
<!-- Contact info (auto-populated) -->
<a href="tel:" data-contact="phone">Loading...</a>
<a href="mailto:" data-contact="email">Loading...</a>
<address data-contact="address">Loading...</address>
<span data-contact="working-hours">Loading...</span>

<!-- Social links (auto-populated) -->
<a href="#" data-social="telegram" target="_blank">
    <i class="fab fa-telegram"></i>
</a>
<a href="#" data-social="vk" target="_blank">
    <i class="fab fa-vk"></i>
</a>
<a href="#" data-social="instagram" target="_blank">
    <i class="fab fa-instagram"></i>
</a>
```

**Automatic Initialization:**
```javascript
// Automatically runs on load:
document.addEventListener('DOMContentLoaded', async () => {
    await settingsLoader.load();
});
```

**Manual Usage:**
```javascript
// Get settings
const settings = await settingsLoader.load();

console.log(settings.contact_phone);
console.log(settings.contact_email);
console.log(settings.social_telegram);
console.log(settings.seo_title);

// Force refresh (bypass cache)
const fresh = await settingsLoader.load(true);
```

### 5.8 calculator-api-loader.js (Calculator Config)

**Location:** `/js/calculator-api-loader.js`  
**Purpose:** Load calculator configuration from API instead of hardcoded config.js

**Key Features:**
- Fetches from `/api/calculator-settings.php`
- localStorage caching (5min TTL)
- Fallback to window.CONFIG for backward compatibility
- Type validation

**Usage:**
```javascript
// In calculator.js:
class Calculator {
    async init() {
        // Load config from API (with fallback to CONFIG)
        this.config = await CalculatorConfigLoader.load();
        
        // Use config
        const materials = this.config.materials;
        const services = this.config.services;
        const formulas = this.config.formulas;
        
        this.renderUI();
    }
}

// Manual load
const config = await CalculatorConfigLoader.load();
console.log('Materials:', config.materials);
console.log('Services:', config.services);
console.log('Formulas:', config.formulas);

// Force refresh
const fresh = await CalculatorConfigLoader.load(true);
```

### 5.9 SSE Stream Endpoint

**Location:** `/api/updates/stream.php`  
**Purpose:** Server-Sent Events endpoint for real-time cache invalidation

**Features:**
- Long-lived connection (5 minutes max)
- Event types: `init`, `invalidate`, `content_changed`, `heartbeat`, `timeout`
- Automatic reconnection on timeout
- Buffering disabled for immediate delivery

**Event Flow:**
```
Client connects → SSE stream established
                         ↓
Server sends: event:init, data:{clientId, timestamp}
                         ↓
Client receives: syncClient emits 'connected'
                         ↓
                  [Connection alive]
                         ↓
Admin updates service → OrderController::store()
                         ↓
$this->invalidateResourceCache('services')
                         ↓
ContentCacheService::invalidateCache('services')
SSEBroadcaster::broadcastCacheInvalidation('services')
                         ↓
Server sends: event:invalidate, data:{resource:'services', timestamp}
                         ↓
Client receives: syncClient emits 'invalidate'
                         ↓
contentLoader.reloadResource('services')
                         ↓
Fetch /api/services.php → Update cache → Re-render
                         ↓
User sees fresh data (no page reload!)
```

**Heartbeat:**
```
Every 30 seconds:
Server sends: event:heartbeat, data:{timestamp}
Client receives: (keeps connection alive)
```

**Timeout & Reconnection:**
```
After 5 minutes:
Server sends: event:timeout, data:{message}
Server closes connection

Client receives:
- syncClient emits 'timeout'
- Auto-reconnect after 5 seconds
- Exponential backoff on repeated failures
```

---

## 6. Troubleshooting Tools & Smoke Tests

### 6.1 Backend Smoke Tests

| Script | Purpose | Usage |
|--------|---------|-------|
| `scripts/eloquent-smoke.php` | Test Eloquent setup, facades, models (17 tests) | `php scripts/eloquent-smoke.php` |
| `verify-facade-fix.php` | Quick Facade verification (4 tests) | `php verify-facade-fix.php` |
| `scripts/api_smoke.php` | Test all API endpoints (services, portfolio, etc.) | `php scripts/api_smoke.php` |
| `scripts/test-settings-service.php` | Test SettingsService CRUD and caching | `php scripts/test-settings-service.php` |
| `scripts/test-forms-api.php` | Test forms system API | `php scripts/test-forms-api.php` |
| `scripts/orders-smoke-test.php` | Test orders domain v2.0 (history, notes, archive) | `php scripts/orders-smoke-test.php` |
| `scripts/orders-export-smoke.php` | Test order export (CSV, PDF, signed URLs) | `php scripts/orders-export-smoke.php` |
| `scripts/test-users-api.php` | Test user management API | `php scripts/test-users-api.php` |
| `scripts/test-admin-session-sync.php` | Test admin session synchronization | `php scripts/test-admin-session-sync.php` |
| `scripts/verify-carbon-fix.php` | Verify Carbon namespace usage | `php scripts/verify-carbon-fix.php` |
| `scripts/test-admin-carbon.php` | Test Carbon in admin context | `php scripts/test-admin-carbon.php` |
| `database/verify-schema.php` | Verify all 18 tables exist and have correct structure | `php database/verify-schema.php` |
| `scripts/hosting-audit.php` | Validate hosting environment (Step 1 deployment) | `php scripts/hosting-audit.php [--strict] [--format=json]` |

### 6.2 Frontend Tests

| File | Purpose | How to Run |
|------|---------|-----------|
| `test-sync-system.html` | Test SSE, cache, content-loader integration | Open in browser: `http://localhost/test-sync-system.html` |
| `test-api-client.html` | Test public API client (retry, offline, etc.) | Open in browser: `http://localhost/test-api-client.html` |
| `test-admin-wrapper.html` | Test admin API client and CSRF handling | Open in browser: `http://localhost/test-admin-wrapper.html` |

### 6.3 PHPUnit Tests

**Run all tests:**
```bash
composer test
# or
vendor/bin/phpunit
```

**Run specific test suite:**
```bash
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Integration
```

**Test coverage:**
```bash
composer test-coverage
# Generates coverage report in tests/coverage/
```

**Key test files:**
- `tests/Unit/SettingsServiceTest.php` - Settings service (25+ tests)
- `tests/Unit/AdminAuthServiceTest.php` - Authentication (20+ tests)
- `tests/Unit/MediaUploadServiceTest.php` - File uploads (10+ tests)
- `tests/Unit/OrderExportServiceTest.php` - Exports (15+ tests)
- `tests/Unit/FormulaValidatorServiceTest.php` - Calculator formulas (30+ tests)
- `tests/Unit/RateLimiterTest.php` - Rate limiting (10+ tests)
- `tests/Unit/CsrfProtectionTest.php` - CSRF tokens (10+ tests)
- `tests/Unit/FacadeSupportTest.php` - DB/Schema facades (12+ tests)
- `tests/Integration/FormSubmissionTest.php` - Form workflow (10+ tests)
- `tests/Integration/BaseApiControllerTest.php` - API base (10+ tests)
- `tests/Integration/ContentApiTest.php` - Content CRUD (30+ tests)
- `tests/Integration/OrdersDomainTest.php` - Orders v2.0 (15+ tests)
- `tests/Integration/CalculatorSettingsApiTest.php` - Calculator settings (20+ tests)

### 6.4 Database Utilities

| Script | Purpose | Usage |
|--------|---------|-------|
| `scripts/provision-database.php` | Full database setup (create, import, seed) | `php scripts/provision-database.php --seed` |
| `scripts/setup-test-db.php` | Create SQLite test database | `php scripts/setup-test-db.php` |
| `database/backup.php` | Backup database with rotation | `php database/backup.php [--retention=30] [--verify]` |
| `scripts/migrate-orders-domain.php` | Add v2.0 features (history, notes, archive) | `php scripts/migrate-orders-domain.php` |
| `scripts/seed-order-status-history.php` | Backfill status history for existing orders | `php scripts/seed-order-status-history.php [--dry-run]` |
| `scripts/migrate-content-fields.php` | Add slug/featured/media columns | `php scripts/migrate-content-fields.php` |
| `scripts/seed-forms.php` | Seed default forms | `php scripts/seed-forms.php` |
| `scripts/seed-calculator-settings.php` | Migrate config.js to database | `php scripts/seed-calculator-settings.php [--force]` |
| `scripts/seed-global-settings.php` | Seed default settings | `php scripts/seed-global-settings.php` |

### 6.5 Deployment & Validation

| Script | Purpose | Usage |
|--------|---------|-------|
| `scripts/deploy.sh` | Automated production deployment | `bash scripts/deploy.sh [--dry-run] [--ci]` |
| `scripts/post-deploy.sh` | Post-deployment release management | `bash scripts/post-deploy.sh [--setup-shared]` |
| `scripts/hosting-audit.php` | Pre-deployment environment validation | `php scripts/hosting-audit.php [--strict]` |
| `scripts/test-hosting-audit.sh` | Validate hosting-audit.php script | `bash scripts/test-hosting-audit.sh` |
| `scripts/test-provision-script.sh` | Validate provision-database.php script | `bash scripts/test-provision-script.sh` |

### 6.6 Troubleshooting Workflows

#### Issue: Services not loading on frontend

**Steps:**
1. **Check SSE connection:**
   ```bash
   # Open browser console:
   console.log(window.syncClient.isConnected);
   ```

2. **Check cache:**
   ```javascript
   // Browser console:
   const services = await cacheManager.get('services:list');
   console.log('Cached services:', services);
   ```

3. **Test API directly:**
   ```bash
   curl http://localhost/api/services.php?active=1
   ```

4. **Run smoke test:**
   ```bash
   php scripts/api_smoke.php
   ```

5. **Check Eloquent:**
   ```bash
   php scripts/eloquent-smoke.php
   ```

#### Issue: Admin panel not saving data

**Steps:**
1. **Check CSRF token:**
   ```javascript
   // Browser console:
   console.log(document.querySelector('meta[name="csrf-token"]').content);
   ```

2. **Check session:**
   ```javascript
   // Browser console:
   console.log(window.ADMIN_SESSION);
   ```

3. **Test admin API:**
   ```javascript
   // Browser console:
   await window.adminApi.get('/api/services.php');
   ```

4. **Check PHP errors:**
   ```bash
   tail -f storage/logs/api_*.log
   ```

5. **Run session test:**
   ```bash
   php scripts/test-admin-session-sync.php
   ```

#### Issue: Calculator not working

**Steps:**
1. **Check config loading:**
   ```javascript
   // Browser console:
   const config = await CalculatorConfigLoader.load();
   console.log('Calculator config:', config);
   ```

2. **Test API:**
   ```bash
   curl http://localhost/api/calculator-settings.php
   ```

3. **Check formula validation:**
   ```bash
   php scripts/seed-calculator-settings.php
   ```

4. **Check PHPUnit:**
   ```bash
   vendor/bin/phpunit tests/Unit/FormulaValidatorServiceTest.php
   ```

#### Issue: Real-time sync not working

**Steps:**
1. **Check SSE endpoint:**
   ```bash
   curl -N http://localhost/api/updates/stream.php
   ```

2. **Check browser support:**
   ```javascript
   // Browser console:
   console.log(typeof EventSource !== 'undefined');
   ```

3. **Test sync system:**
   ```
   Open: http://localhost/test-sync-system.html
   ```

4. **Check cache invalidation:**
   ```javascript
   // Browser console:
   window.addEventListener('content-invalidated', (e) => {
       console.log('Invalidated:', e.detail);
   });
   ```

5. **Manual trigger:**
   ```javascript
   // Make change in admin panel, watch console
   ```

---

## 7. Quick Reference

### 7.1 Key File Locations

```
Configuration:
├── .env                                    # Environment config
├── .env.example                           # Template
├── .env.production.example                # Production template
├── api/config.example.php                 # Legacy config template
└── config.js                              # Frontend config (legacy)

Database:
├── bootstrap/eloquent.php                 # Eloquent initialization
├── database/schema.sql                    # Database schema (18 tables)
├── database/backup.php                    # Backup utility
└── database/verify-schema.php             # Schema verification

API:
├── api/bootstrap.php                      # API bootstrap
├── api/*.php                              # Front controllers
├── api/helpers/                           # Shared helpers
├── app/Http/Controllers/Api/              # Controllers
├── app/Models/                            # Eloquent models
└── app/Services/                          # Business logic

Admin:
├── admin/*.php                            # Admin pages
├── admin/includes/                        # Shared includes
├── admin/js/admin-api-client.js           # API client
├── admin/js/admin-main.js                 # Shared UI
└── admin/js/modules/                      # Page modules

Frontend:
├── *.html                                 # Public pages
├── js/api-client.js                       # API client
├── js/cache-manager.js                    # IndexedDB cache
├── js/sync-client.js                      # SSE client
├── js/database.js                         # Data layer
├── js/content-loader.js                   # Bootstrap API
├── js/settings-loader.js                  # Settings auto-load
└── js/calculator-api-loader.js            # Calculator config

Tests:
├── tests/Unit/                            # Unit tests
├── tests/Integration/                     # Integration tests
├── phpunit.xml                            # PHPUnit config
├── test-sync-system.html                  # Frontend SSE test
└── test-api-client.html                   # Frontend API test

Scripts:
├── scripts/provision-database.php         # DB setup
├── scripts/deploy.sh                      # Deployment
├── scripts/hosting-audit.php              # Environment validation
├── scripts/eloquent-smoke.php             # Eloquent test
├── scripts/api_smoke.php                  # API test
└── scripts/orders-smoke-test.php          # Orders test

Documentation:
├── docs/API_REFERENCE.md                  # API documentation
├── docs/CONTENT_SYNC_SSE.md               # Real-time sync guide
├── docs/GLOBAL_SETTINGS.md                # Settings system
├── docs/CALCULATOR_SETTINGS.md            # Calculator config
├── docs/ORDERS_API_V2.md                  # Orders domain
├── docs/RBAC_AUTHENTICATION.md            # Authentication
├── docs/SECURITY.md                       # Security guide
├── docs/TESTING.md                        # Testing guide
├── docs/DEPLOYMENT.md                     # Deployment guide
├── docs/PRODUCTION_RUNBOOK.md             # Operations guide
└── docs/TROUBLESHOOTING.md                # Troubleshooting
```

### 7.2 Common Commands

```bash
# Development
composer install                           # Install dependencies
composer test                              # Run tests
composer test-coverage                     # Test with coverage

# Database
php scripts/provision-database.php --seed  # Full DB setup
php database/verify-schema.php             # Verify schema
php database/backup.php --verify           # Backup with verify

# Testing
php scripts/eloquent-smoke.php             # Test Eloquent
php scripts/api_smoke.php                  # Test API
vendor/bin/phpunit                         # Run PHPUnit
php scripts/hosting-audit.php              # Validate environment

# Seeding
php scripts/seed-forms.php                 # Seed forms
php scripts/seed-calculator-settings.php   # Seed calculator
php scripts/seed-global-settings.php       # Seed settings

# Deployment
php scripts/hosting-audit.php --strict     # Pre-deployment check
bash scripts/deploy.sh --dry-run           # Preview deployment
bash scripts/deploy.sh                     # Deploy to production

# Admin
php scripts/create-admin.php               # Create admin user
```

### 7.3 Architecture Summary

**Database Layer:**
```
.env → bootstrap/eloquent.php → Capsule → DB/Schema Facades
                                      ↓
                               Eloquent Models
                                      ↓
                                  Services
```

**API Layer:**
```
/api/services.php → api/bootstrap.php → ServiceController
                                              ↓
                                    BaseApiController
                                              ↓
                                       Service Model
                                              ↓
                                    ApiResponse::success()
```

**Admin Layer:**
```
/admin/services.php → header.php → admin-main.js → modules/services.js
                                                           ↓
                                                   admin-api-client.js
                                                           ↓
                                                    /api/services.php
```

**Frontend Layer:**
```
index.html → cache-manager.js + sync-client.js + api-client.js
                              ↓
                        database.js
                              ↓
                     content-loader.js
                              ↓
                    /api/services.php
                              ↓
                      IndexedDB cache
                              ↓
                         Render UI
```

**Real-Time Sync:**
```
Admin updates → BaseApiController::store()
                        ↓
            invalidateResourceCache()
                        ↓
        ContentCacheService + SSEBroadcaster
                        ↓
            /api/updates/stream.php
                        ↓
              syncClient (frontend)
                        ↓
           contentLoader.reloadResource()
                        ↓
                Fetch + update cache
                        ↓
                  Re-render UI
```

---

## Conclusion

This document provides a comprehensive overview of the 3D Print Pro system architecture, covering:

1. **Project Structure** - All entry points (public pages, admin pages, API endpoints)
2. **Database Initialization** - Environment config, Eloquent bootstrap, models, services
3. **API Routing** - Request flow, BaseApiController, specific controllers, response format
4. **Admin UI Data Flow** - Page architecture, admin-main.js, admin-api-client.js, module pattern
5. **Frontend Data Sync** - Cache strategy, SSE real-time updates, content loading, settings

For deeper dives into specific subsystems, refer to the specialized documentation:

- **API Reference:** `docs/API_REFERENCE.md`
- **Real-Time Sync:** `docs/CONTENT_SYNC_SSE.md`
- **Settings System:** `docs/GLOBAL_SETTINGS.md`
- **Calculator:** `docs/CALCULATOR_SETTINGS.md`
- **Orders Domain:** `docs/ORDERS_API_V2.md`
- **Authentication:** `docs/RBAC_AUTHENTICATION.md`
- **Security:** `docs/SECURITY.md`
- **Testing:** `docs/TESTING.md`
- **Deployment:** `docs/DEPLOYMENT.md` & `docs/PRODUCTION_RUNBOOK.md`
- **Troubleshooting:** `docs/TROUBLESHOOTING.md`

**Next Steps:**
- Run smoke tests to verify all systems: `php scripts/eloquent-smoke.php && php scripts/api_smoke.php`
- Test frontend sync: Open `test-sync-system.html` in browser
- Review deployment checklist: `docs/DEPLOYMENT.md`
- Set up hosting environment: `php scripts/hosting-audit.php --strict`
