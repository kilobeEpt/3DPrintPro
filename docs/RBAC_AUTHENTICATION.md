# RBAC Authentication System (v4.0)

Complete Role-Based Access Control (RBAC) authentication system with Eloquent ORM, database-backed sessions, audit logging, and CSRF protection.

## Overview

The RBAC authentication system provides:

1. **Database-Backed User Management** - Admin users stored in `admin_users` table
2. **Password Hashing** - bcrypt password hashing with `PASSWORD_BCRYPT`
3. **Session Persistence** - Sessions stored in `admin_sessions` table
4. **Login Attempt Tracking** - Rate limiting via `admin_login_attempts` table
5. **Action Audit Logging** - All admin actions logged in `admin_action_logs` table
6. **Role-Based Access** - Three roles: `super_admin`, `admin`, `editor`
7. **CSRF Protection** - Session-bound CSRF tokens with rotation
8. **Remember Me** - Optional extended session lifetime

## Architecture

### Database Schema

Four new tables support the RBAC system:

```sql
-- Admin user accounts
admin_users (
    id, email, name, password_hash, role, status,
    last_login_at, last_login_ip, failed_login_attempts,
    locked_until, remember_token, created_at, updated_at
)

-- Persistent sessions
admin_sessions (
    id, session_id, user_id, ip_address, user_agent,
    csrf_token, expires_at, last_activity_at, created_at, updated_at
)

-- Login attempt tracking
admin_login_attempts (
    id, email, ip_address, user_agent,
    success, failure_reason, created_at
)

-- Audit trail
admin_action_logs (
    id, user_id, action, entity_type, entity_id,
    payload, ip_address, user_agent, created_at
)
```

### Eloquent Models

- `App\Models\AdminUser` - User accounts with password hashing
- `App\Models\AdminSession` - Session management
- `App\Models\AdminLoginAttempt` - Login tracking
- `App\Models\AdminActionLog` - Audit logging

### Services

- `App\Services\AdminAuthService` - Central authentication service

## Setup Instructions

### 1. Apply Database Schema

Run the updated schema to create RBAC tables:

```bash
# MySQL
mysql -u username -p database_name < database/schema.sql

# Or via hosting panel
# Import schema.sql via phpMyAdmin
```

### 2. Create First Admin User

Use the CLI script to create your first super-admin:

```bash
# Interactive mode
php scripts/create-admin.php

# Non-interactive mode
php scripts/create-admin.php admin@example.com "Admin User" SecurePassword123 super_admin active
```

**Required:**
- Email: Valid email address (used for login)
- Name: Full name
- Password: Minimum 8 characters
- Role: `super_admin`, `admin`, or `editor` (default: admin)
- Status: `active` or `inactive` (default: active)

### 3. Login

Navigate to `/admin/login.php` and log in with your email and password.

## User Roles

### Super Admin (`super_admin`)
- Full system access
- Can manage all users
- Can access all admin functions
- Cannot be locked by rate limiting

### Admin (`admin`)
- Standard admin access
- Can manage content and settings
- Can view logs
- Subject to rate limiting

### Editor (`editor`)
- Limited access
- Can edit content only
- Cannot change settings
- Subject to rate limiting

## Authentication Features

### Password Security

- **Hashing Algorithm**: bcrypt via `password_hash($password, PASSWORD_BCRYPT)`
- **Hash Verification**: Timing-safe comparison via `password_verify()`
- **No Plain Text**: Passwords never stored in plain text
- **Auto-Upgrade**: Password hashes auto-upgraded if algorithm improves

### Rate Limiting

**Protection Against Brute Force:**
- Maximum 5 failed attempts per email in 15 minutes
- Automatic lockout for 15 minutes after exceeding limit
- Counter reset on successful login
- Tracked per email address across all IPs

**Account Locking:**
- Accounts can be manually locked (`status = 'locked'`)
- Temporary lockouts via `locked_until` timestamp
- Automatic unlock when `locked_until` expires

### Session Management

**Database-Backed Sessions:**
- PHP session ID stored in `admin_sessions` table
- Linked to `admin_users` via foreign key
- Automatic cleanup of expired sessions
- Session metadata: IP, user agent, timestamps

**Session Lifecycle:**
- **Default Lifetime**: 30 minutes of inactivity
- **Remember Me**: 30 days if enabled
- **Activity Tracking**: `last_activity_at` updated on each request
- **Expiration**: Sessions expire at `expires_at` timestamp
- **Regeneration**: CSRF token regenerated on login/logout

### CSRF Protection

**Session-Bound Tokens:**
- CSRF token stored in `admin_sessions.csrf_token`
- Token synced to PHP `$_SESSION['CSRF_TOKEN']`
- Validated using timing-safe `hash_equals()`
- Rotated on authentication changes

**Token Usage:**
- Hidden form fields: `<input name="csrf_token" value="...">`
- HTTP headers: `X-CSRF-Token: abc123...`
- Meta tags: `<meta name="csrf-token" content="...">`

### Audit Logging

**Automatic Logging:**
- Login/logout events
- Admin actions (create, update, delete, view, export)
- Entity tracking (type and ID)
- Request metadata (IP, user agent)
- JSON payload for context

**Log Actions:**
- `login` - User logged in
- `logout` - User logged out
- `create` - Entity created
- `update` - Entity updated
- `delete` - Entity deleted
- `view` - Entity viewed (optional)
- `export` - Data exported

## API Reference

### AdminAuthService Methods

```php
use App\Services\AdminAuthService;

$authService = new AdminAuthService();

// Authenticate user
$result = $authService->authenticate(
    $email,           // User email
    $password,        // Plain text password
    $ipAddress,       // Client IP
    $userAgent,       // User agent string
    $rememberMe       // Boolean (optional)
);

// Returns:
// [
//     'success' => true/false,
//     'user' => AdminUser,           // On success
//     'session' => AdminSession,     // On success
//     'csrf_token' => '...',         // On success
//     'error' => 'Error message',    // On failure
//     'locked_until' => DateTime     // On lockout
// ]

// Validate session
$validation = $authService->validateSession($sessionId);

// Returns:
// [
//     'valid' => true/false,
//     'user' => AdminUser,           // If valid
//     'session' => AdminSession,     // If valid
//     'error' => 'Error message'     // If invalid
// ]

// Destroy session (logout)
$authService->destroySession($sessionId);

// Validate CSRF token
$valid = $authService->validateCsrfToken($sessionId, $token);

// Regenerate CSRF token
$newToken = $authService->regenerateCsrfToken($sessionId);

// Log admin action
$authService->logAction(
    $userId,
    'create',
    'service',
    123,
    ['name' => 'New Service']
);

// Cleanup expired sessions
$authService->cleanupExpiredSessions();
```

### Admin Auth Helpers

```php
// Require authentication (API endpoints)
requireAdminAuth();

// Verify CSRF token
verifyCsrfToken();

// Require both
requireAdminAuthWithCsrf();

// Get authenticated user
$user = getAuthenticatedUser();

// Log action (uses session user)
logAdminAction('create', 'service', 123, ['name' => 'Test']);
```

### Auth Middleware (Admin Pages)

```php
use Auth;

// Require authentication
Auth::require('/admin/login.php');

// Check if authenticated
if (Auth::check()) {
    // User is logged in
}

// Get current user
$user = Auth::user(); // Returns email

// Logout
Auth::logout();
```

### CSRF Class

```php
use CSRF;

// Generate/get token
$token = CSRF::getToken();

// Validate token
if (CSRF::validateToken($token)) {
    // Valid
}

// Verify from POST
CSRF::verifyPostToken(); // Dies with 403 if invalid

// Verify from header
CSRF::verifyHeaderToken('X-CSRF-Token');

// Get form field
echo CSRF::getTokenField();

// Get meta tag
echo CSRF::getTokenMeta();

// Regenerate token
CSRF::regenerateToken();
```

## CLI Scripts

### create-admin.php

Create or update admin users:

```bash
# Interactive mode
php scripts/create-admin.php

# Non-interactive mode
php scripts/create-admin.php <email> <name> <password> [role] [status]

# Examples
php scripts/create-admin.php admin@site.com "Site Admin" Password123
php scripts/create-admin.php editor@site.com "Content Editor" pass123 editor active
```

### setup-admin-credentials.php (Legacy)

**Deprecated:** Use `create-admin.php` instead.

This script stores credentials in the `settings` table (old system). Kept for backward compatibility during migration.

## Security Best Practices

### Password Requirements

- Minimum 8 characters (enforced in `create-admin.php`)
- Recommend 12+ characters with mixed case, numbers, symbols
- Never reuse passwords from other services
- Use a password manager

### Secure Deployment

1. **HTTPS Only**: Always use SSL/TLS (Let's Encrypt)
2. **Strong Passwords**: Enforce complexity requirements
3. **Regular Audits**: Review `admin_action_logs` periodically
4. **Monitor Attempts**: Check `admin_login_attempts` for suspicious activity
5. **Cleanup Sessions**: Run `cleanupExpiredSessions()` via cron
6. **Limit Super Admins**: Only create super admins when necessary
7. **Inactive Users**: Set status to `inactive` instead of deleting
8. **IP Restrictions**: Consider IP whitelist at hosting level

### Production Checklist

- [x] All passwords hashed with bcrypt
- [x] CSRF tokens on all state-changing operations
- [x] HttpOnly, Secure, SameSite session cookies
- [x] Rate limiting (5 attempts, 15-minute lockout)
- [x] Session timeout (30 minutes inactivity)
- [x] Audit logging for all admin actions
- [x] SQL injection protection (Eloquent/PDO)
- [x] XSS protection (htmlspecialchars)
- [x] Session ID regeneration
- [ ] Periodic password rotation policy
- [ ] Two-factor authentication (future)

## Migration from Old System

### Migrating Existing Settings-Based Credentials

If you have credentials in the `settings` table (`admin_login`, `admin_password_hash`), migrate them:

```bash
# 1. Note your current login from settings table
mysql -u user -p database -e "SELECT setting_value FROM settings WHERE setting_key='admin_login';"

# 2. Create admin user with same credentials
php scripts/create-admin.php your_login@example.com "Admin" YourOldPassword

# 3. Test login with new system

# 4. (Optional) Remove old settings
mysql -u user -p database -e "DELETE FROM settings WHERE setting_key IN ('admin_login', 'admin_password_hash');"
```

### Backward Compatibility

The `login-handler.php` accepts both:
- `$_POST['email']` - New RBAC system (preferred)
- `$_POST['login']` - Old system (fallback)

The login form field name can be either `login` or `email`.

## Troubleshooting

### Cannot Create First Admin

**Problem**: Error creating admin user

**Solutions**:
1. Verify database schema is up to date: `mysql -u user -p database < database/schema.sql`
2. Check tables exist: `SHOW TABLES LIKE 'admin_%';`
3. Verify Eloquent is configured: `php scripts/eloquent-smoke.php`
4. Check permissions on `bootstrap/eloquent.php`

### Login Fails with Valid Credentials

**Problem**: "Invalid email or password"

**Solutions**:
1. Verify user exists: `SELECT email FROM admin_users WHERE email='your@email.com';`
2. Check user status: `SELECT status FROM admin_users WHERE email='your@email.com';`
3. Try resetting password: `php scripts/create-admin.php your@email.com "Name" NewPass123`
4. Check for typos in email (case-sensitive)

### Account Locked Out

**Problem**: "Account locked" or "Too many attempts"

**Solutions**:
1. Wait 15 minutes for automatic unlock
2. Check lockout status: `SELECT locked_until FROM admin_users WHERE email='your@email.com';`
3. Manual unlock: `UPDATE admin_users SET locked_until=NULL, failed_login_attempts=0 WHERE email='your@email.com';`
4. Check login attempts: `SELECT * FROM admin_login_attempts WHERE email='your@email.com' ORDER BY created_at DESC LIMIT 10;`

### Session Expires Immediately

**Problem**: Logged out after each page load

**Solutions**:
1. Verify session table exists: `SELECT COUNT(*) FROM admin_sessions;`
2. Check session is created: `SELECT * FROM admin_sessions WHERE session_id='...';`
3. Verify cookies enabled in browser
4. Check `session.save_path` is writable
5. Ensure HTTPS if secure cookies enabled

### CSRF Token Mismatch

**Problem**: 403 Forbidden on form submit

**Solutions**:
1. Refresh page to get new token
2. Check session is valid: `SELECT * FROM admin_sessions WHERE session_id='...';`
3. Verify token in session matches token in form
4. Check browser is not blocking cookies
5. Ensure token is being sent (check DevTools Network tab)

## Testing

### PHPUnit Tests

```bash
# Run all tests
composer test

# Run admin auth tests only
vendor/bin/phpunit --filter AdminAuth
```

**Test Coverage:**
- Unit tests: `tests/Unit/AdminAuthServiceTest.php`
- Integration tests: `tests/Integration/AdminAuthIntegrationTest.php`

**Test Scenarios:**
- Valid credentials
- Invalid password
- Non-existent user
- Inactive account
- Account lockout after max attempts
- Rate limiting by email
- Session creation and validation
- Expired sessions
- CSRF token validation and rotation
- Action logging
- Remember me functionality
- Role-based access checks

### Manual Testing

```bash
# Create test user
php scripts/create-admin.php test@test.com "Test User" password123

# Test login via curl
curl -c cookies.txt -X POST http://localhost/admin/login-handler.php \
  -d "email=test@test.com&password=password123&csrf_token=TOKEN"

# Test authenticated request
curl -b cookies.txt http://localhost/admin/index.php

# Check session in database
mysql -u user -p database -e "SELECT * FROM admin_sessions WHERE user_id=1;"

# Check login attempts
mysql -u user -p database -e "SELECT * FROM admin_login_attempts WHERE email='test@test.com';"
```

## Future Enhancements

- [ ] Two-factor authentication (TOTP)
- [ ] Email verification on registration
- [ ] Password reset via email
- [ ] Login history dashboard
- [ ] User management UI (CRUD)
- [ ] Permission system (granular access control)
- [ ] API token authentication
- [ ] OAuth2/SAML integration
- [ ] Activity dashboard with charts
- [ ] IP whitelist/blacklist

## Version History

- **v4.0** (January 2025): RBAC system with database-backed sessions and audit logging
- **v1.0** (January 2025): PHP session-based authentication
- **v0.1** (2024): localStorage-based authentication (deprecated)

## Credits

Implemented: January 2025  
PHP Version: 7.4+  
Database: MySQL 8.0+ / SQLite (testing)  
ORM: Eloquent (illuminate/database)  
Password Hashing: bcrypt via `password_hash()`  
Session Handler: PHP native sessions + database persistence
