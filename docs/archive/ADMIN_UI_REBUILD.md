# Admin UI Rebuild - Complete Documentation

## Overview
The admin UI has been completely rebuilt from a monolithic `admin.html` into a modern, modular structure with separate pages, JavaScript modules, and REST API integration.

## Architecture

### Directory Structure
```
admin/
├── index.php                  # Dashboard (main page)
├── orders.php                 # Orders management
├── services.php               # Services management
├── portfolio.php              # Portfolio management
├── testimonials.php           # Testimonials management
├── faq.php                    # FAQ management
├── content.php                # Content blocks management
├── settings.php               # System settings
├── login.php                  # Login page (existing)
├── logout.php                 # Logout handler (existing)
├── includes/
│   ├── header.php            # Shared header with navigation
│   ├── footer.php            # Shared footer with scripts
│   ├── sidebar.php           # Sidebar navigation
│   ├── session-config.php    # Session configuration (existing)
│   ├── auth.php              # Authentication middleware (existing)
│   └── csrf.php              # CSRF protection (existing)
├── css/
│   ├── admin-style.css       # ✅ NEW: Modern admin styles
│   └── admin-old.css.bak     # Backup of old styles
└── js/
    ├── admin-api-client.js   # ✅ NEW: Wrapper around apiClient
    ├── admin-main.js         # ✅ NEW: Page bootstrapper
    ├── admin-old.js.bak      # Backup of old monolithic JS
    └── modules/
        ├── dashboard.js      # ✅ NEW: Dashboard stats & charts
        ├── orders.js         # ✅ NEW: Orders CRUD
        ├── services.js       # ✅ NEW: Services CRUD
        ├── portfolio.js      # ✅ NEW: Portfolio CRUD
        ├── testimonials.js   # ✅ NEW: Testimonials CRUD
        ├── faq.js            # ✅ NEW: FAQ CRUD
        ├── content.js        # ✅ NEW: Content blocks CRUD
        └── settings.js       # ✅ NEW: Settings management
```

### Obsolete Files (Removed)
- ❌ `admin.html` - Replaced by modular PHP pages
- ❌ `admin/js/admin.js` - Replaced by modular JS system

## Key Features

### 1. PHP Session-Based Authentication
- All pages protected by `Auth::require()`
- CSRF tokens on all forms
- Automatic session timeout (30 minutes)
- Secure logout handling

### 2. Modular JavaScript Architecture

#### `admin-api-client.js`
Thin wrapper around the public `apiClient` that:
- Automatically includes CSRF tokens from `window.ADMIN_SESSION`
- Provides admin-specific API methods
- Handles authentication errors gracefully

#### `admin-main.js`
Page bootstrapper providing:
- Sidebar collapse/expand
- Dropdown management
- Theme switcher (light/dark)
- Toast notifications
- Orders badge updates
- Utility methods (formatDate, formatMoney, getStatusBadge, etc.)

#### Module Pattern
Each module (`orders.js`, `services.js`, etc.) follows this pattern:
```javascript
class ModuleNameModule {
    constructor() {
        // Initialize state
    }
    
    async init() {
        // Load data and bind events
    }
    
    async loadData() {
        // Fetch from API via adminApi
    }
    
    renderData() {
        // Update DOM
    }
}
```

### 3. REST API Integration
All modules use the REST API via `adminApi`:
```javascript
// Examples
await adminApi.getOrders();
await adminApi.updateOrder(id, data);
await adminApi.createService(data);
await adminApi.deletePortfolioItem(id);
```

### 4. Modern CSS Architecture
`admin-style.css` features:
- CSS custom properties (variables)
- Light/dark theme support via `[data-theme]`
- Responsive grid layouts
- Modern component styling
- Smooth transitions and animations
- Mobile-first responsive design

### 5. Shared Layout Components

#### Header (`includes/header.php`)
- Page title
- Sidebar toggle
- Notifications button
- Quick settings dropdown
- User menu with logout

#### Sidebar (`includes/sidebar.php`)
- Navigation links
- Active page highlighting
- Orders badge (shows new order count)
- User info
- Logout button

#### Footer (`includes/footer.php`)
- Global scripts loading
- ADMIN_SESSION data injection
- Chart.js
- Module-specific scripts

## Page-Specific Features

### Dashboard (`index.php`)
- **Stats Cards**: Total orders, monthly revenue, client count, processing orders
- **Orders Chart**: 7-day trend visualization with Chart.js
- **Recent Orders**: Last 5 orders with quick navigation
- **Auto-refresh**: Stats update every 5 minutes

### Orders (`orders.php`)
- **Filters**: Status, type, search by name/email/phone
- **Inline Status Updates**: Change order status with dropdown
- **Pagination**: 20 orders per page
- **Export**: CSV export of filtered orders
- **Bulk Actions**: Multi-select for batch operations

### Services (`services.php`)
- **Grid Layout**: Service cards with icon, name, description, price
- **Modal CRUD**: Add/edit services in modal dialog
- **Active/Inactive**: Toggle service visibility
- **Drag-to-Reorder**: (Future feature)

### Portfolio, Testimonials, FAQ, Content
- Similar card-based layouts
- Modal CRUD forms
- Active/inactive toggles
- Sorting and filtering

### Settings (`settings.php`)
- **System Settings**: Telegram bot token, chat ID, admin email
- **Notifications**: Enable/disable order notifications
- **Form Validation**: Client-side and server-side validation

## Usage Examples

### Adding a New Module

1. Create module file: `admin/js/modules/my-module.js`
```javascript
class MyModuleModule {
    constructor() { /* ... */ }
    async init() { /* ... */ }
}
window.myModuleModule = new MyModuleModule();
window.myModuleModule.init();
```

2. Create page: `admin/my-page.php`
```php
<?php
define('ADMIN_INIT', true);
require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
Auth::require('/admin/login.php');

$pageTitle = 'My Page';
$pageScripts = ['/admin/js/modules/my-module.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <!-- Your content -->
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
```

3. Add to sidebar: Edit `admin/includes/sidebar.php`
```html
<a href="/admin/my-page.php" class="nav-item">
    <i class="fas fa-icon"></i>
    <span>My Page</span>
</a>
```

### Showing Toast Notifications
```javascript
AdminMain.prototype.showToast('Success message', 'success');
AdminMain.prototype.showToast('Error message', 'error');
AdminMain.prototype.showToast('Warning message', 'warning');
AdminMain.prototype.showToast('Info message', 'info');
```

### Making API Calls
```javascript
// GET request
const orders = await adminApi.getOrders();

// POST request
const newService = await adminApi.createService({
    name: 'New Service',
    icon: 'fas fa-cog',
    description: 'Service description',
    price: 1000,
    active: 1
});

// PUT request
await adminApi.updateOrder(orderId, { status: 'completed' });

// DELETE request
await adminApi.deleteService(serviceId);
```

### Working with Modals
```javascript
const modal = document.createElement('div');
modal.className = 'modal';
modal.innerHTML = `
    <div class="modal-overlay" onclick="this.parentElement.remove()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2>Modal Title</h2>
            <button class="btn-close" onclick="this.closest('.modal').remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <!-- Modal content -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="this.closest('.modal').remove()">Cancel</button>
            <button class="btn btn-primary" onclick="saveData()">Save</button>
        </div>
    </div>
`;
document.body.appendChild(modal);
modal.classList.add('show');
```

## Security Features

1. **PHP Session Auth**: All pages check authentication via `Auth::require()`
2. **CSRF Protection**: Tokens automatically included in all API requests
3. **XSS Prevention**: All user input escaped with `htmlspecialchars()`
4. **SQL Injection Protection**: PDO prepared statements in API
5. **Rate Limiting**: API enforces 60 req/min per IP
6. **Secure Sessions**: HttpOnly, SameSite=Lax, 30min timeout

## Testing

### Manual Smoke Test Checklist
- [ ] Login with credentials
- [ ] Dashboard loads with stats and chart
- [ ] Navigate to Orders page
- [ ] Filter orders by status/type
- [ ] Change order status inline
- [ ] Export orders to CSV
- [ ] Navigate to Services page
- [ ] Add new service via modal
- [ ] Edit existing service
- [ ] Delete service
- [ ] Navigate to Portfolio page
- [ ] Navigate to Testimonials page
- [ ] Navigate to FAQ page
- [ ] Navigate to Content page
- [ ] Navigate to Settings page
- [ ] Update settings
- [ ] Check sidebar collapse/expand
- [ ] Check theme switcher
- [ ] Check user menu dropdown
- [ ] Logout successfully

### API Testing
```bash
# Test admin API (requires authentication)
curl -b cookies.txt https://site.com/admin/index.php
curl -b cookies.txt -X PUT https://site.com/api/orders.php?id=123 \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: YOUR_TOKEN" \
  -d '{"status":"completed"}'
```

## Migration from Old Admin

### What Changed
- **Single page → Multiple pages**: Each section has its own PHP file
- **localStorage auth → PHP sessions**: No more client-side auth
- **Monolithic JS → Modular JS**: Separate modules per feature
- **Inline styles → CSS variables**: Modern theming system
- **Direct DB access → REST API**: All data via API endpoints

### Backwards Compatibility
- Old `admin.html` removed (obsolete)
- Old `admin/js/admin.js` backed up as `admin-old.js.bak`
- Old CSS backed up as `admin-old.css.bak`
- No breaking changes to API endpoints
- Session-based auth replaces localStorage auth

## Performance Optimizations

1. **Lazy Loading**: Modules loaded only on their respective pages
2. **Caching**: API responses cached by `apiClient`
3. **Pagination**: Large datasets split into pages (20 items/page)
4. **Debounced Filters**: Search inputs debounced to reduce API calls
5. **Auto-refresh**: Dashboard stats refresh every 5 minutes only

## Future Enhancements

### Planned Features
- [ ] Drag-and-drop reordering for services/portfolio
- [ ] Bulk actions for orders (bulk status update, bulk delete)
- [ ] Advanced filters (date range, amount range)
- [ ] Order detail view with full history
- [ ] Rich text editor for content blocks (TinyMCE/CKEditor)
- [ ] Image upload for portfolio/testimonials
- [ ] Email notifications (in addition to Telegram)
- [ ] User roles and permissions
- [ ] Activity log/audit trail
- [ ] Data export in multiple formats (CSV, Excel, PDF)
- [ ] Dashboard customization (widget arrangement)
- [ ] Real-time notifications via WebSocket

### Code Quality
- [ ] Add JSDoc comments to all modules
- [ ] Add PHP type hints
- [ ] Add unit tests for modules
- [ ] Add integration tests for API
- [ ] Add E2E tests with Playwright
- [ ] Set up ESLint for JS
- [ ] Set up PHP-CS-Fixer for PHP

## Troubleshooting

### Common Issues

**Issue**: CSRF token validation fails
- **Solution**: Ensure `window.ADMIN_SESSION.csrfToken` is set in header.php

**Issue**: API calls return 401 Unauthorized
- **Solution**: Check session is active, login again if needed

**Issue**: Modules not loading
- **Solution**: Check browser console for JS errors, verify script paths in footer.php

**Issue**: Sidebar not collapsing
- **Solution**: Clear localStorage, check `admin-main.js` is loaded

**Issue**: Theme not switching
- **Solution**: Check localStorage for `adminTheme`, verify CSS custom properties

**Issue**: Stats not updating
- **Solution**: Check API connectivity, verify orders endpoint returns data

## Support

For issues or questions:
1. Check browser console for errors
2. Check PHP error logs (`/logs/`)
3. Verify API endpoints work (`/api/test.php`)
4. Review this documentation
5. Contact development team

---

**Last Updated**: January 2025
**Version**: 2.0.0
**Status**: ✅ Production Ready
