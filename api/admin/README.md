# Admin API Endpoints

This directory contains API endpoints for admin panel functionality that require super_admin role.

## Endpoints

### `/api/admin/users.php`

**Purpose**: Manage admin user accounts (CRUD operations)

**Access**: Super Administrator only (except initial onboarding)

**Methods**:
- `GET` - List users, get single user, get audit history, check if users exist
- `POST` - Create new user
- `PUT` - Update user
- `DELETE` - Delete user

**Features**:
- Role-based access control (RBAC)
- Password complexity validation
- Email uniqueness validation
- Audit trail integration
- Session management (force logout on changes)
- First-time onboarding support

**Controller**: `App\Http\Controllers\Api\AdminUserController`

**Documentation**: See `/docs/ADMIN_GUIDE.md` for complete usage guide

## Security

All endpoints in this directory:
- Require super_admin role (enforced by controller)
- Use CSRF tokens for state-changing operations
- Rate limit requests to prevent abuse
- Log all actions to audit trail
- Validate all input data

## Usage Examples

### Check if users exist (onboarding)
```
GET /api/admin/users.php?action=check_users_exist
```

### List all users
```
GET /api/admin/users.php
GET /api/admin/users.php?role=super_admin
GET /api/admin/users.php?status=active
GET /api/admin/users.php?search=john
```

### Get single user
```
GET /api/admin/users.php?id=1
```

### Get audit history
```
GET /api/admin/users.php?action=audit_history&user_id=1
```

### Create user
```
POST /api/admin/users.php
{
  "email": "admin@example.com",
  "name": "John Doe",
  "password": "SecurePass123",
  "role": "admin",
  "status": "active"
}
```

### Update user
```
PUT /api/admin/users.php
{
  "id": 1,
  "name": "Jane Doe",
  "role": "super_admin"
}
```

### Delete user
```
DELETE /api/admin/users.php?id=1
```

## Response Format

All responses follow standard API format:

**Success:**
```json
{
  "success": true,
  "data": { ... },
  "meta": { ... }
}
```

**Error:**
```json
{
  "success": false,
  "error": "Error message",
  "errors": { ... }
}
```

## Development

When adding new admin endpoints:
1. Create controller in `app/Http/Controllers/Api/`
2. Extend `BaseApiController`
3. Create endpoint file in `api/admin/`
4. Implement RBAC checks
5. Add audit logging
6. Update documentation
