# Admin Login CSRF and Redirect Fix

## Problem Summary

When attempting to log into the admin panel, after submitting the login form, the system redirected to a blank page. No PHP error logs were generated, indicating the issue was in the redirect logic or session persistence.

## Root Cause Analysis

The issue was caused by **session data not being written before redirect headers were sent**. In PHP, session data is typically written at the end of script execution. However, when a redirect (`header('Location: ...')`) is issued immediately, the session data may not be persisted to disk before the redirect occurs.

This resulted in:
1. Login credentials validated successfully
2. Session variables set (`$_SESSION['ADMIN_AUTHENTICATED']`, etc.)
3. Redirect sent to `/admin/index.php`
4. **Session data lost** before being written
5. Admin panel checks authentication, finds no session data
6. Either redirects back to login (circular redirect) or shows blank page

## Additional Issues Found

1. **No output buffering** - Any accidental output before headers would cause "headers already sent" errors
2. **CSRF validation redirect** - Also needed session write before redirect
3. **Missing diagnostic logging** - Made troubleshooting difficult

## Solution Implemented

### 1. Output Buffering (`login-handler.php`)
```php
// Start output buffering to prevent any accidental output before headers
ob_start();
```

### 2. Session Write Before Redirect
Added `session_write_close()` before **all** redirect points:

**Success redirect:**
```php
// CRITICAL: Write session data before redirect
session_write_close();

// Clean output buffer and send redirect
ob_end_clean();
header('Location: ' . $redirectUrl);
exit;
```

**Error redirects (rate limit, empty credentials, auth failure, exceptions):**
```php
$_SESSION['LOGIN_ERROR'] = 'Error message';
session_write_close();
ob_end_clean();
header('Location: /admin/login.php');
exit;
```

**CSRF validation redirect (`includes/csrf.php`):**
```php
$_SESSION['LOGIN_ERROR'] = 'Invalid CSRF token. Please refresh the page and try again.';
session_write_close();
header('Location: /admin/login.php');
exit;
```

### 3. Debug Logging
Added comprehensive logging to `/tmp/login-debug.log`:
- Session ID tracking
- CSRF token comparison (session vs POST)
- Authentication flow steps
- Redirect URLs
- Exception details with stack traces

### 4. Enhanced Error Handling
All error paths now:
- Set appropriate session error message
- Close session (write to disk)
- Clean output buffer
- Send redirect header
- Exit immediately

## Files Modified

1. **admin/login-handler.php**
   - Added output buffering at start
   - Added `session_write_close()` before all redirects
   - Added `ob_end_clean()` before all redirects
   - Added comprehensive debug logging

2. **admin/includes/csrf.php**
   - Added `session_write_close()` before CSRF failure redirect
   - Added debug logging for CSRF validation

3. **admin/login.php**
   - Added debug logging for CSRF token generation

## Testing

### Manual Testing
1. Visit `/admin/login.php`
2. Enter valid admin credentials
3. Submit form
4. Verify redirect to `/admin/index.php`
5. Verify admin panel loads with authenticated session

### Debug Log Analysis
Check `/tmp/login-debug.log` for flow:
```
[2024-01-01 12:00:00] Login page loaded. Session ID: abc123...
[2024-01-01 12:00:00] CSRF Token generated: def456...
[2024-01-01 12:00:05] Login handler started
[2024-01-01 12:00:05] POST request confirmed
[2024-01-01 12:00:05] Rate limit check passed
[2024-01-01 12:00:05] CSRF verification passed
[2024-01-01 12:00:05] Starting authentication
[2024-01-01 12:00:05] Auth success, user ID: 1, email: admin@example.com
[2024-01-01 12:00:05] Session vars set. Session ID: abc123...
[2024-01-01 12:00:05] Redirecting to: /admin/index.php
[2024-01-01 12:00:05] Session closed, now redirecting
```

### Automated Testing
Run test script:
```bash
php test-login-flow.php
```

## Debug Log Cleanup

After verifying the fix works, you can remove the debug logging:

1. Remove debug logging from `admin/login-handler.php`
2. Remove debug logging from `admin/includes/csrf.php`
3. Remove debug logging from `admin/login.php`
4. Delete `/tmp/login-debug.log`

**KEEP** the following critical fixes:
- Output buffering (`ob_start()` at the beginning)
- `session_write_close()` before redirects
- `ob_end_clean()` before redirects

## Session Best Practices

### Always Call `session_write_close()` Before:
- Redirects (`header('Location: ...')`)
- Long-running operations
- Making external HTTP requests

### Why This Matters:
1. **Session File Locking**: PHP locks the session file during `session_start()` to prevent concurrent access
2. **Write Delay**: Session data is written at script end or when `session_write_close()` is called
3. **Race Conditions**: Without explicit close, redirects may complete before session write
4. **Data Loss**: Subsequent requests may not see newly set session variables

## Related Documentation

- Session configuration: `admin/includes/session-config.php`
- Session bootstrap: `includes/admin-session.php`
- Authentication service: `app/Services/AdminAuthService.php`
- CSRF protection: `docs/SECURITY.md`

## Prevention

To prevent similar issues in the future:

1. **Always use output buffering** in handler scripts
2. **Always call `session_write_close()`** before redirects
3. **Always clean output buffer** before sending headers
4. **Add error logging** for troubleshooting
5. **Test session persistence** after authentication changes

## Rollback Instructions

If this fix causes issues, revert commits and:

1. Check session cookie settings (secure, httponly, samesite)
2. Verify session directory is writable (`/var/lib/php/sessions` or custom)
3. Check for session.save_handler configuration
4. Review web server session timeout settings
5. Test with session.cookie_lifetime = 0 (browser session only)
