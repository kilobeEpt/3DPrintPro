# RBAC Authentication Migration Guide

Step-by-step guide to migrate from the old settings-based authentication to the new RBAC system.

## Overview

**Old System (v1.0):**
- Credentials stored in `settings` table (`admin_login`, `admin_password_hash`)
- Single admin user (no roles)
- Session-only tracking (no database persistence)
- No audit logging

**New System (v4.0):**
- Users stored in `admin_users` table
- Multiple users with roles (super_admin, admin, editor)
- Sessions persisted in `admin_sessions` table
- Full audit trail in `admin_action_logs` and `admin_login_attempts`

## Migration Steps

### Step 1: Backup Database

```bash
# Create backup before migration
mysqldump -u username -p database_name > backup_before_rbac.sql

# Or via hosting panel
# Use phpMyAdmin > Export
```

### Step 2: Update Database Schema

Run the updated schema to add RBAC tables:

```bash
# Option A: Command line
mysql -u username -p database_name < database/schema.sql

# Option B: phpMyAdmin
# Import > Choose file: database/schema.sql > Go
```

**Expected Output:**
```
4 new tables created:
- admin_users
- admin_sessions
- admin_login_attempts
- admin_action_logs
```

**Verification:**
```sql
SHOW TABLES LIKE 'admin_%';
-- Should return 4 tables
```

### Step 3: Retrieve Old Credentials

Get your existing credentials from the settings table:

```sql
SELECT setting_key, setting_value 
FROM settings 
WHERE setting_key IN ('admin_login', 'admin_password_hash');
```

**Expected Result:**
```
admin_login: admin (or your custom login)
admin_password_hash: $2y$10$... (bcrypt hash)
```

**Note:** If you don't see these rows, credentials were never set. Proceed to Step 4 with new credentials.

### Step 4: Create First Admin User

#### Option A: If You Know Your Old Password

```bash
php scripts/create-admin.php
```

When prompted:
- **Email**: Your old login + "@example.com" (e.g., `admin@example.com`)
- **Name**: "Administrator" (or your preferred name)
- **Password**: Your old password
- **Role**: `super_admin`
- **Status**: `active`

#### Option B: If You Forgot Your Password

```bash
php scripts/create-admin.php admin@example.com "Administrator" NewSecurePassword123 super_admin active
```

**Important:** Store these credentials securely!

#### Verification

```sql
SELECT id, email, name, role, status FROM admin_users;
```

**Expected:**
```
id: 1
email: admin@example.com
name: Administrator
role: super_admin
status: active
```

### Step 5: Test Login

1. Open browser to `/admin/login.php`
2. Enter email: `admin@example.com`
3. Enter password: (your password from Step 4)
4. Click "Войти" (Login)

**Expected:** Redirect to `/admin/index.php` (dashboard)

**If Login Fails:**
- Check user status: `SELECT status FROM admin_users WHERE email='admin@example.com';`
- Verify password was set: `SELECT LENGTH(password_hash) FROM admin_users WHERE email='admin@example.com';` (should be 60)
- Reset password: `php scripts/create-admin.php admin@example.com "Admin" NewPassword123`

### Step 6: Verify Session Persistence

After successful login, check that session was created:

```sql
SELECT id, user_id, ip_address, expires_at, created_at 
FROM admin_sessions 
ORDER BY created_at DESC 
LIMIT 1;
```

**Expected:**
- `user_id`: 1 (your admin user ID)
- `ip_address`: Your IP
- `expires_at`: ~30 minutes from now
- `csrf_token`: 64-character hex string

### Step 7: Verify Audit Logging

Check login was logged:

```sql
SELECT id, user_id, action, ip_address, created_at 
FROM admin_action_logs 
WHERE action='login' 
ORDER BY created_at DESC 
LIMIT 1;
```

**Expected:**
- `user_id`: 1
- `action`: login
- `ip_address`: Your IP

Check login attempt was logged:

```sql
SELECT id, email, success, ip_address, created_at 
FROM admin_login_attempts 
ORDER BY created_at DESC 
LIMIT 1;
```

**Expected:**
- `email`: admin@example.com
- `success`: 1 (true)

### Step 8: Test Admin Operations

1. Navigate to any admin page (e.g., Services, Orders, Settings)
2. Perform an action (create, update, or delete)
3. Verify action was logged:

```sql
SELECT user_id, action, entity_type, entity_id, created_at 
FROM admin_action_logs 
ORDER BY created_at DESC 
LIMIT 5;
```

### Step 9: (Optional) Remove Old Credentials

After confirming the new system works, remove old settings-based credentials:

```sql
DELETE FROM settings 
WHERE setting_key IN ('admin_login', 'admin_password_hash');
```

**Warning:** Only do this after Step 5 is successful!

### Step 10: Create Additional Users (Optional)

```bash
# Create regular admin
php scripts/create-admin.php manager@example.com "Site Manager" password123 admin active

# Create editor
php scripts/create-admin.php editor@example.com "Content Editor" password123 editor active
```

**Verification:**
```sql
SELECT id, email, name, role, status FROM admin_users;
```

## Rollback Procedure

If migration fails and you need to rollback:

### Option 1: Restore from Backup

```bash
# Drop new tables
mysql -u username -p database_name -e "
DROP TABLE IF EXISTS admin_action_logs;
DROP TABLE IF EXISTS admin_sessions;
DROP TABLE IF EXISTS admin_login_attempts;
DROP TABLE IF EXISTS admin_users;
"

# Restore backup
mysql -u username -p database_name < backup_before_rbac.sql
```

### Option 2: Keep Both Systems

The new RBAC system is backward compatible. You can:

1. Keep both `settings.admin_login` and `admin_users`
2. Login will use `admin_users` if email matches
3. Old settings remain for reference
4. No code changes needed

## Troubleshooting

### Problem: "Table admin_users already exists"

**Cause:** Schema was already applied.

**Solution:**
1. Check if users exist: `SELECT COUNT(*) FROM admin_users;`
2. If 0, proceed with Step 4 (create first admin)
3. If >0, users already migrated - get email: `SELECT email FROM admin_users WHERE role='super_admin';`

### Problem: "Cannot create admin user"

**Cause:** Database connection issue or permissions.

**Solution:**
1. Test Eloquent: `php scripts/eloquent-smoke.php`
2. Check `.env` file exists with DB credentials
3. Verify `bootstrap/eloquent.php` is readable
4. Check DB user has INSERT permissions on `admin_users`

### Problem: "Login fails with valid credentials"

**Cause:** User status not active or password mismatch.

**Solution:**
```sql
-- Check user status
SELECT id, email, status, locked_until FROM admin_users WHERE email='your@email.com';

-- Activate user
UPDATE admin_users SET status='active', locked_until=NULL WHERE email='your@email.com';

-- Reset password
-- Run: php scripts/create-admin.php your@email.com "Name" NewPassword123
```

### Problem: "Session expires immediately"

**Cause:** Session not persisted to database.

**Solution:**
1. Check session was created: `SELECT * FROM admin_sessions ORDER BY created_at DESC LIMIT 1;`
2. Verify cookies enabled in browser
3. Check `includes/admin-session.php` is loaded
4. Ensure `session_start()` called before authentication

### Problem: "CSRF token invalid"

**Cause:** Token not synced between session and database.

**Solution:**
1. Clear browser cookies
2. Check session has token: `SELECT csrf_token FROM admin_sessions WHERE session_id='...';`
3. Refresh login page to regenerate token
4. Verify `CSRF::syncTokenToDatabase()` called

## Post-Migration Checklist

- [ ] Database backup created
- [ ] Schema updated (4 new tables)
- [ ] First super admin created
- [ ] Login tested successfully
- [ ] Session persisted to database
- [ ] Login attempt logged
- [ ] Admin action logged
- [ ] (Optional) Old credentials removed
- [ ] Documentation reviewed
- [ ] Additional users created (if needed)

## Security Recommendations

After migration:

1. **Change Default Password**: Use strong, unique password
2. **Enable HTTPS**: Ensure SSL/TLS certificate installed
3. **Review Users**: Audit all users in `admin_users` table
4. **Monitor Attempts**: Check `admin_login_attempts` for suspicious activity
5. **Review Logs**: Check `admin_action_logs` periodically
6. **Cleanup Sessions**: Run `cleanupExpiredSessions()` via cron
7. **Backup Regularly**: Schedule automated database backups

## Support

If you encounter issues during migration:

1. Check logs: `tail -f /var/log/apache2/error.log` (or nginx)
2. Review documentation: `docs/RBAC_AUTHENTICATION.md`
3. Run smoke tests: `php scripts/eloquent-smoke.php`
4. Verify schema: `php database/verify-schema.php`
5. Test authentication: See "Manual Testing" in RBAC_AUTHENTICATION.md

## Migration Completion

Once migration is complete:

✅ New RBAC authentication system active
✅ Multiple admin users with roles supported
✅ Sessions persisted in database
✅ Full audit trail enabled
✅ Enhanced security with rate limiting
✅ CSRF tokens session-bound
✅ Backward compatible with old login form

**Next Steps:**
- Review `docs/RBAC_AUTHENTICATION.md` for full feature list
- Create additional admin users as needed
- Set up monitoring for login attempts
- Configure session cleanup cron job
- Review and adjust role permissions

## Version Info

- Migration From: v1.0 (settings-based auth)
- Migration To: v4.0 (RBAC with Eloquent)
- Schema Version: 4.0
- Tables Added: 4 (admin_users, admin_sessions, admin_login_attempts, admin_action_logs)
- Backward Compatible: Yes (login field accepts both 'email' and 'login')
- Breaking Changes: None (settings-based auth still works alongside RBAC)
