# Settings API Public Access Tests

## Overview
These test scripts verify that the Settings API correctly allows public access to `contact`, `social`, and `seo` groups without requiring authentication, while protecting private groups.

## Test Scripts

### 1. PHP Logic Test: `test-settings-public-access.php`
Tests the internal logic of public group detection and SettingsService.

**Usage:**
```bash
php scripts/test-settings-public-access.php
```

**What it tests:**
- ✅ Public group detection logic (`in_array()` checks)
- ✅ Query parameter simulation
- ✅ SettingsService can fetch public groups
- ✅ Database contains public settings

**Requirements:**
- PHP 7.4+
- Composer dependencies installed
- Database configured in `.env`

### 2. HTTP API Test: `test-settings-public-api.sh`
Tests the actual HTTP API endpoints to verify authentication behavior.

**Usage:**
```bash
# Start PHP dev server first
php -S localhost:8000 &

# Run tests
bash scripts/test-settings-public-api.sh

# Or with custom URL
API_BASE_URL=https://3dprint-omsk.ru bash scripts/test-settings-public-api.sh
```

**What it tests:**
- ✅ Public groups return 200 OK without auth
- ✅ Private groups return 401 Unauthorized without auth
- ✅ Response contains valid JSON with `success: true`

**Requirements:**
- Running web server (PHP dev server or Apache/Nginx)
- curl
- jq (optional, for pretty output)

## Public vs Private Groups

### Public Groups (No Auth Required)
These groups contain non-sensitive data visible to frontend users:
- `contact` - Contact information (phone, email, address)
- `social` - Social media links
- `seo` - SEO metadata and Open Graph tags

**API Calls:**
```bash
# ✅ These work WITHOUT authentication
curl http://localhost:8000/api/settings.php?group=contact
curl http://localhost:8000/api/settings.php?group=social
curl http://localhost:8000/api/settings.php?group=seo
```

### Private Groups (Auth Required)
These groups contain sensitive configuration data:
- `smtp` - Email server credentials
- `telegram` - Bot tokens and chat IDs
- `logging` - Log configuration
- `cache` - Cache settings
- `rate_limit` - Rate limiting configuration
- `analytics` - Analytics tracking codes
- `notifications` - Notification settings

**API Calls:**
```bash
# ❌ These return 401 without admin session
curl http://localhost:8000/api/settings.php?group=smtp
curl http://localhost:8000/api/settings.php?group=telegram
curl http://localhost:8000/api/settings.php  # All settings
```

## Expected Behavior

### Successful Public Access
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

### Denied Private Access
```json
{
  "success": false,
  "error": "No session found. Please log in."
}
```

## Troubleshooting

### Test fails: "Connection refused"
- Ensure web server is running: `php -S localhost:8000`
- Or check if Apache/Nginx is running: `systemctl status apache2`

### Test fails: Empty response
- Check PHP error logs: `tail -f /tmp/server.log`
- Verify database connection in `.env`
- Run `php scripts/test-settings-public-access.php` for detailed errors

### Public groups return 401
- Check if `$publicGroups` array in `/api/settings.php` includes the group
- Verify the auth check logic in the GET case
- Check server logs for any errors

### Private groups return 200
- This is a security issue! Verify auth checks are in place
- Check if `requireAdminAuth()` is called for non-public groups

## Integration with Frontend

### JavaScript Example
```javascript
// Load public settings automatically
class SettingsLoader {
  async loadPublicSettings() {
    try {
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
    } catch (error) {
      console.error('Failed to load settings:', error);
      return null;
    }
  }
}
```

### Frontend Loader
The `js/settings-loader.js` module automatically loads public settings on page load:
```html
<script src="/js/settings-loader.js"></script>
```

## Security Notes

1. **No sensitive data in public groups**: Never store passwords, tokens, or API keys in `contact`, `social`, or `seo` groups.

2. **Rate limiting**: Public endpoints should have rate limiting to prevent abuse (not yet implemented).

3. **CORS**: If accessing from a different domain, ensure CORS headers are properly configured.

4. **Caching**: Public settings are cached for 5 minutes. Updates may take up to 5 minutes to appear on frontend.

## See Also
- `/docs/GLOBAL_SETTINGS.md` - Complete settings documentation
- `/docs/SETTINGS_SERVICE.md` - SettingsService API reference
- `/api/settings.php` - Implementation
