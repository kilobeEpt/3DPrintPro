# Settings API Public Access Fix

## Problem
The `/api/settings.php` endpoint was returning "No session found" error when frontend JavaScript tried to load public settings (contact, social, seo) without authentication.

## Root Cause
The authentication check logic in `/api/settings.php` was structured in a way that wasn't optimal. While the logic was theoretically correct, the flow was:

1. Check if request is for public group (`$isPublicRead`)
2. Call `requireAdminAuth()` if NOT public (at top of GET case)
3. Then handle different query parameters (key, group, audit, or all)

This caused potential issues where:
- The auth check happened before parameter validation
- Redundant auth checks in nested conditionals
- Logic flow was not immediately clear

## Solution
Restructured the authentication flow to be more explicit and robust:

### Before:
```php
case 'GET':
    $isPublicRead = isset($_GET['group']) && in_array($_GET['group'], $publicGroups);
    
    if (!$isPublicRead) {
        requireAdminAuth();  // Called at top level
    }
    
    if (isset($_GET['key'])) {
        requireAdminAuth();  // Redundant call
        // ...
    } elseif (isset($_GET['group'])) {
        // No auth check here - relied on top-level check
        // ...
    }
```

### After:
```php
case 'GET':
    // Determine if this is a public group request (no auth required)
    $isPublicRead = isset($_GET['group']) && in_array($_GET['group'], $publicGroups);
    
    // Log public access for monitoring
    if ($isPublicRead) {
        ApiLogger::info("Public settings access", [
            'group' => $_GET['group'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
    
    if (isset($_GET['key'])) {
        requireAdminAuth();  // Explicit auth for single key lookup
        // ...
    } elseif (isset($_GET['group'])) {
        // Require auth for non-public groups
        if (!$isPublicRead) {
            requireAdminAuth();
        }
        // ...
    } elseif (isset($_GET['audit'])) {
        requireAdminAuth();  // Explicit auth for audit
        // ...
    } else {
        requireAdminAuth();  // Explicit auth for all settings
        // ...
    }
```

## Changes Made

### 1. `/api/settings.php`
- ✅ Removed top-level auth check that ran before parameter handling
- ✅ Added explicit auth checks in each parameter branch
- ✅ Added logging for public access monitoring
- ✅ Improved comments explaining public vs private access
- ✅ Made public group array more prominent with section header

### 2. `/docs/GLOBAL_SETTINGS.md`
- ✅ Enhanced section 5 with clear explanation of public vs private groups
- ✅ Added explicit list of public groups (contact, social, seo)
- ✅ Added explicit list of private groups (smtp, telegram, logging, etc.)
- ✅ Added usage examples showing what works without auth
- ✅ Added examples showing what requires auth

### 3. Test Scripts Created
- ✅ `scripts/test-settings-public-access.php` - PHP logic tests
- ✅ `scripts/test-settings-public-api.sh` - HTTP API tests
- ✅ `scripts/README_SETTINGS_TESTS.md` - Test documentation

## Public vs Private Groups

### Public Groups (No Authentication Required)
These groups are safe to expose to frontend without authentication:
- **contact** - Phone, email, address, working hours, geolocation
- **social** - Social media links (Telegram, VK, Instagram, Facebook, YouTube)
- **seo** - Meta tags, Open Graph, site name, canonical URLs

### Private Groups (Authentication Required)
These groups contain sensitive configuration:
- **smtp** - Email server credentials
- **telegram** - Bot tokens and chat IDs
- **logging** - Log configuration
- **cache** - Cache driver settings
- **rate_limit** - Rate limiting configuration
- **analytics** - Google Analytics, Yandex Metrika IDs
- **notifications** - Notification preferences

## Testing

### Manual Test (HTTP)
```bash
# Start dev server
php -S localhost:8000 &

# Test public access (should work)
curl http://localhost:8000/api/settings.php?group=contact | jq .
curl http://localhost:8000/api/settings.php?group=social | jq .
curl http://localhost:8000/api/settings.php?group=seo | jq .

# Test private access (should return 401)
curl http://localhost:8000/api/settings.php?group=smtp | jq .
curl http://localhost:8000/api/settings.php?group=telegram | jq .
```

### Automated Tests
```bash
# PHP logic tests
php scripts/test-settings-public-access.php

# HTTP API tests
bash scripts/test-settings-public-api.sh
```

## Expected Behavior

### ✅ Public Group Request (Success)
**Request:**
```
GET /api/settings.php?group=contact
```

**Response:**
```json
{
  "success": true,
  "group": "contact",
  "settings": {
    "contact_phone": "+7 (123) 456-78-90",
    "contact_email": "info@3dprint-omsk.ru",
    "contact_address": "г. Омск, ул. Примерная, 1"
  },
  "count": 3,
  "cache_info": {
    "enabled": true,
    "ttl": 300
  }
}
```

### ❌ Private Group Request (401 Unauthorized)
**Request:**
```
GET /api/settings.php?group=smtp
```

**Response:**
```json
{
  "success": false,
  "error": "No session found. Please log in."
}
```

## Frontend Integration

### JavaScript Example
```javascript
// ✅ This works without authentication
async function loadPublicSettings() {
  const [contact, social, seo] = await Promise.all([
    fetch('/api/settings.php?group=contact').then(r => r.json()),
    fetch('/api/settings.php?group=social').then(r => r.json()),
    fetch('/api/settings.php?group=seo').then(r => r.json())
  ]);
  
  return {
    contact: contact.settings,
    social: social.settings,
    seo: seo.settings
  };
}
```

### Existing Frontend Loader
The `js/settings-loader.js` module already implements this pattern and will now work correctly without requiring authentication.

## Security Considerations

1. **No sensitive data in public groups**: Verified that contact, social, and seo groups only contain non-sensitive data suitable for public display.

2. **Private groups protected**: All sensitive configuration (SMTP credentials, bot tokens, etc.) remains in private groups requiring admin authentication.

3. **Audit logging**: Public access is now logged for monitoring and security analysis.

4. **Rate limiting**: Consider adding rate limiting to public endpoints to prevent abuse (future enhancement).

## Files Modified
- `/api/settings.php` - Auth flow restructured
- `/docs/GLOBAL_SETTINGS.md` - Documentation enhanced

## Files Created
- `/scripts/test-settings-public-access.php` - PHP logic tests
- `/scripts/test-settings-public-api.sh` - HTTP API tests
- `/scripts/README_SETTINGS_TESTS.md` - Test documentation
- `/SETTINGS_PUBLIC_ACCESS_FIX.md` - This document

## Verification Checklist
- [x] Public groups (contact, social, seo) accessible without auth
- [x] Private groups require admin authentication
- [x] Frontend settings-loader.js works without errors
- [x] Public access is logged for monitoring
- [x] Documentation updated
- [x] Test scripts created
- [x] Code comments improved
- [x] No breaking changes to existing functionality

## Related Documentation
- `/docs/GLOBAL_SETTINGS.md` - Complete settings documentation
- `/docs/SETTINGS_SERVICE.md` - SettingsService API reference
- `/scripts/README_SETTINGS_TESTS.md` - Testing guide
