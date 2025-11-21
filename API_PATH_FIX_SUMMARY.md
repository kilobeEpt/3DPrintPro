# API Path Duplication Fix - Summary

## Problem Description

The admin panel was experiencing 404 errors due to API path duplication:
- **Actual (broken)**: `GET https://3dprint-omsk.ru/api//api/orders.php` (404)
- **Expected (correct)**: `GET https://3dprint-omsk.ru/api/orders.php` (200)

## Root Cause

The `APIClient` class in `js/api-client.js` already includes `/api` in its `baseUrl` configuration (line 7):
```javascript
this.baseUrl = baseUrl || (typeof window.CONFIG !== 'undefined' && window.CONFIG.apiBaseUrl) || '/api';
```

The `request` method constructs URLs as: `${this.baseUrl}/${endpoint}` (line 97)

When admin modules passed endpoints with the `/api/` prefix (e.g., `/api/orders.php`), it resulted in:
```
/api + / + /api/orders.php = /api//api/orders.php
```

## Solution

Removed the `/api/` prefix from all endpoint calls in admin JavaScript files. Endpoints should now be just the filename (e.g., `orders.php`, `admin/users.php`) without the `/api/` prefix.

## Files Fixed

### 1. `/admin/js/admin-api-client.js`
**Fixed sections**: Forms API, Form Fields API, Form Submissions API
- `getForms()`: `/api/forms.php` → `forms.php`
- `getForm()`: `/api/forms.php` → `forms.php`
- `createForm()`: `/api/forms.php` → `forms.php`
- `updateForm()`: `/api/forms.php` → `forms.php`
- `deleteForm()`: `/api/forms.php` → `forms.php`
- `getFormFields()`: `/api/form-fields.php` → `form-fields.php`
- `getFormField()`: `/api/form-fields.php` → `form-fields.php`
- `createFormField()`: `/api/form-fields.php` → `form-fields.php`
- `updateFormField()`: `/api/form-fields.php` → `form-fields.php`
- `deleteFormField()`: `/api/form-fields.php` → `form-fields.php`
- `reorderFormFields()`: `/api/form-fields.php` → `form-fields.php`
- `getSubmissions()`: `/api/form-submissions.php` → `form-submissions.php`
- `getSubmission()`: `/api/form-submissions.php` → `form-submissions.php`
- `updateSubmissionStatus()`: `/api/form-submissions.php` → `form-submissions.php`
- `deleteSubmission()`: `/api/form-submissions.php` → `form-submissions.php`
- `bulkSubmissionAction()`: `/api/form-submissions.php` → `form-submissions.php`

### 2. `/admin/js/modules/orders.js`
**Fixed lines**: 174
- `loadOrders()`: `/api/orders.php` → `orders.php`

### 3. `/admin/js/modules/settings.js`
**Fixed lines**: 96, 294, 371
- `loadSettings()`: `/api/settings.php` → `settings.php`
- `saveSettings()`: `/api/settings.php` → `settings.php`
- `showAuditHistory()`: `/api/settings.php` → `settings.php`

### 4. `/admin/js/modules/users.js`
**Fixed lines**: 91, 381, 385, 412, 429, 468
- `loadUsers()`: `/api/admin/users.php` → `admin/users.php`
- `saveUser()` (update): `/api/admin/users.php` → `admin/users.php`
- `saveUser()` (create): `/api/admin/users.php` → `admin/users.php`
- `editUser()`: `/api/admin/users.php` → `admin/users.php`
- `deleteUser()`: `/api/admin/users.php` → `admin/users.php`
- `showAuditHistory()`: `/api/admin/users.php` → `admin/users.php`

### 5. `/admin/js/modules/calculator-settings.js`
**Fixed lines**: 95, 743, 748, 753, 758, 763
- `loadConfig()`: `/api/calculator-settings.php` → `calculator-settings.php`
- `saveAll()` (materials): `/api/calculator-settings.php` → `calculator-settings.php`
- `saveAll()` (services): `/api/calculator-settings.php` → `calculator-settings.php`
- `saveAll()` (quality): `/api/calculator-settings.php` → `calculator-settings.php`
- `saveAll()` (discounts): `/api/calculator-settings.php` → `calculator-settings.php`
- `saveAll()` (formulas): `/api/calculator-settings.php` → `calculator-settings.php`

### 6. `/admin/js/modules/audit.js`
**Fixed lines**: 51, 92, 106, 315, 337
- `loadUsers()`: `/api/admin/users.php` → `admin/users.php`
- `loadLogs()`: `/api/admin/audit-logs.php` → `admin/audit-logs.php`
- `loadStats()`: `/api/admin/audit-logs.php` → `admin/audit-logs.php`
- `exportLogs()`: `/api/admin/audit-logs.php` → `admin/audit-logs.php`
- `cleanupOldLogs()`: `/api/admin/audit-logs.php` → `admin/audit-logs.php`

### 7. `/admin/js/modules/order-detail.js`
**Fixed lines**: 35, 452, 478, 497, 538, 581, 603
- `openDrawer()`: `/api/orders.php` → `orders.php`
- `changeStatus()`: `/api/orders.php` → `orders.php`
- `archiveOrder()`: `/api/orders.php` → `orders.php`
- `unarchiveOrder()`: `/api/orders.php` → `orders.php`
- `addNote()`: `/api/orders.php` → `orders.php`
- `saveNote()`: `/api/orders.php` → `orders.php`
- `deleteNote()`: `/api/orders.php` → `orders.php`

## Standardized Pattern

**Correct usage**: All admin code now follows this pattern:
```javascript
// ✅ CORRECT - no /api/ prefix
await window.adminApi.get('orders.php')
await window.adminApi.post('settings.php', data)
await window.adminApi.get('admin/users.php')

// ❌ INCORRECT - do not use /api/ prefix
await window.adminApi.get('/api/orders.php')  // Creates /api//api/orders.php
```

## Verification

Verified that no `/api/` prefixes remain in admin JavaScript files:
```bash
grep -r "/api/" admin/js/modules/*.js
# Result: No matches found ✅

grep -r "/api/" admin/js/*.js
# Result: No matches found ✅
```

## Files Not Changed

The following files were **NOT modified** because they don't contain `/api/` prefixes in their adminApi calls:
- `/admin/js/modules/services.js` - Uses wrapper methods from adminApi
- `/admin/js/modules/portfolio.js` - Uses wrapper methods from adminApi
- `/admin/js/modules/testimonials.js` - Uses wrapper methods from adminApi
- `/admin/js/modules/content.js` - Uses wrapper methods from adminApi
- `/admin/js/modules/faq.js` - Uses wrapper methods from adminApi

These modules use the higher-level wrapper methods (like `getServices()`, `getPortfolio()`, etc.) which are already defined in `admin-api-client.js` and delegate to the base API client without prefixes.

## Expected Results After Fix

1. ✅ No more `/api//api/` patterns in Network tab
2. ✅ All 404 errors related to path duplication resolved
3. ✅ Admin panel data loads successfully
4. ✅ All CRUD operations (Create, Read, Update, Delete) work correctly
5. ✅ Forms, orders, users, settings, calculator, and audit features functional

## Testing Recommendations

1. **Test each admin module**:
   - Orders: List, view details, change status, add notes
   - Settings: Load, update, view audit history
   - Users: List, create, edit, delete
   - Calculator Settings: Load config, save changes
   - Audit Logs: View logs, filter, export, cleanup

2. **Monitor Network tab**: Verify all API requests show single `/api/` prefix:
   - `GET /api/orders.php` ✅
   - `POST /api/settings.php` ✅
   - `GET /api/admin/users.php` ✅

3. **Check browser console**: No 404 errors should appear

## Architecture Notes

### API Client Structure
```
js/api-client.js (Base APIClient)
    ↓ baseUrl = '/api'
    ↓ request(endpoint) → ${baseUrl}/${endpoint}
    ↓
admin/js/admin-api-client.js (AdminApiClient wrapper)
    ↓ Wraps APIClient methods
    ↓ Adds CSRF token refresh
    ↓ Normalizes responses
    ↓
admin/js/modules/*.js (Module files)
    ↓ Call adminApi methods with endpoint names
    ↓ Example: adminApi.get('orders.php')
```

### Key Principle
**The `/api` prefix is added by APIClient.request() - never add it manually in endpoint strings.**

## Date Fixed
2024-12-19
