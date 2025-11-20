# Real-Time Content Sync Guide

**Version:** 1.0  
**Last Updated:** January 2025  
**Feature Status:** Production Ready ✅

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Server-Side Setup](#server-side-setup)
4. [Client-Side Integration](#client-side-integration)
5. [Cache Strategy](#cache-strategy)
6. [SSE Events](#sse-events)
7. [Frontend Modules](#frontend-modules)
8. [Performance](#performance)
9. [Troubleshooting](#troubleshooting)
10. [Best Practices](#best-practices)

---

## Overview

The Real-Time Content Sync system provides instant content updates across all connected clients using Server-Sent Events (SSE) and IndexedDB caching.

**Key Features:**
- **Server-Sent Events (SSE)** - Real-time push notifications from server
- **IndexedDB Caching** - Client-side persistent storage with TTL
- **Automatic Invalidation** - Cache cleared on content changes
- **Skeleton States** - Loading indicators during content fetch
- **Offline Support** - Graceful degradation with cached data
- **Event-Driven** - Custom events for UI reactivity

---

## Architecture

### Components

```
Backend:
├── ContentCacheService (cache headers, ETags, JSON snapshots)
├── SSEBroadcaster (event broadcasting)
└── /api/updates/stream.php (SSE endpoint)

Frontend:
├── CacheManager (js/cache-manager.js - IndexedDB with TTL)
├── SyncClient (js/sync-client.js - SSE client)
└── ContentLoader (js/content-loader.js - bootstrap & reload)
```

### Data Flow

```
[Admin Updates Content]
         ↓
[ContentCacheService invalidates cache]
         ↓
[SSEBroadcaster sends event to all clients]
         ↓
[SyncClient receives event]
         ↓
[CacheManager invalidates local cache]
         ↓
[ContentLoader reloads content]
         ↓
[UI updates automatically]
```

---

## Server-Side Setup

### ContentCacheService

**Location:** `app/Services/ContentCacheService.php`

**Features:**
- Generates ETags based on `updated_at` timestamps
- Sets cache headers (Cache-Control, Last-Modified)
- Handles 304 Not Modified responses
- Stores JSON snapshots for backend caching
- Tracks invalidation timestamps

**Usage in Controllers:**

```php
use App\Services\ContentCacheService;

class ServiceController extends BaseApiController
{
    protected $cacheService;
    
    public function __construct()
    {
        parent::__construct();
        $this->cacheService = new ContentCacheService();
    }
    
    public function index()
    {
        $services = Service::active()->ordered()->get();
        
        // Generate ETag
        $etag = $this->cacheService->generateETag($services);
        
        // Check if client has current version
        if ($this->cacheService->clientHasCurrent($etag)) {
            http_response_code(304);
            exit;
        }
        
        // Set cache headers
        $this->cacheService->setCacheHeaders($etag, $services->max('updated_at'));
        
        return $this->success(['services' => $services]);
    }
    
    public function store($data)
    {
        $service = Service::create($data);
        
        // Invalidate cache
        $this->cacheService->invalidateCache('services');
        
        // Broadcast SSE event
        $this->broadcaster->broadcastContentUpdate('service', $service->id, 'created');
        
        return $this->success(['service' => $service], 201);
    }
}
```

### SSEBroadcaster

**Location:** `app/Services/SSEBroadcaster.php`

**Features:**
- Broadcasts events to all connected clients
- Stores events in rolling window (100 events max)
- Supports event types: content.created, content.updated, content.deleted, cache.invalidated

**Usage:**

```php
use App\Services\SSEBroadcaster;

$broadcaster = new SSEBroadcaster();

// Broadcast content update
$broadcaster->broadcastContentUpdate('service', 1, 'updated');

// Broadcast cache invalidation
$broadcaster->broadcastCacheInvalidation('services');

// Broadcast custom event
$broadcaster->broadcast('custom.event', [
    'message' => 'Something happened',
    'data' => $customData
]);
```

### SSE Endpoint

**Location:** `/api/updates/stream.php`

**Request:**
```http
GET /api/updates/stream.php
Accept: text/event-stream
```

**Response:**
```
: Connected

event: init
data: {"type":"init","message":"Connected to event stream"}

event: heartbeat
data: {"type":"heartbeat","timestamp":1704067200}

event: content_changed
data: {"type":"content.updated","entityType":"service","entityId":1,"action":"updated","timestamp":1704067205}
```

**Features:**
- Long-lived HTTP connection
- Automatic reconnection on disconnect
- Heartbeat every 30 seconds
- Event history on reconnect

---

## Client-Side Integration

### Loading Order

**Correct order in HTML:**

```html
<!-- 1. Core dependencies -->
<script src="/js/cache-manager.js"></script>
<script src="/js/sync-client.js"></script>
<script src="/js/api-client.js"></script>

<!-- 2. Database layer (initializes cache + SSE) -->
<script src="/js/database.js"></script>

<!-- 3. Content loader (provides bootstrap API) -->
<script src="/js/content-loader.js"></script>

<!-- 4. Your app code -->
<script src="/js/main.js"></script>
```

### Bootstrap Content

**In your main.js:**

```javascript
document.addEventListener('DOMContentLoaded', async () => {
    // Bootstrap content on page load
    await window.contentLoader.bootstrapPage(['services', 'portfolio', 'testimonials']);
    
    // Content is now available in window.__INITIAL_DATA__
    const services = window.__INITIAL_DATA__.services;
    renderServices(services);
});
```

### Listen for Updates

```javascript
// Listen for content invalidation
window.addEventListener('content-invalidated', (event) => {
    console.log('Content invalidated:', event.detail);
    // Optional: Show "New content available" notification
});

// Listen for content reload
window.addEventListener('content-reloaded', (event) => {
    console.log('Content reloaded:', event.detail);
    const resource = event.detail.resource;
    const data = event.detail.data;
    
    // Update UI with new data
    if (resource === 'services') {
        renderServices(data);
    }
});

// Listen for content ready (initial load)
window.addEventListener('content-ready', (event) => {
    console.log('Content ready:', event.detail);
});
```

---

## Cache Strategy

### CacheManager

**Location:** `js/cache-manager.js`

**Features:**
- IndexedDB storage with TTL
- Automatic cleanup on page load
- Resource-keyed entries (e.g., "services:list")
- Metadata tracking (timestamp, ETag, TTL)

**API:**

```javascript
// Get from cache
const cached = await window.cacheManager.get('services:list');
if (cached && !cacheManager.isExpired(cached)) {
    console.log('Using cached data');
    return cached.data;
}

// Set cache with TTL
await window.cacheManager.set('services:list', servicesData, {
    ttl: 300000, // 5 minutes
    etag: 'abc123',
    source: 'api'
});

// Invalidate cache
await window.cacheManager.invalidate('services:list');

// Clear all cache
await window.cacheManager.clearAll();
```

### Cache Keys

**Format:** `{resource}:{operation}`

**Examples:**
- `services:list` - List of all services
- `services:1` - Single service with ID 1
- `portfolio:featured` - Featured portfolio items
- `testimonials:list` - List of testimonials

### TTL Strategy

| Resource | TTL | Reason |
|----------|-----|--------|
| Services | 5 min | Rarely changes |
| Portfolio | 5 min | Rarely changes |
| Testimonials | 5 min | Rarely changes |
| FAQ | 5 min | Rarely changes |
| Settings | 5 min | Changes infrequently |
| Calculator Config | 5 min | Admin-controlled |

**Why 5 minutes?**
- Balance between freshness and performance
- SSE invalidates immediately on change
- Fallback for clients without SSE

---

## SSE Events

### Event Types

#### 1. init

**Sent:** On connection established

```json
{
  "type": "init",
  "message": "Connected to event stream"
}
```

#### 2. heartbeat

**Sent:** Every 30 seconds

```json
{
  "type": "heartbeat",
  "timestamp": 1704067200
}
```

#### 3. invalidate

**Sent:** When cache should be cleared

```json
{
  "type": "cache.invalidated",
  "resource": "services",
  "timestamp": 1704067205
}
```

#### 4. content_changed

**Sent:** When content created/updated/deleted

```json
{
  "type": "content.updated",
  "entityType": "service",
  "entityId": 1,
  "action": "updated",
  "timestamp": 1704067205
}
```

#### 5. timeout

**Sent:** Before server closes connection

```json
{
  "type": "timeout",
  "message": "Connection timeout, please reconnect"
}
```

### Handling Events

```javascript
// In sync-client.js
class SyncClient {
    handleEvent(event, data) {
        switch (event) {
            case 'init':
                console.log('✅ Connected to SSE stream');
                break;
                
            case 'invalidate':
                this.handleInvalidation(data);
                break;
                
            case 'content_changed':
                this.handleContentChange(data);
                break;
                
            case 'heartbeat':
                this.lastHeartbeat = Date.now();
                break;
                
            case 'timeout':
                console.log('⚠️ Connection timeout, reconnecting...');
                this.reconnect();
                break;
        }
    }
}
```

---

## Frontend Modules

### 1. CacheManager

**Purpose:** Manage IndexedDB cache with TTL

**Key Methods:**
- `get(key)` - Retrieve cached item
- `set(key, data, options)` - Store item with TTL
- `invalidate(key)` - Remove specific item
- `clearAll()` - Clear entire cache
- `isExpired(item)` - Check if item expired
- `cleanup()` - Remove expired items

**Example:**

```javascript
// Check cache first
let services = await window.cacheManager.get('services:list');

if (!services || window.cacheManager.isExpired(services)) {
    // Fetch from API
    const response = await fetch('/api/services.php');
    services = await response.json();
    
    // Cache for 5 minutes
    await window.cacheManager.set('services:list', services, {
        ttl: 300000
    });
}

renderServices(services.data);
```

### 2. SyncClient

**Purpose:** Establish SSE connection and handle events

**Key Methods:**
- `connect()` - Establish SSE connection
- `disconnect()` - Close connection
- `reconnect()` - Reconnect after disconnect
- `handleEvent(event, data)` - Process incoming events

**Example:**

```javascript
// Auto-connects on instantiation
const syncClient = new SyncClient('/api/updates/stream.php');

// Listen for custom events
window.addEventListener('content-invalidated', (e) => {
    console.log('Cache invalidated:', e.detail.resource);
    reloadContent(e.detail.resource);
});
```

### 3. ContentLoader

**Purpose:** Bootstrap and reload content with caching

**Key Methods:**
- `bootstrapPage(resources)` - Load multiple resources on page load
- `loadResource(resource)` - Load single resource
- `reloadResource(resource)` - Force reload (bypass cache)

**Example:**

```javascript
// Bootstrap on page load
await window.contentLoader.bootstrapPage(['services', 'portfolio', 'settings']);

// Access loaded data
const services = window.__INITIAL_DATA__.services;
const portfolio = window.__INITIAL_DATA__.portfolio;

// Reload specific resource
await window.contentLoader.reloadResource('services');
```

---

## Performance

### Metrics

**Cache Hit:**
- IndexedDB read: ~2ms
- Parse JSON: ~1ms
- **Total: ~3ms**

**Cache Miss:**
- API request: ~150ms
- Parse JSON: ~1ms
- IndexedDB write: ~5ms
- **Total: ~156ms**

**SSE Latency:**
- Event broadcast: <1ms
- Network transit: ~50ms
- Client processing: ~5ms
- **Total: ~56ms**

**Bootstrap:**
- 3 resources (cached): ~10ms
- 3 resources (uncached): ~450ms
- With SSE init: +50ms
- **Total: ~60-500ms**

### Optimization Tips

1. **Bootstrap Early**
   - Call `bootstrapPage()` in DOMContentLoaded
   - Reduces perceived load time

2. **Cache Aggressively**
   - 5-minute TTL balances freshness and performance
   - SSE ensures immediate updates

3. **Lazy Load**
   - Only bootstrap resources needed for current page
   - Load additional resources on demand

4. **Prefetch**
   - Prefetch likely next pages during idle time

5. **Service Worker** (Future)
   - Offline support with service worker
   - Background sync for cached content

---

## Troubleshooting

### SSE Connection Fails

**Symptoms:** No real-time updates, console shows connection errors

**Solutions:**
1. **Check SSE endpoint:** Verify `/api/updates/stream.php` accessible
2. **Check headers:** Response must have `Content-Type: text/event-stream`
3. **Check server config:** Nginx/Apache must not buffer SSE
4. **Check firewall:** Port 80/443 must allow long-lived connections

**Nginx Config:**
```nginx
location /api/updates/stream.php {
    proxy_buffering off;
    proxy_cache off;
    proxy_set_header Connection '';
    proxy_http_version 1.1;
    chunked_transfer_encoding off;
}
```

### Cache Not Invalidating

**Symptoms:** Old content displayed after admin update

**Solutions:**
1. **Check SSE connection:** Must be connected for instant invalidation
2. **Check TTL:** Cache expires after 5 minutes regardless
3. **Manual refresh:** Hard refresh (Ctrl+Shift+R) clears cache
4. **Clear IndexedDB:** DevTools → Application → IndexedDB → contentCache → Delete

### High Memory Usage

**Symptoms:** Browser memory increases over time

**Solutions:**
1. **Cleanup interval:** CacheManager runs cleanup on page load
2. **Limit cache size:** Keep cached items under 10MB total
3. **Short TTLs:** 5-minute TTL prevents indefinite growth
4. **Manual cleanup:** Call `cacheManager.cleanup()` periodically

### Skeleton States Not Showing

**Symptoms:** Blank screen during content load

**Solutions:**
1. **Check CSS:** Ensure `css/skeleton.css` loaded
2. **Check data attributes:** Elements need `data-content="resource"`
3. **Check event listeners:** ContentLoader should add loading classes
4. **Check z-index:** Skeleton overlay must be above content

---

## Best Practices

### 1. Always Bootstrap

```javascript
// ✅ Good
document.addEventListener('DOMContentLoaded', async () => {
    await contentLoader.bootstrapPage(['services', 'portfolio']);
    renderContent();
});

// ❌ Bad
document.addEventListener('DOMContentLoaded', () => {
    fetch('/api/services.php').then(/* ... */); // Bypasses cache
});
```

### 2. Use Data Attributes

```html
<!-- ✅ Good -->
<div class="services-list" data-content="services">
    <!-- Content will be loaded here -->
</div>

<!-- ❌ Bad -->
<div class="services-list">
    <!-- No data attribute, skeleton won't show -->
</div>
```

### 3. Listen for Events

```javascript
// ✅ Good
window.addEventListener('content-reloaded', (e) => {
    if (e.detail.resource === 'services') {
        renderServices(e.detail.data);
    }
});

// ❌ Bad
// Polling for changes
setInterval(() => {
    fetch('/api/services.php').then(/* ... */);
}, 5000);
```

### 4. Handle Errors Gracefully

```javascript
// ✅ Good
try {
    const data = await contentLoader.loadResource('services');
    renderServices(data);
} catch (error) {
    console.error('Failed to load services:', error);
    showErrorMessage('Unable to load services. Please try again.');
}

// ❌ Bad
const data = await contentLoader.loadResource('services'); // Unhandled error
renderServices(data);
```

### 5. Provide Feedback

```javascript
// ✅ Good
window.addEventListener('content-invalidated', (e) => {
    showNotification(`New ${e.detail.resource} available. Click to reload.`);
});

// ❌ Bad
window.addEventListener('content-invalidated', (e) => {
    // Silent reload, user confused by sudden change
    location.reload();
});
```

---

## Testing

### Manual Testing

1. **Open two browser windows:**
   - Window A: Public website
   - Window B: Admin panel

2. **Make a change in admin:**
   - Edit a service in Window B
   - Save changes

3. **Observe Window A:**
   - Console should log: "Content invalidated: services"
   - Content should reload automatically
   - New service data should display

4. **Check cache:**
   - DevTools → Application → IndexedDB → contentCache
   - Verify `services:list` timestamp updated

5. **Test offline:**
   - DevTools → Network → Offline
   - Reload page
   - Verify cached content displayed
   - Verify offline indicator shown

### Automated Testing

**Frontend Test:** `test-sync-system.html`

```bash
# Open in browser
open http://localhost/test-sync-system.html

# Or use CLI testing tool
node scripts/test-sync-frontend.js
```

**Backend Test:** PHPUnit

```bash
vendor/bin/phpunit tests/Integration/ContentSyncTest.php
```

---

## Migration from Legacy

### Before (Direct API Calls)

```javascript
// Old approach
fetch('/api/services.php')
    .then(res => res.json())
    .then(data => renderServices(data));
```

### After (With Caching & SSE)

```javascript
// New approach
document.addEventListener('DOMContentLoaded', async () => {
    await contentLoader.bootstrapPage(['services']);
    renderServices(window.__INITIAL_DATA__.services);
});

// Listen for updates
window.addEventListener('content-reloaded', (e) => {
    if (e.detail.resource === 'services') {
        renderServices(e.detail.data);
    }
});
```

---

## Future Enhancements

- [ ] Service Worker for offline support
- [ ] Background sync for pending changes
- [ ] Optimistic UI updates
- [ ] Conflict resolution for concurrent edits
- [ ] Push notifications for critical updates
- [ ] WebSocket fallback for SSE-unsupported browsers

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** Production Ready ✅
