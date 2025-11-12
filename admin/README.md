# Admin Panel Assets

This directory contains assets specific to the admin panel (`admin.html`).

## Structure

```
admin/
├── css/
│   └── admin.css       # Admin panel styles
├── js/
│   └── admin.js        # Admin panel logic
└── README.md           # This file
```

## Usage

The admin panel is accessible at `/admin.html` and uses these assets:
- **CSS**: `admin/css/admin.css` - Complete styling for the admin interface
- **JavaScript**: `admin/js/admin.js` - Admin functionality including CRUD operations, charts, and authentication

## Dependencies

The admin panel also relies on shared assets:
- `css/animations.css` - Shared animations
- `js/database.js` - Database wrapper
- `js/validators.js` - Form validation
- `js/telegram.js` - Telegram notifications
- `config.js` - Configuration

## Development

When modifying admin-specific code, ensure changes are made to files in this directory.
Shared functionality should remain in the main `/js` and `/css` directories.
