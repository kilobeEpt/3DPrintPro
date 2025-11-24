# Global Settings Center

## Overview

The Global Settings Center provides a centralized interface for managing all site-wide configurations without touching code. All settings are stored in the database, cached for performance, and fully audited.

## Features

### 1. Tabbed Interface
- **Contacts**: Phone, email, address, working hours, geolocation
- **Social**: Links to Telegram, VK, Instagram, Facebook, YouTube, Twitter, WhatsApp
- **SEO**: Meta tags, Open Graph, site name, canonical URL
- **Email**: SMTP configuration, from address, email notifications
- **Telegram**: Bot token, chat ID, notification preferences
- **Logging/Analytics**: Google Analytics, Yandex Metrika, log levels
- **Cache**: Cache driver, TTL, prefix settings

### 2. Validation & Type Casting
All settings have proper type definitions and validation rules:
- **String fields**: Max length validation, regex patterns for emails/URLs
- **Numeric fields**: Min/max range validation
- **Boolean fields**: Checkbox toggles
- **Coordinates**: Latitude (-90 to 90), Longitude (-180 to 180)

### 3. Audit Trail
Every change is logged to `settings_audit` table with:
- Setting key
- Old value
- New value
- Changed by (admin username)
- Timestamp

View audit history with the "История изменений" button.

### 4. Test Buttons
- **Test Telegram**: Sends a test message to verify bot configuration
- **Test Email**: Sends a test email to verify SMTP configuration

### 5. Public API Access (No Authentication Required)
Frontend pages can fetch settings via public API **WITHOUT authentication**.

**Public Groups** (accessible without session):
- `contact` - Phone, email, address, working hours, geolocation
- `social` - Social media links (Telegram, VK, Instagram, etc.)
- `seo` - SEO metadata, Open Graph, canonical URLs

**Private Groups** (require admin authentication):
- `smtp` - Email server configuration
- `telegram` - Bot tokens and chat IDs
- `logging` - Log levels and file settings
- `cache` - Cache configuration
- `rate_limit` - Rate limiting settings
- `analytics` - Google Analytics, Yandex Metrika IDs
- `notifications` - Notification preferences

**Usage Examples:**
```javascript
// ✅ Public access - works without authentication
fetch('/api/settings.php?group=contact')
  .then(r => r.json())
  .then(data => console.log(data.settings));

fetch('/api/settings.php?group=social')
  .then(r => r.json())
  .then(data => console.log(data.settings));

fetch('/api/settings.php?group=seo')
  .then(r => r.json())
  .then(data => console.log(data.settings));

// ❌ Private access - returns 401 without admin session
fetch('/api/settings.php?group=smtp')  // Requires authentication
fetch('/api/settings.php?group=telegram')  // Requires authentication
fetch('/api/settings.php')  // Get all settings - requires authentication
```

### 6. Caching
- **Backend**: JSON file cache with 5-minute TTL (`storage/cache/settings.json`)
- **Frontend**: localStorage cache with 5-minute TTL
- **Auto-invalidation**: Cache clears automatically on any update

## Setup

### 1. Seed Default Settings
Run the seeder script to populate default values:
```bash
php scripts/seed-global-settings.php
```

This creates settings from hardcoded values in the HTML files:
- Contact information from `index.html` and `contact.html`
- Social links from footer
- SEO metadata from meta tags
- Default SMTP/Telegram/logging configuration

### 2. Configure in Admin Panel
Navigate to **Admin → Настройки** and fill in:
- Real contact information
- Social media URLs
- SMTP credentials
- Telegram bot token and chat ID
- Analytics tracking codes

### 3. Test Integrations
Use the test buttons to verify:
- Telegram bot can send messages
- SMTP configuration works

## Frontend Integration

### Automatic Loading
Include the settings loader in your HTML pages:
```html
<script src="/js/settings-loader.js"></script>
```

The script automatically:
1. Fetches settings from API on page load
2. Caches them in localStorage (5-minute TTL)
3. Updates meta tags, contact info, and social links
4. Updates JSON-LD structured data

### Data Attributes
Use data attributes to automatically populate content:
```html
<!-- Contact information -->
<a data-contact="phone" href="tel:+71234567890">+7 (123) 456-78-90</a>
<a data-contact="email" href="mailto:info@example.com">info@example.com</a>
<span data-contact="address">ул. Ленина, д. 15</span>
<span data-contact="working-hours">Пн-Пт: 9:00-18:00</span>

<!-- Social links -->
<a data-social="telegram" href="https://t.me/channel">
  <i class="fab fa-telegram"></i>
</a>
<a data-social="vk" href="https://vk.com/group">
  <i class="fab fa-vk"></i>
</a>
<a data-social="instagram" href="https://instagram.com/profile">
  <i class="fab fa-instagram"></i>
</a>
```

### Programmatic Access
Access settings in your JavaScript:
```javascript
// Wait for settings to load
window.addEventListener('load', () => {
  const phone = window.siteSettings.getContact('phone');
  const email = window.siteSettings.getContact('email');
  const telegram = window.siteSettings.getSocial('telegram');
  const title = window.siteSettings.getSEO('title');
  
  console.log('Contact phone:', phone);
  console.log('Contact email:', email);
});
```

## API Reference

### GET /api/settings.php
Get all settings (admin only):
```bash
GET /api/settings.php
Authorization: Admin session required
```

Response:
```json
{
  "success": true,
  "settings": {
    "contact_phone": "+7 (999) 123-45-67",
    "contact_email": "info@3dprint-omsk.ru",
    "social_telegram": "https://t.me/PrintPro_Omsk",
    ...
  },
  "cache_info": {
    "enabled": true,
    "ttl": 300
  }
}
```

### GET /api/settings.php?group={name}
Get settings by group (public for contact/social/seo, admin-only for others):
```bash
GET /api/settings.php?group=contact
GET /api/settings.php?group=social
GET /api/settings.php?group=seo
```

Response:
```json
{
  "success": true,
  "group": "contact",
  "settings": {
    "contact_phone": "+7 (999) 123-45-67",
    "contact_email": "info@3dprint-omsk.ru",
    "contact_address": "ул. Ленина, д. 15",
    ...
  },
  "count": 10,
  "cache_info": {
    "enabled": true,
    "ttl": 300
  }
}
```

### GET /api/settings.php?audit=&limit=50
Get audit history (admin only):
```bash
GET /api/settings.php?audit=&limit=50
Authorization: Admin session required
```

### POST /api/settings.php
Update multiple settings (admin only):
```bash
POST /api/settings.php
Authorization: Admin session required
X-CSRF-Token: {token}
Content-Type: application/json

{
  "contact_phone": "+7 (999) 123-45-67",
  "contact_email": "info@3dprint-omsk.ru",
  "telegram_bot_token": "123456:ABC-DEF"
}
```

## Database Schema

### settings table
```sql
CREATE TABLE settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(255) UNIQUE NOT NULL,
  setting_value TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_key (setting_key)
);
```

### settings_audit table
```sql
CREATE TABLE settings_audit (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(255) NOT NULL,
  old_value TEXT,
  new_value TEXT,
  changed_by VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_key (setting_key),
  INDEX idx_changed_by (changed_by),
  INDEX idx_created (created_at)
);
```

## Settings Groups

### Contact (contact_*)
- `contact_phone`: Main contact phone
- `contact_email`: Main contact email
- `contact_address`: Street address
- `contact_city`: City name
- `contact_postal_code`: ZIP/postal code
- `contact_region`: State/region
- `contact_country`: Country code (ISO)
- `contact_working_hours`: Business hours
- `contact_latitude`: GPS latitude
- `contact_longitude`: GPS longitude

### Social (social_*)
- `social_telegram`: Telegram channel/group URL
- `social_vk`: VKontakte profile/group URL
- `social_instagram`: Instagram profile URL
- `social_facebook`: Facebook page URL
- `social_youtube`: YouTube channel URL
- `social_twitter`: Twitter/X profile URL
- `social_whatsapp`: WhatsApp number

### SEO (seo_*)
- `seo_title`: Default site title
- `seo_description`: Default meta description
- `seo_keywords`: Default meta keywords
- `seo_og_image`: Open Graph image URL
- `seo_og_type`: Open Graph type (website/article/product)
- `seo_site_name`: Brand/site name
- `seo_canonical_url`: Canonical base URL

### Email (smtp_*, email_*)
- `smtp_host`: SMTP server hostname
- `smtp_port`: SMTP port (25/465/587)
- `smtp_username`: SMTP login
- `smtp_password`: SMTP password
- `smtp_encryption`: Encryption type (tls/ssl)
- `smtp_from_email`: From email address
- `smtp_from_name`: From display name
- `email_notifications_enabled`: Enable email notifications
- `admin_email`: Admin notification email

### Telegram (telegram_*)
- `telegram_bot_token`: Bot API token
- `telegram_chat_id`: Chat/group ID
- `telegram_contact_url`: Public Telegram link
- `telegram_notify_new_order`: Notify on new orders
- `telegram_notify_status_change`: Notify on status changes

### Logging/Analytics (analytics_*, logging_*)
- `analytics_enabled`: Enable analytics
- `analytics_google_id`: Google Analytics tracking ID
- `analytics_yandex_id`: Yandex Metrika counter ID
- `logging_enabled`: Enable application logging
- `logging_level`: Log level (debug/info/warning/error)
- `logging_max_files`: Log retention in days

### Cache (cache_*)
- `cache_enabled`: Enable caching
- `cache_ttl`: Cache TTL in seconds
- `cache_driver`: Cache driver (file/redis/memcached)
- `cache_prefix`: Cache key prefix

## Migration from Hardcoded Values

The seeder script extracts values from your HTML files:
1. **Meta tags**: Reads `<meta>` tags from HTML `<head>`
2. **JSON-LD**: Extracts structured data from `<script type="application/ld+json">`
3. **Contact info**: Finds phone/email/address in page content
4. **Social links**: Extracts URLs from footer links

After seeding, update values in admin panel to match your actual configuration.

## Best Practices

1. **Test after changes**: Always test Telegram/Email after updating credentials
2. **Use test mode**: Test integrations in non-production environments first
3. **Monitor audit log**: Check history regularly for unauthorized changes
4. **Cache awareness**: Changes take up to 5 minutes to propagate due to caching
5. **Backup settings**: Export settings periodically via audit log
6. **Validate emails**: Use proper email format for all email fields
7. **Secure passwords**: SMTP passwords are stored in database - ensure DB security

## Troubleshooting

### Settings not updating on frontend
- **Check cache**: Wait 5 minutes for cache to expire
- **Clear browser cache**: Force-refresh (Ctrl+F5) to clear localStorage
- **Check API access**: Ensure `/api/settings.php?group=contact` returns data
- **Verify data attributes**: Ensure HTML elements have correct `data-contact` attributes

### Telegram test failing
- **Verify token**: Check bot token is correct (no spaces)
- **Check chat ID**: Ensure chat ID includes the negative sign if applicable
- **Bot added to chat**: Make sure bot is added to the group/channel
- **API accessible**: Check server can reach `api.telegram.org`

### Email test failing
- **Check SMTP config**: Verify host, port, username, password
- **Test connectivity**: Ensure server can connect to SMTP server
- **Check firewall**: Port 25/465/587 may be blocked
- **Verify credentials**: Test SMTP login separately
- **Enable debug**: Check server error logs for details

### Validation errors
- **Field too long**: Reduce length to fit max length limit
- **Invalid format**: Check email/URL format matches expected pattern
- **Out of range**: Ensure numeric values are within min/max bounds
- **Missing required**: Some fields may require values

## Related Files

- `/app/Services/SettingsService.php` - Core service with type maps and validation
- `/api/settings.php` - API endpoint for CRUD operations
- `/admin/settings.php` - Admin UI with tabbed interface
- `/admin/js/modules/settings.js` - Frontend JavaScript module
- `/js/settings-loader.js` - Public site settings loader
- `/scripts/seed-global-settings.php` - Seeder script
- `/storage/cache/settings.json` - JSON cache file
- `/docs/GLOBAL_SETTINGS.md` - This documentation

## Change Log

### v1.0 (2025-01-XX)
- Initial release
- Tabbed interface with 7 categories
- Public API access for contact/social/SEO
- Frontend auto-loader with localStorage caching
- Audit trail for all changes
- Test buttons for Telegram and Email
- Comprehensive validation and type casting
- Migration script from hardcoded HTML values
