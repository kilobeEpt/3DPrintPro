# Admin API Session Authentication Fix

## Problem

The admin panel loaded successfully and showed "✅ Admin panel initialized", but all API requests to endpoints like `/api/orders.php`, `/api/form-submissions.php`, etc., returned the error:

```
"No session found. Please log in."
```

This indicated that the session was being lost when transitioning between `/admin/` pages and `/api/` endpoints.

## Root Cause

The issue was that **API endpoints were not loading the admin session configuration** before starting the session.

### Session Name Mismatch

In PHP, the session cookie name is controlled by `ini_set('session.name', ...)` or the `session.name` ini setting. The admin panel pages were using the custom session name `3DPRINT_ADMIN_SESSION` (configured in `includes/admin-session.php`), but API endpoints that used `/api/bootstrap.php` were not loading this configuration, causing them to use PHP's default session name `PHPSESSID`.

**Result**: The browser sent the `3DPRINT_ADMIN_SESSION` cookie, but the API endpoints were looking for `PHPSESSID`, so they couldn't find the session data.

### Cookie Path and Security Settings

Additionally, the API endpoints were not applying the same security settings for session cookies:
- HttpOnly (prevents JavaScript access)
- SameSite=Lax (prevents CSRF attacks)
- Secure (HTTPS only)
- Use strict mode
- Use only cookies (no URL parameters)

## Solution

### 1. Modified `/api/bootstrap.php`

Added the admin session configuration **at the very beginning** of the bootstrap file, before any other includes:

```php
// CRITICAL: Load admin session configuration FIRST before any session_start() calls
// This ensures all API endpoints use the same session name and cookie settings as admin pages
require_once __DIR__ . '/../includes/admin-session.php';
```

This ensures that:
- The session name is set to `3DPRINT_ADMIN_SESSION` before any `session_start()` call
- All session cookie security settings are applied consistently
- API endpoints and admin pages share the same session

### 2. Modified `/api/helpers/admin_auth.php`

Added a conditional check to avoid double-loading the admin session configuration:

```php
// Load admin session config if not already loaded (for backward compatibility)
if (!defined('ADMIN_SESSION_NAME')) {
    require_once __DIR__ . '/../../includes/admin-session.php';
}
```

This maintains backward compatibility for API endpoints that load `admin_auth.php` directly without using `bootstrap.php`.

## How It Works Now

### Authentication Flow

1. **User logs in** via `/admin/login.php`
   - Session is created with name `3DPRINT_ADMIN_SESSION`
   - Session cookie is sent to browser with security flags
   - Session data includes: `ADMIN_AUTHENTICATED`, `ADMIN_USER_ID`, `CSRF_TOKEN`, etc.

2. **User makes API request** to `/api/orders.php`
   - Browser automatically sends `3DPRINT_ADMIN_SESSION` cookie (due to `credentials: 'include'` in fetch)
   - `orders.php` loads `/api/bootstrap.php`
   - `bootstrap.php` loads `admin-session.php` which configures session name to `3DPRINT_ADMIN_SESSION`
   - `OrderController` calls `requireAdminAuth()`
   - `requireAdminAuth()` calls `session_start()` which now uses the correct session name
   - PHP reads the `3DPRINT_ADMIN_SESSION` cookie and restores session data
   - `AdminAuthService` validates the session and user
   - Request succeeds! ✅

### Session Configuration Applied

From `includes/admin-session.php`, the following settings are applied to all API endpoints:

```php
ini_set('session.name', '3DPRINT_ADMIN_SESSION');
ini_set('session.cookie_httponly', 1);      // JavaScript cannot access
ini_set('session.cookie_samesite', 'Lax');  // CSRF protection
ini_set('session.cookie_secure', 1);        // HTTPS only (if detected)
ini_set('session.gc_maxlifetime', 1800);    // 30 minutes
ini_set('session.cookie_lifetime', 0);      // Until browser closes
ini_set('session.use_only_cookies', 1);     // No URL parameters
ini_set('session.use_strict_mode', 1);      // Stronger security
```

## Files Modified

1. **`/api/bootstrap.php`**
   - Added `require_once __DIR__ . '/../includes/admin-session.php';` at the beginning
   - Updated documentation comment

2. **`/api/helpers/admin_auth.php`**
   - Added conditional check for `ADMIN_SESSION_NAME` before loading admin-session.php
   - Prevents double-loading and maintains backward compatibility

## Testing

### Automatic Verification

Run the test script to verify the fix:

```bash
php test-api-session.php
```

This tests:
1. ✅ Admin session configuration is loaded
2. ✅ Session name is set correctly to `3DPRINT_ADMIN_SESSION`
3. ✅ API bootstrap preserves session name
4. ✅ Session cookie security settings are applied
5. ✅ Session data can be set and retrieved
6. ✅ Admin auth helper functions are available

### Manual Verification

1. **Login to admin panel**: `https://3dprint-omsk.ru/admin/login.php`
2. **Open Developer Tools** → Network tab
3. **Navigate to any admin page** (e.g., Orders, Services)
4. **Check API requests**:
   - All requests to `/api/*` should have status `200 OK`
   - No "No session found" errors
   - Cookies tab shows `3DPRINT_ADMIN_SESSION` cookie being sent
   - Response data loads correctly

### Browser Console Checks

Open the browser console on any admin page and run:

```javascript
// Check that API client is initialized
console.log('Admin API ready:', !!window.adminApi);

// Test an API call
window.adminApi.getOrders({limit: 5}).then(
    orders => console.log('✅ Orders loaded:', orders.length),
    error => console.error('❌ Error:', error)
);

// Check session cookie
document.cookie.split(';').forEach(c => {
    if (c.includes('3DPRINT_ADMIN_SESSION')) {
        console.log('✅ Session cookie present:', c.trim());
    }
});
```

Expected output:
```
Admin API ready: true
✅ Session cookie present: 3DPRINT_ADMIN_SESSION=<session_id>
✅ Orders loaded: 5
```

## API Endpoints Affected

All API endpoints now share the same session configuration:

### Using bootstrap.php (Fixed automatically)
- `/api/orders.php` → `OrderController`
- `/api/services.php` → `ServiceController`
- `/api/portfolio.php` → `PortfolioController`
- `/api/testimonials.php` → `TestimonialController`
- `/api/faq.php` → `FAQController`
- `/api/content.php` → `ContentBlockController`
- `/api/calculator-settings.php` → `CalculatorSettingsController`
- `/api/admin/users.php` → `AdminUserController`
- `/api/admin/audit-logs.php` (uses bootstrap.php + admin_auth.php)

### Loading admin_auth.php directly (Fixed via conditional check)
- `/api/settings.php`
- `/api/forms.php`
- `/api/form-fields.php`
- `/api/form-submissions.php`
- `/api/email-test.php`
- `/api/telegram-test.php`

## Session Security Features

All API endpoints now benefit from these security features:

1. **Custom Session Name**: `3DPRINT_ADMIN_SESSION` (harder to guess than default `PHPSESSID`)
2. **HttpOnly Cookie**: JavaScript cannot access session cookie (XSS protection)
3. **SameSite=Lax**: Prevents CSRF attacks while allowing top-level navigation
4. **Secure Flag**: Cookie only sent over HTTPS (when HTTPS is detected)
5. **Use Only Cookies**: Session ID not passed in URLs (prevents session fixation)
6. **Strict Mode**: Prevents session adoption attacks
7. **30-Minute Timeout**: Automatic logout after inactivity
8. **Session Regeneration**: ID regenerated every 15 minutes (from `admin/includes/session-config.php`)

## Related Files

- **Session Configuration**: `/includes/admin-session.php`
- **Admin Session Logic**: `/admin/includes/session-config.php`
- **API Bootstrap**: `/api/bootstrap.php`
- **Auth Helpers**: `/api/helpers/admin_auth.php`
- **API Client**: `/js/api-client.js` (sends `credentials: 'include'`)
- **Admin API Client**: `/admin/js/admin-api-client.js` (wraps API client, adds CSRF)

## Session Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│ Admin Login (admin/login.php)                                   │
├─────────────────────────────────────────────────────────────────┤
│ 1. Load includes/admin-session.php                              │
│    → Set session name: 3DPRINT_ADMIN_SESSION                    │
│    → Configure security settings                                │
│ 2. Start session: session_start()                               │
│ 3. Validate credentials                                         │
│ 4. Create session in admin_sessions table                       │
│ 5. Set $_SESSION data                                           │
│ 6. Browser receives cookie: 3DPRINT_ADMIN_SESSION=<id>          │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ Admin Page (admin/index.php, admin/orders.php, etc.)           │
├─────────────────────────────────────────────────────────────────┤
│ 1. Load includes/admin-session.php                              │
│ 2. Start session: session_start()                               │
│ 3. Browser sends cookie: 3DPRINT_ADMIN_SESSION=<id>             │
│ 4. Session data is restored                                     │
│ 5. Page renders with user data                                  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ JavaScript API Call (fetch to /api/orders.php)                 │
├─────────────────────────────────────────────────────────────────┤
│ 1. fetch('/api/orders.php', {credentials: 'include'})           │
│ 2. Browser sends cookie: 3DPRINT_ADMIN_SESSION=<id>             │
│ 3. Browser sends header: X-CSRF-Token: <token>                  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ API Endpoint (api/orders.php)                                   │
├─────────────────────────────────────────────────────────────────┤
│ 1. Load api/bootstrap.php                                       │
│    → Load includes/admin-session.php                            │
│    → Set session name: 3DPRINT_ADMIN_SESSION (MATCHES!)         │
│ 2. OrderController->handle()                                    │
│    → requireAdminAuth()                                         │
│    → session_start() (reads 3DPRINT_ADMIN_SESSION cookie)       │
│    → Session data found and validated ✅                        │
│ 3. Process request                                              │
│ 4. Return JSON response                                         │
└─────────────────────────────────────────────────────────────────┘
```

## Prevention

To prevent this issue in the future:

1. **Always use `/api/bootstrap.php`** for new API endpoints
2. **If loading helpers directly**, ensure `admin-session.php` is loaded first
3. **Test session persistence** after any authentication changes
4. **Document session configuration** in all new auth-related code

## Rollback

If this fix causes any issues, rollback by reverting these two files:

```bash
git checkout HEAD -- api/bootstrap.php api/helpers/admin_auth.php
```

However, this will restore the original bug, so proper debugging is recommended instead.

## Related Documentation

- **Admin Login Fix**: `ADMIN_LOGIN_FIX.md` - Previous session persistence fix for login redirects
- **RBAC Authentication**: `docs/RBAC_AUTHENTICATION.md` - Complete authentication system documentation
- **Security Guide**: `docs/SECURITY.md` - Session security best practices
- **Admin Guide**: `docs/ADMIN_GUIDE.md` - Admin panel usage and features

---

**Fix Date**: 2024
**Status**: ✅ Deployed and Verified
**Impact**: All admin API requests now work correctly with session authentication
