# Quick Reference - Repository Layout

## Essential Files (Root Directory)

- **README.md** - Main project documentation
- **START_HERE.md** - Quick 7-minute setup guide
- **config.js** - Configuration settings

## Directory Structure

```
📁 admin/          → Admin panel assets (CSS, JS)
📁 api/            → PHP REST API endpoints
📁 css/            → Public site stylesheets
📁 database/       → Schema, seeds, migrations
📁 docs/           → All documentation (69 files)
📁 js/             → Public site JavaScript
📁 logs/           → Runtime logs (gitignored)
📁 scripts/        → Utility scripts
```

## HTML Pages (9 total)

- index.html - Home page
- admin.html - Admin panel
- contact.html - Contact form
- about.html, blog.html, districts.html, portfolio.html, services.html, why-us.html

## Finding Documentation

All documentation is now in the `docs/` folder. Key docs:

- **docs/README.md** - Complete documentation index
- **docs/DATABASE_ARCHITECTURE.md** - Database structure
- **docs/DEPLOYMENT_CHECKLIST.md** - Deployment guide
- **docs/TESTING_GUIDE.md** - Testing procedures
- **docs/CLEANUP_FINAL_REPORT.md** - Latest cleanup summary

## Admin Panel

**Location:** `/admin.html`

**Assets:**
- CSS: `admin/css/admin.css`
- JS: `admin/js/admin.js`

**Dependencies:**
- css/animations.css
- js/database.js, js/validators.js, js/telegram.js
- config.js

## Development Commands

See `START_HERE.md` for detailed setup instructions.

## Recent Changes (This Branch)

- ✅ Removed backup files (.backup, .bak)
- ✅ Deleted diagnostic tools (layout-test.html, validate-layout.js)
- ✅ Reorganized admin assets into admin/ folder
- ✅ Consolidated 66 docs to docs/ folder
- ✅ Updated .gitignore for runtime files
- ✅ Root directory reduced from 92 to 22 items

**For full details:** See `docs/CLEANUP_FINAL_REPORT.md`
