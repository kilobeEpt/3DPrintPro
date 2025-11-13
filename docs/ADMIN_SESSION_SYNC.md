# Admin Session Synchronization

## Overview

This document describes the unified session management system that ensures admin login pages and API endpoints share the same PHP session cookie, enabling authenticated API requests.

## Problem Statement

**Before the fix:**
- Admin login flow used custom session name `3DPRINT_ADMIN_SESSION` (configured in `admin/includes/session-config.php`)
- API authentication used default PHP session name `PHPSESSID` (via `session_start()` in `api/helpers/admin_auth.php`)
- Result: API endpoints couldn't read the authenticated admin session and returned `401 Unauthorized` for all requests, even after successful login
- CSRF tokens stored in the admin session were also inaccessible to the API layer

## Solution Architecture

### Shared Session Bootstrap

Created a centralized session configuration file that both admin pages and API endpoints include:

**File:** `includes/admin-session.php`

**Purpose:**
- Defines `ADMIN_SESSION_NAME` constant (`3DPRINT_ADMIN_SESSION`)
- Configures secure session settings via `ini_set()` before `session_start()`
- Provides `bootstrapAdminSession()` function for explicit initialization
- Auto-bootstraps when included (idempotent)

**Security settings applied:**
- Custom session name (prevents default `PHPSESSID` collision)
- HttpOnly cookies (JavaScript cannot access)
- SameSite=Lax (CSRF protection)
- Secure flag (HTTPS only, auto-detected)
- 30-minute garbage collection timeout
- Browser-session cookie lifetime (0)
- Cookies-only (no URL session IDs)
- Strict session mode

### Integration Points

#### 1. Admin Pages (`admin/includes/session-config.php`)
- Includes `includes/admin-session.php` to bootstrap session settings
- Starts session with configured name
- Implements activity timeout logic (30 minutes)
- Implements session fixation protection (ID regeneration every 15 minutes)

#### 2. API Endpoints (`api/helpers/admin_auth.php`)
- Includes `includes/admin-session.php` to bootstrap session settings
- `requireAdminAuth()` function:
  - Starts session with configured name
  - Checks session timeout (30 minutes)
  - Validates authentication flags
  - Updates `LAST_ACTIVITY` timestamp
  - Returns 401 if not authenticated or expired
- `verifyCsrfToken()` function:
  - Reads from same session (CSRF tokens now accessible)
  - Returns 403 if token invalid
- `requireAdminAuthWithCsrf()` function:
  - Combines both checks for write operations

## Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                     User Login Flow                         │
└─────────────────────────────────────────────────────────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │  admin/login.php             │
              │  (Login form)                │
              └──────────────────────────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │  admin/login-handler.php     │
              │  • Includes session-config   │
              │  • Verifies credentials      │
              │  • Calls Auth::login()       │
              └──────────────────────────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │  admin/includes/session-     │
              │  config.php                  │
              │  • Includes admin-session    │
              │  • Starts session            │
              │  • Sets timeout logic        │
              └──────────────────────────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │  includes/admin-session.php  │
              │  • Defines session name      │
              │  • Sets ini_set() config     │
              │  • Returns control           │
              └──────────────────────────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │  Session created:            │
              │  Name: 3DPRINT_ADMIN_SESSION │
              │  Cookie: HttpOnly, Secure    │
              │  $_SESSION['ADMIN_AUTHEN...']│
              └──────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                  API Request Flow (After Login)             │
└─────────────────────────────────────────────────────────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │  Browser sends request to    │
              │  GET /api/orders.php         │
              │  Cookie: 3DPRINT_ADMIN_...   │
              └──────────────────────────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │  api/orders.php              │
              │  • Includes admin_auth.php   │
              │  • Calls requireAdminAuth()  │
              └──────────────────────────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │  api/helpers/admin_auth.php  │
              │  • Includes admin-session    │
              │  • Starts session            │
              │  • Checks authentication     │
              └──────────────────────────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │  includes/admin-session.php  │
              │  • Configures session name   │
              │  • Returns control           │
              └──────────────────────────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │  Session read:               │
              │  Name: 3DPRINT_ADMIN_SESSION │
              │  $_SESSION['ADMIN_AUTHEN...']│
              │  Status: ✅ Authenticated    │
              └──────────────────────────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │  Returns 200 OK              │
              │  {success: true, data: [...]}│
              └──────────────────────────────┘
```

## Files Modified

### 1. Created: `includes/admin-session.php` (NEW)
```php
// Shared session bootstrap for admin pages and API endpoints
define('ADMIN_SESSION_NAME', '3DPRINT_ADMIN_SESSION');
function bootstrapAdminSession() { /* ... */ }
bootstrapAdminSession();
```

### 2. Updated: `admin/includes/session-config.php`
**Before:**
```php
ini_set('session.name', '3DPRINT_ADMIN_SESSION');
// ... all security settings ...
session_start();
```

**After:**
```php
require_once __DIR__ . '/../../includes/admin-session.php';
session_start();
// ... timeout and fixation logic ...
```

### 3. Updated: `api/helpers/admin_auth.php`
**Before:**
```php
function requireAdminAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start(); // Uses default PHPSESSID
    }
    // ...
}
```

**After:**
```php
require_once __DIR__ . '/../../includes/admin-session.php';

function requireAdminAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start(); // Uses 3DPRINT_ADMIN_SESSION
    }
    // Added: session timeout check
    // ...
}
```

## Testing

### Automated Tests

Run the test script:
```bash
php scripts/test-admin-session-sync.php
```

**Tests performed:**
1. ✅ Shared bootstrap file exists
2. ✅ ADMIN_SESSION_NAME constant defined
3. ✅ Admin session-config includes bootstrap
4. ✅ API admin_auth includes bootstrap
5. ✅ No duplicate ini_set calls
6. ✅ Bootstrap function exists
7. ✅ Session timeout in API layer
8. ✅ CSRF validation uses shared session

### Manual Testing

#### Test 1: Verify Session Cookie Name
1. Open browser DevTools → Application → Cookies
2. Log in at `/admin/login.php`
3. **Expected:** Cookie named `3DPRINT_ADMIN_SESSION` (not `PHPSESSID`)

#### Test 2: Authenticated API Request (Success)
1. Log in to admin panel
2. Open browser console
3. Run:
   ```javascript
   fetch('/api/orders.php')
     .then(r => r.json())
     .then(console.log)
   ```
4. **Expected:** `200 OK` with `{success: true, data: [...]}`
5. **Before fix:** `401 Unauthorized`

#### Test 3: Unauthenticated API Request (Fail)
1. Log out or open incognito window
2. Run same fetch command
3. **Expected:** `401 Unauthorized` with `{success: false, error: "Authentication required..."}`

#### Test 4: CSRF Token Validation
1. Log in to admin panel
2. Open browser console
3. Run (POST without CSRF token):
   ```javascript
   fetch('/api/services.php', {
     method: 'POST',
     headers: {'Content-Type': 'application/json'},
     body: JSON.stringify({name: 'Test'})
   }).then(r => r.json()).then(console.log)
   ```
4. **Expected:** `403 Forbidden` with `{success: false, error: "Invalid CSRF token..."}`
5. **Before fix:** `401 Unauthorized` (couldn't read session)

#### Test 5: Session Timeout
1. Log in to admin panel
2. Wait 31 minutes (or adjust `$timeout` in code for faster testing)
3. Make API request
4. **Expected:** `401` with `{error: "Session expired due to inactivity..."}`

#### Test 6: Valid CSRF Request
1. Log in to admin panel
2. Open browser console
3. Get CSRF token: `window.ADMIN_SESSION.csrfToken`
4. Run:
   ```javascript
   fetch('/api/services.php', {
     method: 'PUT',
     headers: {
       'Content-Type': 'application/json',
       'X-CSRF-Token': window.ADMIN_SESSION.csrfToken
     },
     body: JSON.stringify({id: 1, name: 'Updated'})
   }).then(r => r.json()).then(console.log)
   ```
5. **Expected:** `200 OK` (if service exists) or validation error (not 401/403)

## Backward Compatibility

✅ **No breaking changes:**
- Admin pages continue to work as before
- Session name remains `3DPRINT_ADMIN_SESSION`
- Session timeout (30 minutes) unchanged
- Session fixation protection (15-minute ID regeneration) unchanged
- Login rate limiting (5 attempts, 15-minute lockout) unchanged
- All existing security settings preserved

## Security Considerations

### ✅ Improvements
- **Session timeout enforced in API layer:** Previously only checked in admin pages, now also enforced in `requireAdminAuth()`
- **Unified session configuration:** Eliminates risk of mismatched settings between admin and API
- **No code duplication:** Single source of truth for session settings

### ✅ Maintained
- **HttpOnly cookies:** JavaScript cannot access session
- **SameSite=Lax:** CSRF protection for top-level navigation
- **Secure flag:** HTTPS-only in production (auto-detected)
- **CSRF token validation:** Works correctly now that API can read session
- **Session fixation protection:** ID regeneration every 15 minutes
- **Activity timeout:** 30 minutes of inactivity

### 🔒 Best Practices
- Never expose session ID in URLs
- Always validate CSRF tokens for write operations (POST/PUT/DELETE)
- Use HTTPS in production to enable Secure flag
- Monitor failed login attempts (rate limiting in place)
- Log out users after 30 minutes of inactivity

## Troubleshooting

### Issue: API still returns 401 after login

**Possible causes:**
1. Browser cache - clear cookies and try again
2. HTTPS mismatch - ensure Secure flag matches protocol
3. Session files not writable - check PHP session.save_path permissions
4. Multiple PHP versions - ensure all use same session storage

**Debug steps:**
```php
// In api/orders.php (temporarily add):
error_log('Session name: ' . session_name());
error_log('Session ID: ' . session_id());
error_log('Session data: ' . print_r($_SESSION, true));
```

### Issue: CSRF validation always fails

**Possible causes:**
1. Token not included in request header
2. Token expired (session regenerated)
3. Session not accessible

**Debug steps:**
```javascript
// In browser console:
console.log('CSRF Token:', window.ADMIN_SESSION.csrfToken);
console.log('Session cookie:', document.cookie);
```

### Issue: Session expires too quickly

**Possible causes:**
1. PHP garbage collection aggressive
2. Shared hosting session cleanup
3. Multiple requests not updating LAST_ACTIVITY

**Solution:**
- Increase `session.gc_maxlifetime` in bootstrap
- Use database session storage (not file-based)
- Ensure all API endpoints call `requireAdminAuth()` (updates timestamp)

## Performance Considerations

### Session Storage
- **Default:** File-based (`/tmp` or PHP session.save_path)
- **Recommended for production:** Database or Redis session handler
- **Benefit:** Better concurrency, no file I/O bottlenecks

### Caching
- Session settings applied once per request (via `ini_set()`)
- No performance impact from shared bootstrap
- Session ID regeneration (every 15 min) has negligible overhead

## Future Enhancements

### Potential Improvements
1. **Database session storage:** Store sessions in MySQL for better scaling
2. **Remember me functionality:** Persistent login tokens
3. **Multiple admin users:** User table with roles/permissions
4. **Session activity log:** Track all admin actions
5. **API token authentication:** Alternative to session cookies for programmatic access
6. **WebSocket support:** Real-time session expiration notifications

### Migration Path (if needed)
```php
// Example: Custom session handler
class DatabaseSessionHandler implements SessionHandlerInterface {
    public function read($id) { /* ... */ }
    public function write($id, $data) { /* ... */ }
    // ... implement other methods
}

session_set_save_handler(new DatabaseSessionHandler());
```

## References

- [PHP Session Security](https://www.php.net/manual/en/session.security.php)
- [OWASP Session Management](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
- [SameSite Cookie Attribute](https://web.dev/samesite-cookies-explained/)

## Changelog

### v1.0.0 (Current)
- ✅ Created shared session bootstrap (`includes/admin-session.php`)
- ✅ Unified session name across admin pages and API endpoints
- ✅ Added session timeout enforcement in API layer
- ✅ CSRF tokens now accessible from API context
- ✅ Eliminated code duplication in session configuration
- ✅ Comprehensive test suite and documentation

---

**Status:** ✅ Production Ready  
**Last Updated:** 2025-01-13  
**Reviewed By:** Development Team  
