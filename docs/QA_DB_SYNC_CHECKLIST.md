# QA Database Sync Checklist

Comprehensive manual verification guide for frontend/admin/database synchronization and system integration.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Step 1: Database Diagnostics](#step-1-database-diagnostics)
4. [Step 2: API Health Check](#step-2-api-health-check)
5. [Step 3: Frontend Data Loading & Caching](#step-3-frontend-data-loading--caching)
6. [Step 4: SSE Real-Time Sync](#step-4-sse-real-time-sync)
7. [Step 5: Public Form Submissions](#step-5-public-form-submissions)
8. [Step 6: Browser Console Verification](#step-6-browser-console-verification)
9. [Step 7: Admin Panel Testing](#step-7-admin-panel-testing)
10. [Step 8: Cross-Module Integration](#step-8-cross-module-integration)
11. [Sign-Off Criteria](#sign-off-criteria)
12. [Troubleshooting](#troubleshooting)

---

## Overview

This checklist validates that the entire system stack—database, API, frontend caching, admin panel, and real-time sync—works together correctly. Use this guide before declaring a deployment or major feature release as "synced and ready."

**Time estimate:** 45-60 minutes for full checklist

**When to use:**
- After fresh deployment to production/staging
- After database schema changes or migrations
- After major refactoring of API or frontend
- Before release sign-off
- During periodic QA audits

---

## Prerequisites

Before starting, ensure you have:

- [ ] **Server access** (SSH, SFTP, or shell access)
- [ ] **Admin credentials** (super_admin role preferred)
- [ ] **Browser with DevTools** (Chrome, Firefox, or Edge)
- [ ] **Base URL** of the application (e.g., `https://3dprint-omsk.ru` or `http://localhost:8000`)
- [ ] **PHP CLI access** for running scripts
- [ ] **Database credentials** (if running db_audit.php locally)

**Optional but recommended:**
- [ ] Access to `test-sync-system.html` for interactive testing
- [ ] Multiple browsers for cross-browser validation
- [ ] Mobile device or responsive design mode for mobile testing

---

## Step 1: Database Diagnostics

**Purpose:** Verify database schema, structure, and baseline data integrity.

### 1.1 Run Database Audit Script

```bash
php scripts/db_audit.php
```

**Expected Output:**

```
=== DATABASE AUDIT REPORT ===
Date: 2024-01-15 14:30:00

CONNECTION: OK ✓
- Host: localhost
- Database: 3dprint_omsk
- Character Set: utf8mb4
- Collation: utf8mb4_unicode_ci

SCHEMA VALIDATION: OK ✓
Tables found: 18/18
All required tables exist

TABLE DETAILS:
┌─────────────────────────┬──────────┬────────────┬─────────────┬────────────┐
│ Table                   │ Rows     │ Columns    │ Indexes     │ Status     │
├─────────────────────────┼──────────┼────────────┼─────────────┼────────────┤
│ orders                  │ 150      │ 15         │ 5           │ OK ✓       │
│ order_status_history    │ 320      │ 7          │ 3           │ OK ✓       │
│ order_notes             │ 45       │ 6          │ 2           │ OK ✓       │
│ settings                │ 72       │ 4          │ 2           │ OK ✓       │
│ services                │ 8        │ 10         │ 3           │ OK ✓       │
│ portfolio               │ 24       │ 12         │ 4           │ OK ✓       │
│ testimonials            │ 15       │ 11         │ 3           │ OK ✓       │
│ faq                     │ 12       │ 7          │ 2           │ OK ✓       │
│ content_blocks          │ 6        │ 8          │ 2           │ OK ✓       │
│ forms                   │ 3        │ 9          │ 2           │ OK ✓       │
│ form_fields             │ 28       │ 10         │ 4           │ OK ✓       │
│ form_submissions        │ 95       │ 7          │ 4           │ OK ✓       │
│ form_submission_values  │ 380      │ 5          │ 3           │ OK ✓       │
│ settings_audit          │ 142      │ 7          │ 3           │ OK ✓       │
│ admin_users             │ 4        │ 8          │ 2           │ OK ✓       │
│ admin_sessions          │ 2        │ 6          │ 3           │ OK ✓       │
│ admin_login_attempts    │ 87       │ 6          │ 3           │ OK ✓       │
│ admin_action_logs       │ 1,248    │ 10         │ 5           │ OK ✓       │
└─────────────────────────┴──────────┴────────────┴─────────────┴────────────┘

FOREIGN KEYS: OK ✓
All foreign key relationships valid

INDEXES: OK ✓
All required indexes present

SUMMARY: ✅ ALL CHECKS PASSED
```

### 1.2 Verification Checklist

- [ ] **Connection successful** - No database credentials errors
- [ ] **18 tables found** - All tables exist (orders, forms, settings, RBAC, etc.)
- [ ] **No missing columns** - All required columns present in each table
- [ ] **Indexes present** - Performance indexes on foreign keys, slugs, status fields
- [ ] **Foreign keys valid** - All relationships properly constrained
- [ ] **Character set UTF8MB4** - Full Unicode support including emojis and Cyrillic

### 1.3 Troubleshooting

| Issue | Solution |
|-------|----------|
| **Script not found** | Ensure you're in project root: `cd /var/www/html` |
| **Database connection failed** | Check `.env` or `api/config.php` credentials |
| **Missing tables** | Run `php scripts/provision-database.php --import-only` |
| **Missing columns** | Run migration scripts from `database/migrations/` |
| **Foreign key errors** | Check for orphaned records, restore from backup |

---

## Step 2: API Health Check

**Purpose:** Validate all API endpoints respond correctly with proper authentication and data.

### 2.1 Run API Smoke Test (Read-Only Mode)

**For production/live sites:**

```bash
php scripts/api_smoke.php \
  --url=https://3dprint-omsk.ru \
  --admin-email=admin@example.com \
  --admin-password=YourSecurePassword \
  --readonly
```

**For development/staging (full CRUD test):**

```bash
php scripts/api_smoke.php \
  --url=http://localhost:8000 \
  --admin-email=admin@example.com \
  --admin-password=YourSecurePassword
```

**Expected Output:**

```
🧪 API Smoke Test Suite v2.0
Base URL: https://3dprint-omsk.ru
Mode: READ-ONLY (safe for production)
Auth: Enabled
================================================================================

📦 Testing: Health/Test Endpoint
--------------------------------------------------------------------------------
  ✅ GET /api/test.php returns 200
  ✅ Response has correct structure
  ✅ Response has database_status

📦 Testing: Admin Authentication
--------------------------------------------------------------------------------
  ✅ Admin login returns redirect
  ✅ Admin authentication successful
  ✅ Session cookie set

📦 Testing: Services API
--------------------------------------------------------------------------------
  ✅ GET /api/services.php returns 200
  ✅ Response has data array
  ✅ Services have required fields (id, name, slug)

[... more test groups ...]

================================================================================
📊 Test Summary
================================================================================
Total Tests:  127
✅ Passed:    127
❌ Failed:    0
Success Rate: 100.0%

✅ ALL SMOKE TESTS PASSED
```

### 2.2 Verification Checklist

- [ ] **100% pass rate** - All tests show green checkmarks
- [ ] **Health endpoint responds** - `/api/test.php` returns 200
- [ ] **Admin authentication works** - Login successful with valid credentials
- [ ] **Public endpoints accessible** - Services, portfolio, testimonials, FAQ, content blocks
- [ ] **Admin endpoints secured** - Require authentication, return 401/403 without credentials
- [ ] **Response structures valid** - All responses have `success`, `data`, `meta` keys
- [ ] **Data integrity** - Services have names, portfolio has images, testimonials have ratings
- [ ] **Cleanup successful** (CRUD mode only) - All test records deleted

### 2.3 Troubleshooting

| Issue | Solution |
|-------|----------|
| **Authentication failed** | Verify admin credentials with `php scripts/create-admin.php` |
| **401/403 errors** | Check admin user status is 'active', not 'locked' |
| **500 errors** | Check PHP error logs: `tail -f storage/logs/php_error.log` |
| **Timeout errors** | Increase PHP `max_execution_time` in php.ini |
| **Connection refused** | Verify web server is running and URL is correct |
| **CSRF token errors** | Clear admin sessions: `DELETE FROM admin_sessions` |
| **Rate limit errors** | Wait 1 minute or clear rate limit cache: `rm -rf storage/cache/rate_limits/*` |

---

## Step 3: Frontend Data Loading & Caching

**Purpose:** Verify frontend correctly loads data from API and caches it in IndexedDB.

### 3.1 Access Test Sync System

Open in browser:

```
https://3dprint-omsk.ru/test-sync-system.html
```

Or for local development:

```
http://localhost:8000/test-sync-system.html
```

### 3.2 Perform Interactive Tests

**Test 1: Initial Load**

1. Click **"Clear All Cache & Reload"** button
2. Wait for page to reload
3. Observe console output

**Expected Output:**

```
[ContentLoader] Initializing...
[CacheManager] IndexedDB initialized: 3dprint-cache
[SyncClient] SSE client initialized
[ContentLoader] Bootstrapping page with resources: services, portfolio, testimonials
[CacheManager] Cache MISS for services:list
[API] Fetching: /api/services.php
[CacheManager] Cache STORE: services:list (expires in 300s)
[ContentLoader] ✓ services loaded (8 items)
[CacheManager] Cache MISS for portfolio:list
[API] Fetching: /api/portfolio.php
[CacheManager] Cache STORE: portfolio:list (expires in 300s)
[ContentLoader] ✓ portfolio loaded (24 items)
[ContentLoader] ✓ All resources loaded successfully
[SyncClient] Connected to SSE stream: /api/updates/stream.php
```

**Test 2: Cache Hit**

1. Click **"Reload Page"** button (or press F5)
2. Observe console output

**Expected Output:**

```
[ContentLoader] Initializing...
[ContentLoader] Bootstrapping page with resources: services, portfolio, testimonials
[CacheManager] Cache HIT for services:list (expires in 287s)
[ContentLoader] ✓ services loaded from cache (8 items)
[CacheManager] Cache HIT for portfolio:list (expires in 285s)
[ContentLoader] ✓ portfolio loaded from cache (24 items)
[ContentLoader] ✓ All resources loaded successfully
[SyncClient] Connected to SSE stream
```

**Performance verification:**
- Cache HIT should be ~2-5ms
- API fetch should be ~100-300ms
- Total bootstrap should be <500ms with cache

**Test 3: Cache Invalidation**

1. Keep test page open
2. In another tab, login to admin panel: `/admin/login.php`
3. Edit a service: `/admin/services.php` → Click Edit → Change name → Save
4. Return to test page tab
5. Observe console output

**Expected Output:**

```
[SyncClient] SSE Event: content_changed
  Entity: service
  Action: update
  ID: 3
[SyncClient] Invalidating cache: services:list
[CacheManager] Cache DELETE: services:list
[ContentLoader] Content reload needed: services
[API] Fetching: /api/services.php
[CacheManager] Cache STORE: services:list (expires in 300s)
[ContentLoader] ✓ services reloaded (8 items)
```

**Test 4: IndexedDB Inspection**

1. Open DevTools → **Application** tab (Chrome) or **Storage** tab (Firefox)
2. Expand **IndexedDB** → **3dprint-cache** → **cache** object store
3. Verify entries exist for:
   - `services:list`
   - `portfolio:list`
   - `testimonials:list`
4. Click an entry and inspect structure

**Expected Structure:**

```json
{
  "key": "services:list",
  "value": {
    "data": [ /* array of services */ ],
    "timestamp": 1705324800000,
    "expiresAt": 1705325100000,
    "etag": "abc123def456"
  }
}
```

### 3.3 Verification Checklist

- [ ] **Initial load successful** - All resources loaded, no errors
- [ ] **Cache MISS on first load** - API requests made, data stored in IndexedDB
- [ ] **Cache HIT on reload** - Data loaded from IndexedDB, no API requests
- [ ] **Cache timing correct** - TTL set to 5 minutes (300s)
- [ ] **IndexedDB populated** - All bootstrapped resources visible in DevTools
- [ ] **SSE connected** - Connection to `/api/updates/stream.php` established
- [ ] **Auto-invalidation works** - Admin edits trigger cache clear and reload
- [ ] **Performance acceptable** - Cache hits <5ms, API fetches <300ms

### 3.4 Troubleshooting

| Issue | Solution |
|-------|----------|
| **IndexedDB not initialized** | Check browser compatibility, try Chrome/Firefox latest |
| **Cache never expires** | Check system clock, verify TTL in cache-manager.js |
| **SSE not connecting** | Verify `/api/updates/stream.php` accessible, check CORS headers |
| **Cache not invalidating** | Verify admin operations call `invalidateResourceCache()` |
| **Data not reloading** | Check browser console for errors, verify SyncClient event handlers |
| **Stale data showing** | Clear IndexedDB manually: DevTools → Application → Clear Storage |

---

## Step 4: SSE Real-Time Sync

**Purpose:** Validate Server-Sent Events stream for real-time content updates.

### 4.1 Test SSE Endpoint Directly

**Using curl:**

```bash
curl -N -H "Accept: text/event-stream" https://3dprint-omsk.ru/api/updates/stream.php
```

**Expected Output:**

```
: Connection established

event: init
data: {"timestamp":"2024-01-15T14:30:00Z","server":"3dprint-omsk.ru"}

event: heartbeat
data: {"timestamp":"2024-01-15T14:30:30Z"}

event: heartbeat
data: {"timestamp":"2024-01-15T14:31:00Z"}
```

**Stop with:** `Ctrl+C`

### 4.2 Test SSE in Browser

**Option 1: Test Sync System Page**

1. Open `test-sync-system.html` in browser
2. Open DevTools → **Network** tab
3. Filter by "stream"
4. Find request to `/api/updates/stream.php`
5. Verify:
   - Status: `200`
   - Type: `eventsource` or `text/event-stream`
   - Preview shows events

**Option 2: Browser Console**

```javascript
// Open any page, then in console:
const sse = new EventSource('/api/updates/stream.php');

sse.addEventListener('init', (e) => {
  console.log('SSE Connected:', JSON.parse(e.data));
});

sse.addEventListener('heartbeat', (e) => {
  console.log('Heartbeat:', JSON.parse(e.data));
});

sse.addEventListener('content_changed', (e) => {
  console.log('Content Changed:', JSON.parse(e.data));
});

sse.onerror = (e) => {
  console.error('SSE Error:', e);
};

// To close:
// sse.close();
```

### 4.3 Test End-to-End SSE Flow

1. Open two browser windows side-by-side:
   - **Window A:** `https://3dprint-omsk.ru/services.html` (with DevTools console open)
   - **Window B:** `https://3dprint-omsk.ru/admin/services.php` (admin panel, logged in)

2. In **Window A** console, enable verbose logging:
   ```javascript
   localStorage.setItem('debug_sse', 'true');
   location.reload();
   ```

3. In **Window B** (admin), edit a service:
   - Click **Edit** on any service
   - Change the name or description
   - Click **Save**

4. In **Window A**, observe console output

**Expected Output:**

```
[SyncClient] SSE Event: content_changed
  Entity: service
  Action: update
  ID: 3
  Timestamp: 2024-01-15T14:35:00Z
[SyncClient] Invalidating cache: services:list
[CacheManager] Cache DELETE: services:list
[ContentLoader] Content reload needed: services
[API] GET /api/services.php
[CacheManager] Cache STORE: services:list
[ContentLoader] ✓ services reloaded (8 items)
[UI] Page content updated automatically
```

5. Verify service list UI updates **without page reload**

### 4.4 Verification Checklist

- [ ] **SSE endpoint responds** - `/api/updates/stream.php` returns 200
- [ ] **init event received** - Connection confirmed with timestamp
- [ ] **Heartbeat events** - Received every 30 seconds to keep connection alive
- [ ] **content_changed events** - Triggered when admin edits content
- [ ] **Cache invalidation** - SSE events trigger IndexedDB cache deletion
- [ ] **Auto-reload** - Frontend automatically fetches fresh data
- [ ] **UI updates** - Page content updates without manual refresh
- [ ] **Connection resilient** - Automatically reconnects after disconnect

### 4.5 Troubleshooting

| Issue | Solution |
|-------|----------|
| **404 on stream.php** | Verify file exists at `/api/updates/stream.php` |
| **Connection timeout** | Check Nginx/Apache buffering config (see `docs/WEB_SERVER_CONFIG.md`) |
| **No events received** | Check SSEBroadcaster writes to `storage/cache/sse_events.json` |
| **CORS errors** | Verify `SecurityHeaders::apply()` includes correct CORS policy |
| **Frequent disconnects** | Check PHP `max_execution_time`, increase if needed |
| **Events not triggering** | Verify controllers call `invalidateResourceCache()` on write operations |

---

## Step 5: Public Form Submissions

**Purpose:** Verify public users can submit forms and data persists correctly to database.

### 5.1 Submit Contact Form

1. Navigate to contact page:
   ```
   https://3dprint-omsk.ru/contact.html
   ```

2. Fill out the contact form:
   - **Name:** Test User QA
   - **Email:** qa-test@example.com
   - **Phone:** +7 913 555-1234
   - **Message:** This is a QA test submission to verify database sync.

3. Click **Submit** button

4. Observe UI feedback

**Expected Behavior:**
- Form shows loading spinner/state
- Success notification appears: "Спасибо! Ваше сообщение отправлено."
- Form fields clear/reset
- No JavaScript errors in console

### 5.2 Submit Calculator Order Form

1. Navigate to calculator page:
   ```
   https://3dprint-omsk.ru/calculator.html
   ```

2. Configure a 3D print order:
   - Select material: **PLA**
   - Enter dimensions: 10x10x10 cm
   - Select quality: **High**
   - Quantity: **5**

3. Observe price calculation updates in real-time

4. Click **"Оформить заказ"** (Place Order)

5. Fill out order form:
   - **Name:** QA Calculator Test
   - **Email:** qa-calc@example.com
   - **Phone:** +7 913 555-5678
   - **Comments:** Testing calculator integration with DB

6. Submit form

**Expected Behavior:**
- Price updates dynamically as you change inputs
- Order form modal/section appears
- Calculator data pre-populated in hidden fields
- Success message after submission
- Order number displayed

### 5.3 Verify Database Records

**Using Admin Panel UI:**

1. Login to admin panel:
   ```
   https://3dprint-omsk.ru/admin/login.php
   ```

2. Navigate to **Orders** page:
   ```
   https://3dprint-omsk.ru/admin/orders.php
   ```

3. Verify two new orders appear:
   - **Contact Form:** "Test User QA" - Status: New
   - **Calculator Order:** "QA Calculator Test" - Status: New

4. Click **View** on calculator order

5. Verify calculator data captured:
   - Material: PLA
   - Dimensions: 10x10x10
   - Quality: High
   - Quantity: 5
   - Total Price: displayed correctly

**Using Database Query (SSH/CLI):**

```bash
mysql -u your_user -p your_database

SELECT 
  o.id,
  o.order_number,
  o.name,
  o.email,
  o.status,
  o.type,
  o.calculator_data,
  o.created_at
FROM orders o
WHERE o.email IN ('qa-test@example.com', 'qa-calc@example.com')
ORDER BY o.created_at DESC
LIMIT 2;
```

**Expected Result:**

```
+-----+---------------+--------------------+---------------------+--------+-----------+-------------------+---------------------+
| id  | order_number  | name               | email               | status | type      | calculator_data   | created_at          |
+-----+---------------+--------------------+---------------------+--------+-----------+-------------------+---------------------+
| 152 | ORD-20240115  | QA Calculator Test | qa-calc@example.com | new    | order     | {"material":"PLA" | 2024-01-15 14:45:00 |
| 151 | ORD-20240115  | Test User QA       | qa-test@example.com | new    | contact   | NULL              | 2024-01-15 14:42:00 |
+-----+---------------+--------------------+---------------------+--------+-----------+-------------------+---------------------+
```

### 5.4 Verify Form Submissions Table

```bash
mysql -u your_user -p your_database

SELECT 
  fs.id,
  f.name AS form_name,
  fs.status,
  fs.submitted_data,
  o.order_number,
  fs.created_at
FROM form_submissions fs
JOIN forms f ON fs.form_id = f.id
LEFT JOIN orders o ON o.form_submission_id = fs.id
WHERE JSON_EXTRACT(fs.submitted_data, '$.email') IN ('"qa-test@example.com"', '"qa-calc@example.com"')
ORDER BY fs.created_at DESC
LIMIT 2;
```

**Expected Result:**

```
+-----+----------------+--------+-------------------+---------------+---------------------+
| id  | form_name      | status | submitted_data    | order_number  | created_at          |
+-----+----------------+--------+-------------------+---------------+---------------------+
| 96  | Calculator     | pending| {"name":"QA Cal...| ORD-20240115  | 2024-01-15 14:45:00 |
| 95  | Contact Form   | pending| {"name":"Test U...| ORD-20240115  | 2024-01-15 14:42:00 |
+-----+----------------+--------+-------------------+---------------+---------------------+
```

### 5.5 Verify Telegram Notification (Optional)

If Telegram integration is configured:

1. Check your Telegram bot chat
2. Verify two new order notifications received
3. Each notification should include:
   - Order number
   - Customer name and contact
   - Order type (contact/order)
   - Timestamp

**Example Notification:**

```
🆕 Новый заказ

📋 Номер: ORD-20240115
👤 Клиент: QA Calculator Test
📧 Email: qa-calc@example.com
📱 Телефон: +7 913 555-5678

💬 Комментарий:
Testing calculator integration with DB

📊 Детали заказа:
• Материал: PLA
• Размеры: 10x10x10 см
• Качество: High
• Количество: 5
• Стоимость: 1,250 ₽

🕐 15 января 2024 г., 14:45
```

### 5.6 Verification Checklist

- [ ] **Contact form submits** - No errors, success message displayed
- [ ] **Calculator order submits** - Price calculated, order created
- [ ] **Orders appear in admin** - Both submissions visible in Orders page
- [ ] **Calculator data captured** - JSON field populated with material, dimensions, etc.
- [ ] **Form submissions linked** - `form_submissions` records linked to `orders` via foreign key
- [ ] **Status is "new/pending"** - Default status applied correctly
- [ ] **Order numbers unique** - Auto-generated sequential numbers
- [ ] **Timestamps accurate** - `created_at` matches submission time
- [ ] **Telegram notifications sent** (if enabled) - Bot receives order details

### 5.7 Troubleshooting

| Issue | Solution |
|-------|----------|
| **Form shows error** | Check browser console, verify API endpoint responds |
| **No success message** | Check JavaScript event handlers, verify FormService returns success |
| **Orders not appearing** | Verify form POST goes to correct endpoint, check PHP error log |
| **Calculator data empty** | Verify calculator values passed to form submission |
| **Telegram not sending** | Test bot: `/api/telegram-test.php`, verify token and chat_id |
| **Validation errors** | Check FormService validation rules match form field configurations |

---

## Step 6: Browser Console Verification

**Purpose:** Ensure no JavaScript errors or warnings in production environment.

### 6.1 Check Each Public Page

Visit each page and inspect browser console:

1. **Homepage:** `/index.html`
2. **Services:** `/services.html`
3. **Portfolio:** `/portfolio.html`
4. **Calculator:** `/calculator.html`
5. **Contact:** `/contact.html`
6. **About:** `/about.html`
7. **FAQ:** `/faq.html`

**For each page:**

1. Open **DevTools** → **Console** tab
2. Clear console (trash icon)
3. Reload page (F5)
4. Wait for full page load (including API calls)
5. Review console output

**Expected Output (Clean):**

```
[ContentLoader] Initializing...
[ContentLoader] Bootstrapping page with resources: services
[CacheManager] Cache HIT for services:list
[ContentLoader] ✓ services loaded from cache (8 items)
[SyncClient] Connected to SSE stream
```

**Acceptable Warnings:**

- Third-party analytics warnings (Google Analytics, Yandex.Metrica)
- Chrome Extensions warnings (not related to site code)
- `[Violation] Added non-passive event listener` (performance advisory)

**Unacceptable Errors:**

❌ `Uncaught TypeError: Cannot read property...`
❌ `Uncaught ReferenceError: ... is not defined`
❌ `Failed to fetch: /api/...`
❌ `404 Not Found: /js/...`
❌ `CORS policy: No 'Access-Control-Allow-Origin' header...`
❌ `SecurityError: Blocked a frame with origin...`

### 6.2 Check Network Tab

1. Open **DevTools** → **Network** tab
2. Reload page
3. Review all requests

**Expected:**

- [ ] **All resources load** - No 404 errors on CSS, JS, images
- [ ] **API calls successful** - All `/api/*` requests return 200
- [ ] **SSE stream active** - `/api/updates/stream.php` shows pending/streaming
- [ ] **Cache headers present** - `ETag`, `Last-Modified`, `Cache-Control` on API responses
- [ ] **Proper MIME types** - CSS as `text/css`, JS as `application/javascript`
- [ ] **No mixed content** - All resources loaded over HTTPS (if site is HTTPS)

### 6.3 Check Application Tab (IndexedDB)

1. Open **DevTools** → **Application** tab (Chrome) or **Storage** tab (Firefox)
2. Expand **IndexedDB** → **3dprint-cache**
3. Click **cache** object store

**Expected:**

- [ ] **Database exists** - `3dprint-cache` database present
- [ ] **Object store exists** - `cache` store with records
- [ ] **Keys match resources** - Entries like `services:list`, `portfolio:list`, etc.
- [ ] **Data structure valid** - Each entry has `data`, `timestamp`, `expiresAt`, `etag`
- [ ] **Timestamps recent** - `timestamp` and `expiresAt` within last 5 minutes

### 6.4 Performance Audit (Optional)

1. Open **DevTools** → **Lighthouse** tab
2. Select:
   - **Performance** ✓
   - **Desktop** or **Mobile**
3. Click **Analyze page load**

**Target Scores:**

- **Performance:** 85+ (desktop), 70+ (mobile)
- **Accessibility:** 90+
- **Best Practices:** 90+
- **SEO:** 90+

### 6.5 Verification Checklist

- [ ] **No critical JavaScript errors** - Console clean or only acceptable warnings
- [ ] **All API calls succeed** - Network tab shows 200 responses
- [ ] **All static assets load** - No 404s on CSS, JS, images
- [ ] **SSE stream connected** - EventSource request pending/active
- [ ] **IndexedDB populated** - Cache entries present with valid structure
- [ ] **No CORS errors** - Cross-origin requests allowed where needed
- [ ] **No mixed content warnings** - All resources use HTTPS on HTTPS site
- [ ] **Performance acceptable** - Lighthouse scores meet targets

### 6.6 Troubleshooting

| Issue | Solution |
|-------|----------|
| **JS errors on load** | Check file paths, verify JS files uploaded correctly |
| **404 on API calls** | Verify Apache/Nginx rewrites configured, check `.htaccess` |
| **CORS errors** | Add CORS headers in `SecurityHeaders::apply()` |
| **Mixed content warnings** | Update hardcoded HTTP URLs to HTTPS or relative paths |
| **IndexedDB empty** | Verify `cache-manager.js` loaded before `database.js` |
| **High Lighthouse issues** | Optimize images, enable compression, minify JS/CSS |

---

## Step 7: Admin Panel Testing

**Purpose:** Verify all admin modules function correctly with full CRUD operations and notifications.

### 7.1 Login & Dashboard

1. Navigate to admin login:
   ```
   https://3dprint-omsk.ru/admin/login.php
   ```

2. Login with super_admin credentials

3. Verify dashboard loads:
   ```
   https://3dprint-omsk.ru/admin/index.php
   ```

**Expected:**
- [ ] Login successful, no errors
- [ ] Dashboard displays statistics cards (total orders, revenue, etc.)
- [ ] Charts render correctly (Chart.js)
- [ ] Recent orders list shows latest entries
- [ ] Navigation sidebar visible with all module links

### 7.2 Services Module

**Location:** `/admin/services.php`

**Test CRUD Operations:**

1. **Create:**
   - Click **"Add Service"** button
   - Fill form:
     - Name: QA Test Service
     - Description: Testing service creation
     - Price: 1000
     - Active: ✓
   - Click **Save**
   - **Expected:** Success notification, service appears in table

2. **Read:**
   - Verify new service in list
   - Click **View** or **Edit**
   - **Expected:** Service details display correctly

3. **Update:**
   - Click **Edit** on QA Test Service
   - Change price: 1500
   - Click **Save**
   - **Expected:** Success notification, price updated in table

4. **Delete:**
   - Click **Delete** on QA Test Service
   - Confirm deletion
   - **Expected:** Confirmation modal, service removed from list

**Verify:**
- [ ] All CRUD operations succeed
- [ ] Success notifications appear
- [ ] Table updates without page reload
- [ ] No console errors
- [ ] SSE event broadcast (check network tab for SSE `content_changed` event)

### 7.3 Portfolio Module

**Location:** `/admin/portfolio.php`

**Test Image Upload:**

1. Click **"Add Project"** button
2. Fill form:
   - Title: QA Test Project
   - Description: Testing portfolio with image upload
   - Category: Industrial
   - Featured: ✓
3. Upload test image (JPEG/PNG, <5MB)
4. Click **Save**

**Expected:**
- [ ] Image upload progress indicator
- [ ] Image preview after upload
- [ ] Project created with image_path populated
- [ ] Image accessible at `/storage/uploads/portfolio/{filename}`
- [ ] Success notification

**Test Featured Toggle:**

1. Click **Feature** button on any project
2. Verify featured badge appears/disappears
3. Check public portfolio page
4. **Expected:** Featured projects appear first

**Delete Test Project:**

1. Click **Delete** on QA Test Project
2. **Expected:** Image file also deleted from storage

### 7.4 Forms Builder Module

**Location:** `/admin/forms.php`

**Test Form Builder:**

1. Click **"Create Form"**
2. Set form name: QA Test Form
3. Set slug: qa-test-form
4. Add fields via drag-and-drop or buttons:
   - **Text Field:** Name (required)
   - **Email Field:** Email (required, email validation)
   - **Textarea:** Message (optional)
5. Configure validation rules on each field
6. Set notification settings:
   - Telegram: ✓ Enabled
   - Email: ✓ Enabled, recipients: `qa@example.com`
7. Click **Save Form**

**Expected:**
- [ ] Form created successfully
- [ ] Fields appear in correct order
- [ ] Validation rules saved
- [ ] Notification settings persisted

**Test Form Preview:**

1. Click **Preview** button
2. Fill out form in preview modal
3. Click **Submit**
4. **Expected:** Validation works, submission creates entry

**Test Conditional Logic (if implemented):**

1. Add conditional field (e.g., show field B if field A = "yes")
2. Test in preview
3. **Expected:** Field visibility toggles based on conditions

### 7.5 Orders Module

**Location:** `/admin/orders.php`

**Test Order Listing:**

1. Verify orders table displays
2. Test filters:
   - Status: New, Processing, Completed
   - Date range: Last 7 days
   - Search: Enter email from Step 5 (`qa-test@example.com`)
3. **Expected:** Filters work, results update

**Test Order Details:**

1. Click **View** on an order
2. Verify modal/page shows:
   - Customer info (name, email, phone)
   - Order details (type, calculator data if applicable)
   - Status history timeline
   - Internal notes section
3. **Expected:** All data displays correctly

**Test Status Change:**

1. Change order status: New → Processing
2. Add comment: "QA test status change"
3. Click **Update Status**
4. **Expected:**
   - Success notification
   - Status updates in table
   - Status history logged (check `order_status_history` table)
   - Telegram/Email notification sent (if configured)

**Test Internal Notes:**

1. Click **Add Note** button
2. Enter note: "QA test note - order verified"
3. Save note
4. **Expected:**
   - Note appears in notes list
   - Timestamp and admin name displayed
   - Note persisted in `order_notes` table

**Test Export:**

1. Select multiple orders (checkboxes)
2. Click **Export to CSV**
3. **Expected:**
   - CSV file downloads
   - Contains selected orders with all fields
   - UTF-8 encoding (opens correctly in Excel)

**Test Archiving:**

1. Click **Archive** on a completed order
2. **Expected:**
   - Order moved to archived view
   - Marked with `archived_at` timestamp
   - Still accessible via filters

### 7.6 Settings Module

**Location:** `/admin/settings.php`

**Test Tabs:**

Visit each settings tab and verify:

1. **Contacts Tab:**
   - Update phone: +7 913 999-9999
   - Save
   - **Expected:** Success notification, audit logged

2. **Social Media Tab:**
   - Update Telegram link
   - Save
   - **Expected:** Changes persisted

3. **SEO Tab:**
   - Update meta description
   - Save
   - **Expected:** Changes reflected on public pages

4. **Email Tab (SMTP):**
   - Verify SMTP settings
   - Click **Test Email** button
   - **Expected:** Test email sent, success/failure notification

5. **Telegram Tab:**
   - Verify bot token and chat ID
   - Click **Test Telegram** button
   - **Expected:** Test message received in Telegram, success notification

6. **Logging Tab:**
   - Set log level: debug
   - Save
   - **Expected:** Settings applied

7. **Cache Tab:**
   - Toggle cache enabled
   - Set TTL: 600 seconds
   - Save
   - **Expected:** Cache behavior updates

**Test Audit History:**

1. Click **View Audit History** button
2. Verify modal shows recent changes:
   - Setting key
   - Old value → New value
   - Admin user who made change
   - Timestamp
3. **Expected:** All test changes logged

### 7.7 Calculator Settings Module

**Location:** `/admin/calculator-settings.php`

**Test Configuration Tabs:**

1. **Materials Tab:**
   - Add new material: "QA Test Material", price: 50
   - Save
   - **Expected:** Material added to list

2. **Services Tab:**
   - Update service price
   - Save
   - **Expected:** Price updated

3. **Quality Multipliers Tab:**
   - Adjust multiplier values
   - Save
   - **Expected:** Values persisted

4. **Formulas Tab:**
   - View formula editor
   - Test calculation with sandbox
   - **Expected:** Formula validates, test calculation runs

**Test Calculator Sync:**

1. Make changes in admin calculator settings
2. Open public calculator page: `/calculator.html`
3. Verify changes reflected (may need cache clear)
4. **Expected:** Public calculator uses updated config

### 7.8 Audit Logs Module

**Location:** `/admin/audit.php`

**Test Log Viewing:**

1. Verify audit logs table displays
2. Test filters:
   - User: Select your admin user
   - Action: Create, Update, Delete
   - Entity: Service, Order, Setting
   - Date range: Today
3. **Expected:** Filters work, logs display all test actions from previous steps

**Test Stats Dashboard:**

1. Verify stats cards show:
   - Total logs
   - Today's activity
   - Rate limit violations
   - Unique IPs
2. **Expected:** Counts match activity

**Test Details Modal:**

1. Click **View Details** on a log entry
2. Verify modal shows:
   - Full payload JSON
   - User info
   - IP address
   - User agent
   - Timestamps
3. **Expected:** All metadata present

**Test Export:**

1. Apply filters (e.g., last 7 days)
2. Click **Export CSV**
3. **Expected:** CSV downloads with filtered logs

**Test Cleanup:**

1. Click **Cleanup Old Logs** button
2. Set retention: 90 days
3. Confirm
4. **Expected:** Logs older than 90 days deleted, success notification

### 7.9 Users Module (super_admin only)

**Location:** `/admin/users.php`

**Test User Management:**

1. Click **"Create User"** button
2. Fill form:
   - Email: qa-test-admin@example.com
   - Name: QA Test Admin
   - Role: editor
   - Password: TestPass123!
   - Status: active
3. Click **Create**
4. **Expected:** User created, appears in table

**Test Edit User:**

1. Click **Edit** on QA Test Admin
2. Change role: editor → admin
3. Save
4. **Expected:** Role updated, success notification

**Test Password Reset:**

1. Click **Edit** on QA Test Admin
2. Enter new password
3. Save
4. **Expected:** Password changed, user's sessions cleared

**Test Delete User:**

1. Click **Delete** on QA Test Admin
2. Confirm deletion
3. **Expected:** User deleted, cannot login with old credentials

### 7.10 Verification Checklist (All Modules)

- [ ] **All modules load** - No 404 or 500 errors
- [ ] **CRUD operations work** - Create, Read, Update, Delete all succeed
- [ ] **Success notifications** - Appear after each action
- [ ] **Tables update dynamically** - No page reload needed
- [ ] **Modals function** - Open, close, submit correctly
- [ ] **Forms validate** - Required fields enforced, proper error messages
- [ ] **File uploads work** - Images upload to correct storage location
- [ ] **Search/filtering** - All filter controls function correctly
- [ ] **Pagination** - Works if table has >20 entries
- [ ] **Audit logging** - All actions logged to `admin_action_logs`
- [ ] **SSE broadcasts** - Content changes trigger SSE events
- [ ] **No console errors** - JavaScript console clean
- [ ] **Mobile responsive** - Test on mobile/tablet device or DevTools responsive mode

### 7.11 Troubleshooting

| Issue | Solution |
|-------|----------|
| **Module won't load** | Check PHP error log, verify includes and autoloader |
| **CRUD fails silently** | Open DevTools Network tab, check API response |
| **No notifications** | Verify `window.showNotification()` defined in admin-main.js |
| **Upload fails** | Check storage permissions: `chmod 755 storage/uploads` |
| **Search doesn't work** | Check API endpoint accepts search parameter |
| **Audit logs empty** | Verify `logAdminAction()` called in controllers |
| **SSE not broadcasting** | Check `invalidateResourceCache()` called on write ops |

---

## Step 8: Cross-Module Integration

**Purpose:** Verify modules work together correctly with data flowing between them.

### 8.1 Form → Order → Status Flow

**Full end-to-end workflow:**

1. **Public side:** Submit calculator order (already done in Step 5)
2. **Admin Orders:** Find new order, change status to "Processing"
3. **Admin Audit:** Verify status change logged
4. **Database:** Check `order_status_history` has new entry
5. **Telegram/Email:** Verify notification sent

**Expected:**
- [ ] Order appears in Orders module
- [ ] Status change persists to database
- [ ] Status history logged with admin attribution
- [ ] Audit log entry created (action: status_change)
- [ ] Notification sent if configured

### 8.2 Settings → Frontend Sync

**Test settings propagation:**

1. **Admin Settings:** Update contact phone to `+7 913 123-4567`
2. **Public Homepage:** Navigate to `/index.html`
3. **Browser Console:** Check settings-loader.js output
4. **Verify:** Phone number in footer updates automatically

**Expected:**
- [ ] Settings change saved
- [ ] Settings API returns new value
- [ ] Frontend loader fetches updated settings
- [ ] UI updates with new phone (may need page reload)
- [ ] Audit log entry created

### 8.3 Calculator Settings → Calculator Page

**Test calculator config sync:**

1. **Admin Calculator Settings:** Add new material "QA Material", price 75
2. **Clear cache:** In admin, trigger cache clear (or wait 5 min)
3. **Public Calculator:** Navigate to `/calculator.html`
4. **Verify:** New material appears in materials dropdown

**Expected:**
- [ ] Material saved in calculator_settings
- [ ] Calculator config API returns new material
- [ ] Frontend calculator loads updated config
- [ ] New material selectable and price calculated correctly

### 8.4 Content Edit → SSE → Cache Invalidation

**Test real-time content sync:**

1. **Browser Window A:** Open `/services.html`, leave open with console visible
2. **Browser Window B:** Login to admin, navigate to `/admin/services.php`
3. **Window B:** Edit any service (change name)
4. **Window A Console:** Observe SSE event logged
5. **Window A UI:** Verify service list updates without manual refresh

**Expected:**
- [ ] Admin edit saves successfully
- [ ] SSE event broadcast with type `content_changed`
- [ ] SyncClient receives event in Window A
- [ ] Cache invalidated for `services:list`
- [ ] Fresh data fetched from API
- [ ] UI updates dynamically

### 8.5 User Roles → Module Access

**Test role-based access:**

1. **Create editor user:**
   ```bash
   php scripts/create-admin.php \
     qa-editor@example.com \
     "QA Editor" \
     EditorPass123 \
     editor \
     active
   ```

2. **Logout super_admin**, **login as editor**

3. **Verify access:**
   - ✓ Can access: Orders, Services, Portfolio, Forms, Submissions
   - ❌ Cannot access: Users, Audit Logs (if configured)

4. **Test limitations:**
   - Try to access `/admin/users.php` directly
   - **Expected:** Redirect or 403 Forbidden

### 8.6 Rate Limiting Across Endpoints

**Test rate limiter:**

1. **Run rapid API calls:**
   ```bash
   for i in {1..70}; do
     curl -s https://3dprint-omsk.ru/api/services.php > /dev/null
     echo "Request $i sent"
   done
   ```

2. **Expected after ~60 requests:**
   - HTTP 429 Too Many Requests
   - Response: `{"success": false, "error": "Rate limit exceeded"}`
   - Console log: Rate limit violation logged to `admin_action_logs`

3. **Wait 1 minute, retry:**
   - **Expected:** Requests succeed again

### 8.7 Backup → Restore → Verify

**Test data integrity:**

1. **Create backup:**
   ```bash
   php database/backup.php --verify
   ```
   - **Expected:** Backup file created in `storage/backups/`
   - Checksum validated

2. **Make test changes:**
   - Delete a service in admin panel
   - Note the service ID

3. **Restore backup:**
   ```bash
   mysql -u user -p database_name < storage/backups/backup_20240115_140000.sql
   ```

4. **Verify restoration:**
   - **Expected:** Deleted service reappears in admin
   - Database state matches backup timestamp

### 8.8 Verification Checklist

- [ ] **Form submissions create orders** - Data flows from public forms to Orders module
- [ ] **Settings propagate to frontend** - Changes in admin reflected on public pages
- [ ] **Calculator config syncs** - Admin changes update public calculator
- [ ] **Real-time sync works** - Admin edits trigger frontend cache invalidation via SSE
- [ ] **Role-based access enforced** - Editors cannot access super_admin-only modules
- [ ] **Rate limiting global** - Applies across all API endpoints consistently
- [ ] **Backups restore correctly** - Data integrity preserved through backup/restore cycle
- [ ] **Audit logs complete** - All cross-module actions logged with full context

---

## Sign-Off Criteria

Before declaring the system "synced and production-ready," all of the following must be verified:

### Critical (Must Pass)

- [ ] **Database schema complete** - All 18 tables exist with correct structure
- [ ] **API smoke test 100% pass** - All endpoints respond correctly
- [ ] **Zero console errors** - No critical JavaScript errors on any page
- [ ] **Form submissions persist** - Data flows from public forms to database
- [ ] **Admin CRUD functional** - All modules allow create, read, update, delete
- [ ] **Authentication works** - Login/logout, session management, CSRF protection
- [ ] **Cache system operational** - IndexedDB stores data, TTL respected, invalidation works
- [ ] **SSE stream connected** - Real-time sync functional, events broadcast
- [ ] **No data loss** - Backup/restore cycle preserves data integrity

### Important (Should Pass)

- [ ] **Real-time sync responsive** - Admin changes reflect on frontend within 5 seconds
- [ ] **Notifications working** - Telegram/Email notifications sent for orders and status changes
- [ ] **File uploads functional** - Images upload, store, and display correctly
- [ ] **Rate limiting enforced** - Excessive requests blocked, violations logged
- [ ] **Audit logs complete** - All admin actions logged with full metadata
- [ ] **Mobile responsive** - All pages usable on mobile devices
- [ ] **Performance acceptable** - Lighthouse scores meet targets, page loads <3s

### Nice to Have (Optional)

- [ ] **Analytics tracking** - Google Analytics/Yandex.Metrica configured
- [ ] **SEO optimized** - Meta tags, sitemap, robots.txt correct
- [ ] **Email templates** - Branded email notifications
- [ ] **Advanced calculator features** - Complex formulas, discounts, bulk pricing
- [ ] **Multi-language** - (if applicable)

---

## Troubleshooting

### Common Issues Matrix

| Symptom | Possible Cause | Solution |
|---------|----------------|----------|
| **Database audit fails** | Missing tables, wrong credentials | Run `php scripts/provision-database.php --import-only` |
| **API smoke test fails** | Services down, wrong URL | Verify web server running, check URL, test with `curl` |
| **IndexedDB empty** | JS load order wrong | Ensure `cache-manager.js` loads before `database.js` |
| **SSE disconnects frequently** | Server timeout, buffering | Update Nginx/Apache config per `docs/WEB_SERVER_CONFIG.md` |
| **Forms don't submit** | CSRF token missing, API down | Check FormService, verify CSRF in API request |
| **Admin shows blank pages** | PHP errors, missing includes | Check PHP error log: `tail -f storage/logs/php_error.log` |
| **No notifications** | Telegram/SMTP not configured | Test with `/api/telegram-test.php` and `/api/email-test.php` |
| **Cache never invalidates** | SSE not broadcasting | Verify `invalidateResourceCache()` called in controllers |
| **Orders missing calculator data** | Calculator mapping not configured | Check FormService calculator_mapping in form settings |
| **Rate limit violations** | DDoS, bot traffic | Review `admin_action_logs`, block IPs in firewall |

### Debug Mode

Enable verbose logging for troubleshooting:

**Frontend (Browser Console):**

```javascript
// Enable debug logging
localStorage.setItem('debug_cache', 'true');
localStorage.setItem('debug_sse', 'true');
localStorage.setItem('debug_api', 'true');

// Reload page
location.reload();

// To disable:
localStorage.removeItem('debug_cache');
localStorage.removeItem('debug_sse');
localStorage.removeItem('debug_api');
```

**Backend (PHP):**

Edit `.env`:

```
APP_DEBUG=true
LOG_LEVEL=debug
```

**Warning:** Disable debug mode in production after troubleshooting.

### Getting Help

If issues persist after troubleshooting:

1. **Collect diagnostics:**
   - PHP error log: `tail -n 100 storage/logs/php_error.log`
   - Database audit: `php scripts/db_audit.php > db_audit_report.txt`
   - API smoke test: `php scripts/api_smoke.php --url=<url> --admin-email=<email> --admin-password=<pass> > api_smoke_report.txt`
   - Browser console: Copy full console output including errors
   - Network tab: Export HAR file (DevTools → Network → right-click → Save as HAR)

2. **Review documentation:**
   - Architecture: `docs/DB_SYNC_ANALYSIS.md`
   - API: `docs/API_REFERENCE.md`, `docs/CONTENT_API_V2.md`
   - Security: `docs/SECURITY.md`
   - Troubleshooting: `docs/TROUBLESHOOTING.md`

3. **Check known issues:**
   - GitHub Issues (if applicable)
   - Change log / release notes
   - Recent commits affecting relevant modules

---

## QA Sign-Off Template

Copy and complete this template after executing checklist:

```
=== QA DATABASE SYNC SIGN-OFF ===

Date: __________________
Tester: __________________
Environment: [ ] Production [ ] Staging [ ] Development
Base URL: __________________

=== RESULTS ===

[ ] Step 1: Database Diagnostics - PASS / FAIL
[ ] Step 2: API Health Check - PASS / FAIL
[ ] Step 3: Frontend Caching - PASS / FAIL
[ ] Step 4: SSE Real-Time Sync - PASS / FAIL
[ ] Step 5: Form Submissions - PASS / FAIL
[ ] Step 6: Browser Console - PASS / FAIL
[ ] Step 7: Admin Panel Testing - PASS / FAIL
[ ] Step 8: Cross-Module Integration - PASS / FAIL

=== CRITICAL ISSUES ===

Issue 1: __________________
  Severity: [ ] Critical [ ] High [ ] Medium [ ] Low
  Status: [ ] Resolved [ ] In Progress [ ] Blocked

Issue 2: __________________
  Severity: [ ] Critical [ ] High [ ] Medium [ ] Low
  Status: [ ] Resolved [ ] In Progress [ ] Blocked

=== SIGN-OFF ===

Overall Status: [ ] APPROVED [ ] APPROVED WITH ISSUES [ ] REJECTED

Tester Signature: __________________
Date: __________________

Notes:
__________________________________________
__________________________________________
__________________________________________
```

---

## Related Documentation

- **[TESTING.md](TESTING.md)** - Automated testing guide (PHPUnit, smoke tests)
- **[DB_SYNC_ANALYSIS.md](DB_SYNC_ANALYSIS.md)** - Full-stack architecture analysis
- **[API_REFERENCE.md](API_REFERENCE.md)** - Complete API documentation
- **[CONTENT_SYNC_SSE.md](CONTENT_SYNC_SSE.md)** - Real-time sync technical guide
- **[ADMIN_GUIDE.md](ADMIN_GUIDE.md)** - Admin panel user guide
- **[PRODUCTION_RUNBOOK.md](PRODUCTION_RUNBOOK.md)** - Deployment and operations guide
- **[TROUBLESHOOTING.md](TROUBLESHOOTING.md)** - Common issues and solutions
- **[SECURITY.md](SECURITY.md)** - Security best practices and validation

---

**Checklist Version:** 1.0  
**Last Updated:** January 2024  
**Maintained By:** 3D Print Pro Development Team
