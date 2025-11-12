# Admin Authentication System

Complete PHP session-based authentication system with CSRF protection for the 3D Print Pro admin panel.

## Architecture Overview

The authentication system consists of:

1. **PHP Session Management** - Secure session handling with HttpOnly cookies, SameSite protection
2. **CSRF Token Protection** - Token-based protection for all state-changing operations
3. **Database-backed Credentials** - Hashed passwords stored in settings table
4. **Login Rate Limiting** - Brute force protection with account lockout
5. **API Endpoint Protection** - Authentication required for admin-only endpoints

## File Structure

```
admin/
├── login.php              # Login form page
├── login-handler.php      # Login POST handler
├── logout.php             # Logout handler
├── index.php              # Protected admin dashboard
└── includes/
    ├── session-config.php # Session security settings
    ├── auth.php           # Auth middleware & helpers
    └── csrf.php           # CSRF token management

api/
└── helpers/
    └── admin_auth.php     # API auth helpers

scripts/
└── setup-admin-credentials.php  # CLI tool to set credentials
```

## Setup Instructions

### 1. Initialize Admin Credentials

Run the CLI script to set up initial admin credentials:

```bash
# Interactive mode
php scripts/setup-admin-credentials.php

# Non-interactive mode
php scripts/setup-admin-credentials.php admin MySecurePassword123
```

This will:
- Hash the password using PHP's `password_hash()`
- Store `admin_login` and `admin_password_hash` in the `settings` table
- Display confirmation and security warnings

### 2. Access Admin Panel

Navigate to: `https://your-site.com/admin/login.php`

Enter the credentials you set up in step 1.

### 3. Verify Protection

Try accessing `/admin/index.php` directly - you should be redirected to login.

## Authentication Features

### Session Security

**Session Configuration** (`admin/includes/session-config.php`):
- Custom session name (`3DPRINT_ADMIN_SESSION`)
- HttpOnly cookies (JavaScript cannot access)
- SameSite=Lax (CSRF protection)
- Secure flag for HTTPS (auto-detected)
- 30-minute inactivity timeout
- Session ID regeneration every 15 minutes
- Automatic session expiration handling

### Login Rate Limiting

**Brute Force Protection** (`admin/login-handler.php`):
- Maximum 5 failed attempts
- 15-minute lockout after exceeding limit
- Attempts reset after successful login
- Session-based tracking (per client)

### CSRF Protection

**Token Management** (`admin/includes/csrf.php`):
- Tokens generated per session
- 32-byte random token (cryptographically secure)
- Validated using timing-attack-safe comparison (`hash_equals`)
- Token regenerated after successful login
- Supported via:
  - Hidden form fields (`csrf_token`)
  - HTTP headers (`X-CSRF-Token`)
  - Meta tags for JavaScript

### Auth Middleware

**Protection Helpers** (`admin/includes/auth.php`):

```php
// Require authentication (redirect to login if not authenticated)
Auth::require('/admin/login.php');

// Require authentication for API (JSON error if not authenticated)
Auth::requireApi();

// Log in a user
Auth::login($username);

// Log out a user
Auth::logout();

// Check if authenticated
if (Auth::check()) {
    // User is authenticated
}

// Get current user
$user = Auth::user();
```

### API Protection

**Endpoint Protection** (`api/helpers/admin_auth.php`):

```php
// Require admin authentication (401 if not authenticated)
requireAdminAuth();

// Verify CSRF token (403 if invalid)
verifyCsrfToken();

// Require both auth and CSRF token
requireAdminAuthWithCsrf();
```

## Protected Endpoints

### Admin-Only Endpoints (All Operations)

- `api/settings.php` - GET/POST/PUT/DELETE (all require auth)

### Admin-Only Write Operations

- `api/services.php` - POST/PUT/DELETE require auth (GET is public)
- `api/portfolio.php` - POST/PUT/DELETE require auth (GET is public)
- `api/testimonials.php` - POST/PUT/DELETE require auth (GET is public)
- `api/faq.php` - POST/PUT/DELETE require auth (GET is public)
- `api/content.php` - POST/PUT/DELETE require auth (GET is public)
- `api/orders.php` - GET/PUT/DELETE require auth (POST is public for form submissions)

### Public Endpoints

- `api/orders.php` - POST (for public form submissions)
- All GET endpoints (except orders and settings)

## Integration with Frontend

### Admin Dashboard (`admin/index.php`)

PHP session data is passed to JavaScript:

```php
<script>
    window.ADMIN_SESSION = {
        authenticated: true,
        login: <?php echo json_encode($adminLogin); ?>,
        csrfToken: <?php echo json_encode($csrfToken); ?>
    };
</script>
```

### API Client (`js/api-client.js`)

CSRF token automatically included in all API requests:

```javascript
// Automatically adds X-CSRF-Token header to requests
if (window.ADMIN_SESSION && window.ADMIN_SESSION.csrfToken) {
    fetchOptions.headers['X-CSRF-Token'] = window.ADMIN_SESSION.csrfToken;
}
```

### Admin JavaScript (`admin/js/admin.js`)

Updated to work with PHP sessions:

```javascript
// Check authentication from PHP session
if (window.ADMIN_SESSION && window.ADMIN_SESSION.authenticated) {
    this.currentUser = {
        login: window.ADMIN_SESSION.login,
        name: 'Администратор',
        authenticated: true
    };
}

// Logout redirects to PHP handler
logout() {
    if (confirm('Вы уверены, что хотите выйти?')) {
        window.location.href = '/admin/logout.php';
    }
}
```

## Security Best Practices

### Production Checklist

- [x] Passwords hashed with `password_hash()` (bcrypt)
- [x] CSRF tokens validated on all state-changing operations
- [x] HttpOnly cookies prevent XSS cookie theft
- [x] SameSite cookies prevent CSRF attacks
- [x] Secure flag for HTTPS deployments
- [x] Session timeout after 30 minutes inactivity
- [x] Login rate limiting (5 attempts, 15-minute lockout)
- [x] Session ID regeneration on login and periodically
- [x] SQL injection protection (PDO prepared statements)
- [x] XSS protection (htmlspecialchars with ENT_QUOTES)

### Recommendations

1. **Strong Passwords**: Use at least 12 characters with mixed case, numbers, and symbols
2. **HTTPS Only**: Deploy with SSL/TLS certificate (Let's Encrypt)
3. **Regular Updates**: Change admin password periodically
4. **Monitor Access**: Check login attempts and session activity
5. **Backup Credentials**: Store credentials securely offline
6. **Unique Password**: Never reuse passwords from other services
7. **IP Whitelist**: Consider restricting admin access to specific IPs (hosting panel)
8. **Two-Factor Auth**: Consider adding 2FA in the future

## Troubleshooting

### Cannot Login

**Problem**: Invalid credentials error
**Solutions**:
1. Verify credentials in database: `SELECT * FROM settings WHERE setting_key IN ('admin_login', 'admin_password_hash');`
2. Reset credentials: `php scripts/setup-admin-credentials.php`
3. Check for typos in username (case-sensitive)

### Session Expired Immediately

**Problem**: Session not persisting across page loads
**Solutions**:
1. Check if cookies are enabled in browser
2. Verify `session.save_path` is writable: `php -i | grep session.save_path`
3. Check hosting panel for session configuration
4. Ensure HTTPS if `session.cookie_secure` is enabled

### CSRF Token Invalid

**Problem**: 403 error on form submissions
**Solutions**:
1. Refresh the admin page to get a new token
2. Check if cookies are blocked by browser/extension
3. Verify `session_start()` is called before token generation
4. Check for session timeout (30 minutes)

### Locked Out After Failed Attempts

**Problem**: "Too many login attempts" error
**Solutions**:
1. Wait 15 minutes for automatic unlock
2. Clear browser cookies and retry
3. Use different browser/incognito mode
4. Contact system administrator if persistent

### API Returns 401 Unauthorized

**Problem**: Admin API calls fail with 401
**Solutions**:
1. Check if logged in: visit `/admin/login.php`
2. Verify session not expired (30 minutes)
3. Check browser console for `window.ADMIN_SESSION`
4. Verify CSRF token is being sent in requests

## Migration from Old System

### Changes from Previous Auth

**Before** (localStorage-based):
- Credentials: Hardcoded in JavaScript (`admin` / `admin123`)
- Storage: Browser localStorage
- Security: Minimal (client-side only)
- CSRF: Not protected
- Sessions: No server-side state

**After** (PHP session-based):
- Credentials: Hashed in database
- Storage: Secure PHP sessions
- Security: Industry-standard (server-side)
- CSRF: Full protection
- Sessions: 30-minute timeout, regeneration, lockout

### Breaking Changes

1. **Old `admin.html` deprecated**: Use `/admin/index.php` instead
2. **Logout function changed**: No longer `admin.logout()`, now redirects to `/admin/logout.php`
3. **API requires auth**: Admin operations need valid session
4. **CSRF tokens required**: All POST/PUT/DELETE need CSRF token
5. **Credentials in database**: No longer hardcoded in code

## API Reference

### Login Handler (`/admin/login-handler.php`)

**Method**: POST  
**Content-Type**: `application/x-www-form-urlencoded`

**Request**:
```
login=admin
password=secretpassword
csrf_token=abc123...
```

**Success Response** (302 Redirect):
- Location: `/admin/index.php` or intended URL
- Session cookie set

**Error Response** (302 Redirect):
- Location: `/admin/login.php?error=1`
- Error message in session

**Rate Limit**:
- 5 attempts per session
- 15-minute lockout after exceeding limit

### Logout Handler (`/admin/logout.php`)

**Method**: GET  
**Authentication**: Required (session)

**Response** (302 Redirect):
- Location: `/admin/login.php?logged_out=1`
- Session destroyed, cookies cleared

## Development Notes

### Testing Authentication

```bash
# Test login flow
curl -c cookies.txt -X POST http://localhost/admin/login-handler.php \
  -d "login=admin&password=admin123&csrf_token=TOKEN"

# Test protected endpoint with session
curl -b cookies.txt http://localhost/admin/index.php

# Test API with CSRF token
curl -b cookies.txt -X POST http://localhost/api/services.php \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: TOKEN" \
  -d '{"name":"Test Service"}'
```

### Debugging Session Issues

```php
// Add to admin pages temporarily
var_dump($_SESSION);
var_dump(session_id());
var_dump(session_status());
```

### Custom Session Duration

Edit `admin/includes/session-config.php`:

```php
// Change timeout from 30 to 60 minutes
$timeout = 3600; // seconds
ini_set('session.gc_maxlifetime', 3600);
```

## Future Enhancements

- [ ] Two-factor authentication (TOTP)
- [ ] Remember me functionality
- [ ] Failed login attempt logging
- [ ] Email notifications for suspicious activity
- [ ] IP-based access restrictions
- [ ] Multiple admin users with roles
- [ ] Password reset via email
- [ ] Login history and audit log
- [ ] Session management dashboard
- [ ] API token authentication (for automation)

## Credits

Implemented: January 2025  
Version: 1.0  
PHP Version: 7.4+  
Session Handler: PHP native sessions  
Password Hashing: bcrypt (via `password_hash()`)
