# Admin Session Quick Start Guide

## TL;DR - What Changed?

**Before:** Admin login used session name `3DPRINT_ADMIN_SESSION`, but API endpoints used default `PHPSESSID` → **API returned 401 even after successful login**

**After:** Both admin pages and API endpoints now use `3DPRINT_ADMIN_SESSION` via shared bootstrap → **API requests work correctly with authentication**

## Files to Know

### Core Files
```
includes/admin-session.php           ← NEW: Shared session bootstrap
admin/includes/session-config.php    ← UPDATED: Uses shared bootstrap
api/helpers/admin_auth.php           ← UPDATED: Uses shared bootstrap
```

### How to Use

#### ✅ In Admin Pages
```php
<?php
define('ADMIN_INIT', true);
require_once __DIR__ . '/includes/session-config.php';  // Auto-includes bootstrap
require_once __DIR__ . '/includes/auth.php';

Auth::require();  // Redirect to login if not authenticated
```

#### ✅ In API Endpoints
```php
<?php
require_once __DIR__ . '/helpers/admin_auth.php';  // Auto-includes bootstrap

// For read operations (GET)
requireAdminAuth();

// For write operations (POST/PUT/DELETE)
requireAdminAuthWithCsrf();
```

#### ❌ DON'T Do This
```php
// WRONG: Manual session_start() without bootstrap
session_start();  // Uses PHPSESSID - won't see admin session!

// WRONG: Including bootstrap twice
require_once 'includes/admin-session.php';
require_once 'admin/includes/session-config.php';  // Already includes it

// WRONG: Setting session name manually
ini_set('session.name', '3DPRINT_ADMIN_SESSION');  // Use bootstrap instead
```

## Testing Your Changes

### 1. Quick Visual Test (Browser)
```javascript
// After login, check cookie name in DevTools → Application → Cookies
// Should see: 3DPRINT_ADMIN_SESSION (not PHPSESSID)

// Test authenticated API request
fetch('/api/orders.php')
  .then(r => r.json())
  .then(console.log)
// Expected: 200 OK with data (not 401)
```

### 2. Run Automated Test
```bash
php scripts/test-admin-session-sync.php
# Should see: ✅ All tests passed!
```

### 3. Manual Endpoint Test
```bash
# Login first (save cookies)
curl -c cookies.txt -X POST http://localhost/admin/login-handler.php \
  -d "login=admin&password=yourpass&csrf_token=..."

# Test authenticated API request
curl -b cookies.txt http://localhost/api/orders.php
# Expected: {"success":true,"data":[...]} (not 401)
```

## Common Issues

### Issue: Still Getting 401 After Login

**Causes:**
1. Browser cached old cookies → Clear cookies and login again
2. Session files not writable → Check permissions on session.save_path
3. Multiple PHP versions → Ensure all use same session storage
4. HTTPS mismatch → Check Secure flag matches your protocol

**Debug:**
```php
// Add to api/orders.php temporarily:
error_log('Session name: ' . session_name());         // Should be: 3DPRINT_ADMIN_SESSION
error_log('Session ID: ' . session_id());
error_log('Authenticated: ' . ($_SESSION['ADMIN_AUTHENTICATED'] ?? 'NO'));
```

### Issue: CSRF Validation Fails

**Causes:**
1. Token not sent in header → Check X-CSRF-Token header
2. Session regenerated → Refresh page to get new token
3. Different session scope → Fixed by unified bootstrap

**Debug:**
```javascript
// Check token in browser console:
console.log('CSRF Token:', window.ADMIN_SESSION?.csrfToken);
console.log('Session Cookie:', document.cookie);
```

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                  Unified Session Flow                        │
└─────────────────────────────────────────────────────────────┘

    Admin Page                         API Endpoint
        │                                   │
        ▼                                   ▼
┌──────────────────┐              ┌──────────────────┐
│ session-config   │              │ admin_auth.php   │
└──────────────────┘              └──────────────────┘
        │                                   │
        └───────────────┬───────────────────┘
                        ▼
              ┌──────────────────┐
              │ admin-session.php│ ← Shared Bootstrap
              └──────────────────┘
                        │
                        ▼
              ┌──────────────────┐
              │ Session Started  │
              │ Name: 3DPRINT... │
              │ Settings: Secure │
              └──────────────────┘
                        │
                        ▼
              ┌──────────────────┐
              │ Same $_SESSION   │ ✅ Unified!
              │ Same CSRF Token  │
              │ Same Auth State  │
              └──────────────────┘
```

## Best Practices

### ✅ DO
- Use shared bootstrap via session-config.php or admin_auth.php
- Check authentication before accessing protected resources
- Validate CSRF tokens for all write operations
- Update LAST_ACTIVITY on every request
- Use HTTPS in production

### ❌ DON'T
- Call session_start() directly without bootstrap
- Hardcode session name in multiple places
- Skip CSRF validation for POST/PUT/DELETE
- Expose session ID in URLs or logs
- Use HTTP in production (Secure flag won't work)

## Migration Checklist

If you're adding a new admin page or API endpoint:

- [ ] Admin page includes `admin/includes/session-config.php`
- [ ] Admin page calls `Auth::require()` at the top
- [ ] API endpoint includes `api/helpers/admin_auth.php`
- [ ] API GET calls `requireAdminAuth()`
- [ ] API POST/PUT/DELETE calls `requireAdminAuthWithCsrf()`
- [ ] Test authenticated request returns 200 (not 401)
- [ ] Test unauthenticated request returns 401
- [ ] Test missing CSRF returns 403 (not 401)

## Further Reading

- Full documentation: `docs/ADMIN_SESSION_SYNC.md`
- Authentication system: `docs/ADMIN_AUTHENTICATION.md`
- API documentation: `docs/API_UNIFIED_REST.md`
- Test suite: `scripts/test-admin-session-sync.php`

## Quick Reference

### Session Settings (via Bootstrap)
- **Name:** `3DPRINT_ADMIN_SESSION`
- **HttpOnly:** Yes (JS cannot access)
- **SameSite:** Lax (CSRF protection)
- **Secure:** Yes (HTTPS only, auto-detected)
- **Timeout:** 30 minutes inactivity
- **ID Regen:** Every 15 minutes (admin pages)

### Auth Functions
- `requireAdminAuth()` - Check authentication (401 if not)
- `verifyCsrfToken()` - Check CSRF token (403 if invalid)
- `requireAdminAuthWithCsrf()` - Both checks combined
- `Auth::check()` - Returns bool (no exit)
- `Auth::require()` - Redirects to login (for pages)
- `Auth::login($username)` - Set authenticated state
- `Auth::logout()` - Clear session and destroy

### HTTP Status Codes
- `200` - Success (authenticated, valid request)
- `401` - Unauthorized (not logged in or session expired)
- `403` - Forbidden (logged in but CSRF invalid)
- `422` - Validation Error (authenticated but bad data)

---

**Last Updated:** 2025-01-13  
**Version:** 1.0.0  
**Status:** Production Ready ✅
