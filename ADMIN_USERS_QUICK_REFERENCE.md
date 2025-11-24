# Admin Users Management - Quick Reference

## Access

**URL**: `/admin/users.php`  
**Permission**: Super Administrator only  
**API**: `/api/admin/users.php`

## Key Features

- ✅ Create/Edit/Delete users
- ✅ Manage roles & permissions
- ✅ Search & filter users
- ✅ View audit history
- ✅ Monitor active sessions
- ✅ Force logout on changes

## User Roles

| Role | Access Level | Can Manage Users |
|------|-------------|------------------|
| `super_admin` | Full | ✅ Yes |
| `admin` | Standard | ❌ No |
| `editor` | Limited | ❌ No |

## User Status

| Status | Login | Description |
|--------|-------|-------------|
| `active` | ✅ Yes | Normal operation |
| `inactive` | ❌ No | Temporarily suspended |
| `locked` | ❌ No | Account locked (auto after 5 failed logins) |

## API Endpoints

### List Users
```bash
GET /api/admin/users.php
GET /api/admin/users.php?search=john
GET /api/admin/users.php?role=admin
GET /api/admin/users.php?status=active
```

### Get Single User
```bash
GET /api/admin/users.php?id=1
```

### Create User
```bash
POST /api/admin/users.php
Content-Type: application/json

{
  "email": "admin@example.com",
  "name": "John Doe",
  "password": "SecurePass123",
  "role": "admin",
  "status": "active"
}
```

### Update User
```bash
PUT /api/admin/users.php
Content-Type: application/json

{
  "id": 1,
  "name": "Jane Doe",
  "role": "super_admin"
}
```

### Delete User
```bash
DELETE /api/admin/users.php?id=1
```

### Get Audit History
```bash
GET /api/admin/users.php?action=audit_history&user_id=1
```

### Check Users Exist (Onboarding)
```bash
GET /api/admin/users.php?action=check_users_exist
```

## Password Requirements

- ✅ Minimum 8 characters
- ✅ At least one letter (a-z, A-Z)
- ✅ At least one number (0-9)
- ✅ Maximum 255 characters

## Security Features

### RBAC Enforcement
- Only super_admin can access
- API returns 403 for non-super-admins
- Sidebar link only visible to super_admins

### Self-Protection
- ❌ Cannot change own role
- ❌ Cannot deactivate own account
- ❌ Cannot delete own account

### Session Management
- Password change → Force logout (all sessions)
- Role change → Force logout (all sessions)
- Status change → Force logout (all sessions)
- User deletion → Terminate all sessions

### Audit Trail
- All actions logged to `admin_action_logs`
- Includes: action, entity, timestamp, IP, user agent
- Preserved even after user deletion

## Common Tasks

### Onboard New Team Member
1. Click **Add User**
2. Enter email, name, password
3. Select role (usually `admin`)
4. Set status to `active`
5. Save
6. Share credentials securely

### Offboard Team Member
Option A (Soft):
1. Edit user
2. Change status to `inactive`
3. Save (auto logout)

Option B (Hard):
1. Click **Delete**
2. Confirm
3. User permanently removed

### Promote User
1. Edit user
2. Change role to higher level
3. Save (auto logout)

### Handle Security Incident
1. Edit compromised account
2. Set status to `locked`
3. Change password
4. Save (auto logout)
5. Review audit history
6. Unlock when resolved

## Troubleshooting

### Cannot Access Users Page
**Problem**: 403 Forbidden  
**Solution**: You need super_admin role

### Cannot Delete User
**Problem**: "Cannot delete your own account"  
**Solution**: Ask another super_admin to delete

### User Cannot Login
**Check**:
- Status is `active`
- Password is correct
- Not locked (wait 15 min or unlock manually)

### Audit History Empty
**Reason**: User has no actions logged yet  
**Note**: Login/logout creates entries

## File Locations

```
admin/
  ├── users.php              # UI page
  └── js/modules/users.js    # Frontend logic

api/admin/
  └── users.php              # API endpoint

app/Http/Controllers/Api/
  └── AdminUserController.php # Backend logic

docs/
  ├── ADMIN_GUIDE.md         # User guide
  └── API_REFERENCE.md       # API docs
```

## Testing

### Smoke Test
```bash
php scripts/test-users-api.php
```

### Manual Test
1. Login as super_admin
2. Navigate to Users page
3. Create test user
4. Edit test user
5. View audit history
6. Delete test user

## Support

- **User Guide**: `docs/ADMIN_GUIDE.md` → "User Management"
- **API Docs**: `api/admin/README.md`
- **Implementation**: `ADMIN_USERS_IMPLEMENTATION.md`
- **Checklist**: `IMPLEMENTATION_CHECKLIST.md`

## Quick Commands

```bash
# Run smoke test
php scripts/test-users-api.php

# Create admin user (CLI)
php scripts/create-admin.php email@example.com "Name" "Password123" admin active

# Check logs
tail -f logs/api.log
```

---

**Version**: 1.0  
**Last Updated**: November 19, 2024
