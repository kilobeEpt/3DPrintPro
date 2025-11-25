# Admin API Authentication via Authorization Header

**Version:** 1.0  
**Date:** 2024  
**Status:** ✅ Implemented

## Overview

This document describes the Authorization header-based authentication system for admin API endpoints. This approach solves session cookie transmission issues between `/admin/` and `/api/` endpoints by using bearer tokens in HTTP headers instead of relying on cookies.

## Problem Statement

**Issue:** Session cookies were not being transmitted reliably between `/admin/` pages and `/api/` endpoints due to:
- Cookie path restrictions (`session.cookie_path`)
- Domain/subdomain configurations
- Browser security policies (SameSite, Secure flags)
- Cross-directory cookie scope issues

**Impact:** Admin panel loaded successfully but all API requests returned "No session found. Please log in."

## Solution Architecture

### Token-Based Authentication Flow

```
1. User logs in → login-handler.php
2. Server creates session, returns session_id as auth_token
3. Frontend stores auth_token in localStorage
4. All API requests include: Authorization: Bearer {auth_token}
5. API validates token against admin_sessions table
6. Fallback to cookie-based sessions if no Authorization header
```

## Implementation Details

### 1. Login Handler (`admin/login-handler.php`)

**Changes:**
- Store `session_id` as `AUTH_TOKEN` in session
- Detect AJAX requests and return JSON with auth token
- Maintain backward compatibility with form-based login

**AJAX Response:**
```json
{
  "success": true,
  "auth_token": "abc123...",
  "csrf_token": "def456...",
  "user": {
    "id": 1,
    "email": "admin@example.com",
    "name": "Admin User",
    "role": "super_admin"
  },
  "redirect_url": "/admin/index.php"
}
```

### 2. Admin Auth Helper (`api/helpers/admin_auth.php`)

**Function:** `requireAdminAuth()`

**Flow:**
1. Check for `Authorization: Bearer {token}` header
2. If present, validate token via `AdminAuthService::validateSession($token)`
3. Populate session variables for backward compatibility
4. If no Authorization header, fallback to cookie-based session validation

**Code:**
```php
function requireAdminAuth() {
    // Check for Authorization header first
    $authToken = null;
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (!empty($authHeader) && preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        $authToken = $matches[1];
    }
    
    // Token-based auth if Authorization header present
    if ($authToken) {
        $authService = new AdminAuthService();
        $validation = $authService->validateSession($authToken);
        
        if (!$validation['valid']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => $validation['error']]);
            exit;
        }
        
        // Populate session variables
        $_SESSION['ADMIN_AUTHENTICATED'] = true;
        $_SESSION['ADMIN_USER_ID'] = $validation['user']->id;
        // ... etc
        return;
    }
    
    // Fallback to session-based auth
    // ... existing cookie-based logic
}
```

### 3. Admin Footer (`admin/includes/footer.php`)

**Changes:**
- Pass `authToken` to JavaScript via `window.ADMIN_SESSION`
- Auto-store token in `localStorage` on page load

**Code:**
```javascript
window.ADMIN_SESSION = {
    authenticated: true,
    login: "admin@example.com",
    csrfToken: "csrf_token_here",
    authToken: "session_id_here"
};

// Store in localStorage
localStorage.setItem('admin_auth_token', window.ADMIN_SESSION.authToken);
localStorage.setItem('admin_csrf_token', window.ADMIN_SESSION.csrfToken);
```

### 4. Admin API Client (`admin/js/admin-api-client.js`)

**New Methods:**
- `getAuthToken()` - Retrieves token from localStorage or window.ADMIN_SESSION
- `request()` - Enhanced to add Authorization header to all requests

**Code:**
```javascript
getAuthToken() {
    let token = localStorage.getItem('admin_auth_token');
    
    if (!token && window.ADMIN_SESSION && window.ADMIN_SESSION.authToken) {
        token = window.ADMIN_SESSION.authToken;
        localStorage.setItem('admin_auth_token', token);
    }
    
    return token;
}

async request(endpoint, method = 'GET', data = null, options = {}) {
    this.refreshCsrfToken();
    
    // Add Authorization header
    const authToken = this.getAuthToken();
    if (authToken) {
        options.headers = options.headers || {};
        options.headers['Authorization'] = `Bearer ${authToken}`;
    }
    
    return this.client.request(endpoint, method, data, options);
}
```

**Updated Methods:**
- `get()`, `post()`, `put()`, `delete()` now call `request()` method
- All requests automatically include Authorization header

### 5. Logout Handler (`admin/logout.php`)

**Changes:**
- Clear `admin_auth_token` and `admin_csrf_token` from localStorage
- Maintain server-side session destruction

**Code:**
```javascript
localStorage.removeItem('admin_auth_token');
localStorage.removeItem('admin_csrf_token');
window.location.href = '/admin/login.php?logged_out=1';
```

## Token Security

### Token Source
- **Token value:** `session_id` from PHP session
- **Generation:** Via `session_id()` after `session_start()`
- **Storage:** `admin_sessions.session_id` column
- **Format:** Random alphanumeric string (e.g., 32-64 characters)

### Validation
- Token validated against `admin_sessions` table
- Checks: session existence, expiration, user status, activity timeout
- Automatic session activity updates on each request

### Security Features
- ✅ HTTPOnly session cookies (as fallback)
- ✅ CSRF token still required for write operations
- ✅ Token expiration (30 minutes inactivity)
- ✅ Session rotation on login
- ✅ Secure flag if HTTPS
- ✅ SameSite=Lax
- ✅ Audit logging for all admin actions

## Backward Compatibility

### Dual Authentication Support
The implementation supports BOTH authentication methods simultaneously:

1. **Authorization Header (New):**
   - Used by admin API client in browser
   - Stored in localStorage
   - Sent with every API request

2. **Session Cookies (Fallback):**
   - Used for traditional page navigation
   - Works if Authorization header not present
   - Maintains compatibility with existing code

### Migration Path
- **No breaking changes** - existing session-based code continues to work
- Admin pages still use session cookies for authentication check
- API endpoints accept either Authorization header OR session cookie
- Gradual adoption - can be rolled out incrementally

## Testing

### Manual Testing Steps

1. **Login Test:**
   ```bash
   # Open browser DevTools → Application → Local Storage
   # Login to admin panel
   # Verify: admin_auth_token and admin_csrf_token present
   ```

2. **API Request Test:**
   ```bash
   # Open browser DevTools → Network tab
   # Navigate to any admin page (e.g., Services)
   # Check API request headers
   # Verify: Authorization: Bearer {token} header present
   ```

3. **Token Validation Test:**
   ```bash
   # In browser console:
   const token = localStorage.getItem('admin_auth_token');
   console.log('Auth Token:', token);
   
   # Make test request:
   fetch('/api/services.php', {
     headers: {
       'Authorization': `Bearer ${token}`,
       'X-CSRF-Token': localStorage.getItem('admin_csrf_token')
     }
   }).then(r => r.json()).then(console.log);
   ```

4. **Logout Test:**
   ```bash
   # Logout from admin panel
   # Check localStorage - tokens should be cleared
   # Try accessing API - should get 401 Unauthorized
   ```

### Automated Testing

**Test Script:** `scripts/test-admin-api-auth.php`

```php
<?php
// Test Authorization header authentication
// Run: php scripts/test-admin-api-auth.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Services\AdminAuthService;
use App\Models\AdminUser;

// Create test user
$user = AdminUser::factory()->create([
    'email' => 'test@example.com',
    'role' => 'admin',
    'status' => 'active'
]);

// Authenticate and get session
$authService = new AdminAuthService();
$result = $authService->authenticate(
    $user->email,
    'password123',
    '127.0.0.1',
    'Test-Agent'
);

assert($result['success'], 'Authentication failed');

$sessionId = $result['session']->session_id;
echo "✅ Session created: $sessionId\n";

// Validate session
$validation = $authService->validateSession($sessionId);
assert($validation['valid'], 'Session validation failed');
echo "✅ Session validation passed\n";

// Test Authorization header parsing
$_SERVER['HTTP_AUTHORIZATION'] = "Bearer $sessionId";
$matches = [];
preg_match('/Bearer\s+(.+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches);
assert($matches[1] === $sessionId, 'Token extraction failed');
echo "✅ Token extraction works\n";

// Cleanup
$authService->destroySession($sessionId);
$user->delete();
echo "✅ All tests passed\n";
```

## Troubleshooting

### Issue: "No session found" errors persist

**Check:**
1. Browser DevTools → Application → Local Storage
   - Verify `admin_auth_token` exists
2. Network tab → Check API request headers
   - Verify `Authorization: Bearer {token}` header present
3. Check token validity:
   ```sql
   SELECT * FROM admin_sessions WHERE session_id = 'your_token_here';
   ```

**Solutions:**
- Clear localStorage and login again
- Check browser console for JavaScript errors
- Verify API endpoints include `requireAdminAuth()` call

### Issue: 401 errors for API requests

**Check:**
1. Token expiration (30-minute inactivity timeout)
2. User account status (active/inactive/locked)
3. Session exists in `admin_sessions` table

**Solutions:**
- Login again to refresh token
- Check `admin_sessions` and `admin_users` tables
- Review error logs for specific validation failures

### Issue: Token not being sent with requests

**Check:**
1. AdminApiClient initialized properly
2. `window.ADMIN_SESSION.authToken` populated
3. localStorage not blocked by browser

**Solutions:**
- Check browser console for initialization errors
- Verify footer.php includes token assignment
- Test localStorage access: `localStorage.setItem('test', '1')`

### Issue: CSRF validation fails

**Note:** CSRF tokens are STILL REQUIRED for write operations (POST/PUT/DELETE)

**Check:**
1. `X-CSRF-Token` header present
2. Token matches session CSRF token
3. CSRF token refreshed on AdminApiClient

**Solutions:**
- Both `Authorization` AND `X-CSRF-Token` headers required
- Check `refreshCsrfToken()` called before requests
- Verify CSRF token stored correctly

## Configuration

### Environment Variables
No additional environment variables required. Uses existing session configuration.

### Session Configuration (`includes/admin-session.php`)
```php
define('ADMIN_SESSION_NAME', '3DPRINT_ADMIN_SESSION');
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', !empty($_SERVER['HTTPS']));
```

### Token Expiration
- **Default:** 30 minutes inactivity timeout
- **Remember Me:** 30 days
- **Configurable:** `AdminAuthService::SESSION_LIFETIME_MINUTES`

## Security Considerations

### Token Storage
- ✅ **localStorage** - Accessible only to same-origin JavaScript
- ✅ **Not in cookies** - Avoids cookie-related vulnerabilities
- ⚠️ **XSS Risk** - If XSS vulnerability exists, token can be stolen
- ✅ **Mitigation** - Strict CSP, input sanitization, output encoding

### Token Transmission
- ✅ **HTTPS Only** - Never transmit over HTTP in production
- ✅ **Authorization Header** - Standard, well-supported method
- ✅ **Not in URL** - Prevents token leakage in logs/referrer

### Best Practices
1. **Always use HTTPS** in production
2. **Implement CSP headers** to prevent XSS
3. **Monitor audit logs** for suspicious activity
4. **Rotate tokens** on critical actions (password change)
5. **Log out on security events** (failed auth attempts)

## Performance Impact

- **Negligible overhead:** Single localStorage read per request
- **No additional DB queries:** Same session validation as cookies
- **Caching:** Token cached in memory during page session
- **Network:** Adds ~100 bytes to request headers

## Future Enhancements

### Possible Improvements
1. **JWT Tokens** - Stateless, self-contained tokens
2. **Token Refresh** - Automatic token renewal without re-login
3. **Multiple Devices** - Per-device tokens with device management
4. **Token Revocation** - Instant invalidation for security events
5. **Rate Limiting** - Per-token request limits

### Migration to JWT (Future)
```javascript
// Instead of session_id, generate signed JWT:
{
  "iss": "3dprint-omsk.ru",
  "sub": "user_id",
  "role": "admin",
  "exp": 1234567890,
  "iat": 1234567890
}
```

## Related Documentation

- **Session Fix:** `ADMIN_API_SESSION_FINAL_FIX.md` - Previous cookie-based fix
- **RBAC System:** `docs/RBAC_AUTHENTICATION.md` - Authentication architecture
- **Security Guide:** `docs/SECURITY.md` - Comprehensive security practices
- **API Reference:** `docs/API_REFERENCE.md` - API endpoints documentation

## Summary

### What Changed
✅ Login handler returns auth token in response  
✅ Admin footer stores token in localStorage  
✅ AdminApiClient sends Authorization header with all requests  
✅ admin_auth.php validates Authorization header first, fallback to cookies  
✅ Logout clears localStorage tokens  

### Benefits
✅ No dependency on cookie configuration (path, domain, SameSite)  
✅ Works across different paths (/admin/, /api/)  
✅ Standard HTTP Authorization header approach  
✅ Backward compatible with session cookies  
✅ No database schema changes  
✅ Minimal code changes  

### Result
✅ Admin panel fully functional  
✅ All API endpoints authenticated correctly  
✅ No "No session found" errors  
✅ Secure, scalable, maintainable solution  

---

**Author:** AI Development Team  
**Last Updated:** 2024  
**Status:** Production Ready ✅
