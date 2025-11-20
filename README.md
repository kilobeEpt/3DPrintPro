# 3D Print Pro - Омск

Профессиональный сервис 3D печати с интерактивным калькулятором, админ-панелью, PHP REST API и интеграцией с Telegram.

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-orange)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-Proprietary-red)]()

---

## 🚀 Quick Start

### For New Installations

1. **Install Dependencies** → Run `composer install` in project root
2. **Setup Database & Backend** → See [docs/SETUP_GUIDE.md](docs/SETUP_GUIDE.md)
3. **Deploy to Production** → See [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)
4. **Configure Admin Panel** → See [docs/ADMIN_GUIDE.md](docs/ADMIN_GUIDE.md)
5. *(Optional)* **Use Eloquent ORM** → See [docs/ELOQUENT_SETUP.md](docs/ELOQUENT_SETUP.md)

### For Existing Installations

1. **Access admin panel:** `https://your-domain.com/admin/login.php`
2. **Configure Telegram:** Settings → Telegram Configuration
3. **Manage content:** Edit services, portfolio, FAQ via admin panel

**Total setup time:** ~10 minutes

---

## 📚 Documentation

### Core Guides

| Document | Description |
|----------|-------------|
| **[SETUP_GUIDE.md](docs/SETUP_GUIDE.md)** | Complete installation and configuration guide |
| **[DEPLOYMENT.md](docs/DEPLOYMENT.md)** | Production deployment checklist and procedures |
| **[API_REFERENCE.md](docs/API_REFERENCE.md)** | REST API endpoints and usage documentation |
| **[ADMIN_GUIDE.md](docs/ADMIN_GUIDE.md)** | Admin panel features and usage instructions |
| **[DATABASE_SCHEMA.md](docs/DATABASE_SCHEMA.md)** | Database tables, columns, and relationships |
| **[TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md)** | Common issues and solutions |

### Feature Guides

- **[FORMS_SYSTEM.md](docs/FORMS_SYSTEM.md)** - Complete form builder guide with field types and validation
- **[REAL_TIME_SYNC_GUIDE.md](docs/REAL_TIME_SYNC_GUIDE.md)** - SSE and IndexedDB caching system
- **[CALCULATOR_SETTINGS.md](docs/CALCULATOR_SETTINGS.md)** - Dynamic calculator configuration
- **[GLOBAL_SETTINGS.md](docs/GLOBAL_SETTINGS.md)** - Centralized settings management
- **[CONTENT_SYNC_SSE.md](docs/CONTENT_SYNC_SSE.md)** - Real-time content synchronization
- **[ORDERS_API_V2.md](docs/ORDERS_API_V2.md)** - Enhanced orders with status history and notes
- **[SECURITY.md](docs/SECURITY.md)** - Comprehensive security guide

### Technical Documentation

- **[ELOQUENT_SETUP.md](docs/ELOQUENT_SETUP.md)** - Eloquent ORM integration and usage guide
- **[SETTINGS_SERVICE.md](docs/SETTINGS_SERVICE.md)** - Settings service with caching and audit logging
- **[MIGRATION_GUIDE.md](docs/MIGRATION_GUIDE.md)** - Guide for migrating from legacy DB class to Eloquent
- **[TELEGRAM_INTEGRATION.md](docs/TELEGRAM_INTEGRATION.md)** - Telegram bot setup and configuration
- **[RBAC_AUTHENTICATION.md](docs/RBAC_AUTHENTICATION.md)** - Role-based access control system (v4.0)
- **[RBAC_MIGRATION_GUIDE.md](docs/RBAC_MIGRATION_GUIDE.md)** - Migrate from old to new auth system

### Testing & QA

- **[TESTING.md](docs/TESTING.md)** - Complete testing guide with PHPUnit and smoke tests
- **[QA_REGRESSION.md](docs/QA_REGRESSION.md)** - Manual test cases for comprehensive QA
- **[TEST_CHECKLIST.md](docs/TEST_CHECKLIST.md)** - Testing procedures and checklist

---

## ✨ Features

### For Customers
- ✅ Interactive price calculator with real-time updates
- ✅ Responsive design (mobile, tablet, desktop)
- ✅ Contact forms with validation
- ✅ Service catalog with detailed descriptions
- ✅ Portfolio showcase
- ✅ Customer testimonials
- ✅ FAQ section

### For Business Owners
- ✅ **Admin Panel** - Full-featured dashboard at `/admin`
- ✅ **Order Management** - View, update, and track orders
- ✅ **Content Management** - Edit services, portfolio, FAQ, testimonials
- ✅ **Settings Service** - Centralized settings with caching and audit logging
- ✅ **Telegram Notifications** - Instant notifications for new orders
- ✅ **Database-Driven** - All data stored in MySQL
- ✅ **RBAC Authentication** - Role-based access with database-backed sessions
- ✅ **Audit Logging** - Complete trail of admin actions
- ✅ **Statistics Dashboard** - Orders, revenue, and trends
- ✅ **CSV Export** - Download orders as spreadsheet

---

## 🏗️ Architecture

### Technology Stack

**Frontend:**
- HTML5, CSS3, Vanilla JavaScript (ES6+)
- Responsive design with mobile-first approach
- Font Awesome icons
- Chart.js for statistics

**Backend:**
- PHP 7.4+ with PDO and Eloquent ORM
- MySQL 8.0+ database
- RESTful API architecture
- Composer for dependency management
- Rate limiting (60 req/min)

**Security:**
- PDO prepared statements (SQL injection protection)
- XSS protection (htmlspecialchars)
- CSRF tokens on admin operations (session-bound)
- Session security (HttpOnly, SameSite, Secure)
- Login rate limiting (5 attempts, 15-min lockout)
- Password hashing (bcrypt with PASSWORD_BCRYPT)
- Role-based access control (super_admin/admin/editor)
- Audit logging (all admin actions tracked)

### Project Structure

```
/
├── admin/              # Admin panel (PHP session-based)
│   ├── login.php       # Login page
│   ├── index.php       # Dashboard
│   ├── orders.php      # Order management
│   ├── services.php    # Service management
│   ├── settings.php    # Configuration
│   ├── css/            # Admin styles
│   ├── js/             # Admin JavaScript
│   └── includes/       # Auth & session management
│
├── api/                # REST API endpoints
│   ├── orders.php      # Orders CRUD
│   ├── services.php    # Services CRUD
│   ├── portfolio.php   # Portfolio CRUD
│   ├── testimonials.php # Testimonials CRUD
│   ├── faq.php         # FAQ CRUD
│   ├── content.php     # Content blocks CRUD
│   ├── settings.php    # Settings management
│   ├── telegram-test.php # Telegram testing
│   ├── test.php        # API health check
│   ├── config.php      # Database credentials (not in git)
│   └── helpers/        # Shared utilities
│
├── database/           # Database schema and utilities
│   ├── schema.sql      # Complete database schema
│   ├── seed-data.php   # Initial data
│   ├── verify-schema.php # Schema validation
│   └── backup.php      # Backup utility
│
├── scripts/            # Utility scripts
│   ├── create-admin.php # Create admin users (RBAC)
│   ├── setup-admin-credentials.php # Legacy admin setup
│   ├── db_audit.php    # Database diagnostics
│   └── api_smoke_test.php # API testing
│
├── docs/               # Documentation
│   ├── SETUP_GUIDE.md
│   ├── DEPLOYMENT.md
│   ├── API_REFERENCE.md
│   ├── ADMIN_GUIDE.md
│   ├── DATABASE_SCHEMA.md
│   └── TROUBLESHOOTING.md
│
├── css/                # Stylesheets
│   ├── style.css       # Base styles
│   ├── responsive.css  # Responsive/mobile styles
│   └── animations.css  # Animations
│
├── js/                 # JavaScript
│   ├── api-client.js   # API wrapper with retry logic
│   ├── database.js     # Database abstraction
│   ├── calculator.js   # Price calculator
│   ├── utils.js        # Shared utilities
│   ├── status-indicator.js # Connectivity status
│   └── main.js         # Main application
│
├── *.html              # Public pages
│   ├── index.html      # Homepage
│   ├── services.html   # Services
│   ├── portfolio.html  # Portfolio
│   ├── about.html      # About
│   ├── contact.html    # Contact
│   └── ...
│
├── config.js           # Frontend configuration
├── robots.txt          # SEO: Robots
└── sitemap.xml         # SEO: Sitemap
```

### Database Schema

16 tables storing all application data:

**Content Management:**
- `orders` - Customer orders and contact forms
- `settings` - Application configuration
- `services` - Service offerings
- `portfolio` - Project showcase
- `testimonials` - Customer reviews
- `faq` - FAQ items
- `content_blocks` - Dynamic page content

**Forms System (v3.0):**
- `forms` - Dynamic form definitions
- `form_fields` - Form field configurations
- `form_submissions` - Form submission records
- `form_submission_values` - Individual field values
- `settings_audit` - Settings change audit log

**RBAC Authentication (v4.0):**
- `admin_users` - Admin user accounts with roles
- `admin_sessions` - Persistent session storage
- `admin_login_attempts` - Login attempt tracking
- `admin_action_logs` - Admin action audit trail

See [docs/DATABASE_SCHEMA.md](docs/DATABASE_SCHEMA.md) for complete schema reference.

### API Endpoints

| Endpoint | Methods | Auth | Description |
|----------|---------|------|-------------|
| `/api/orders.php` | GET, POST, PUT, DELETE | POST=Public, Others=Admin | Order management |
| `/api/services.php` | GET, POST, PUT, DELETE | GET=Public, Others=Admin | Service management |
| `/api/portfolio.php` | GET, POST, PUT, DELETE | GET=Public, Others=Admin | Portfolio management |
| `/api/testimonials.php` | GET, POST, PUT, DELETE | GET=Public, Others=Admin | Testimonials management |
| `/api/faq.php` | GET, POST, PUT, DELETE | GET=Public, Others=Admin | FAQ management |
| `/api/content.php` | GET, POST, PUT, DELETE | GET=Public, Others=Admin | Content blocks management |
| `/api/settings.php` | GET, POST, PUT, DELETE | All=Admin | Settings management |
| `/api/telegram-test.php` | POST | Admin | Telegram connection test |
| `/api/test.php` | GET | Public | API health check |

See [docs/API_REFERENCE.md](docs/API_REFERENCE.md) for complete API documentation.

---

## 🔧 Requirements

- **Web Server:** Apache 2.4+ or Nginx 1.18+ with mod_rewrite/rewrite module
- **PHP:** 7.4 or higher with extensions:
  - PDO
  - PDO_MySQL
  - cURL
  - mbstring
  - JSON
- **Database:** MySQL 8.0+ or MariaDB 10.5+
- **HTTPS:** SSL certificate (required for secure sessions)

---

## 📦 Installation

### Quick Installation (7 Minutes)

```bash
# 1. Upload files to server
scp -r * user@your-server:/var/www/html/

# 2. Create database and import schema
mysql -u root -p
CREATE DATABASE your_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit
mysql -u user -p your_db < database/schema.sql

# 3. Configure backend
cp api/config.example.php api/config.php
nano api/config.php  # Edit credentials

# 4. Setup admin user (RBAC v4.0)
php scripts/create-admin.php

# 5. Seed initial data
curl https://your-domain.com/api/init-database.php

# 6. Verify installation
curl https://your-domain.com/api/test.php?audit=full
```

See [docs/SETUP_GUIDE.md](docs/SETUP_GUIDE.md) for detailed installation instructions.

---

## 🔐 Security

### Built-in Security Features

- ✅ SQL injection protection via PDO prepared statements
- ✅ XSS protection via htmlspecialchars()
- ✅ CSRF token validation on all state-changing operations
- ✅ Password hashing with bcrypt
- ✅ Secure PHP sessions (HttpOnly, SameSite, Secure)
- ✅ Login rate limiting (5 attempts, 15-minute lockout)
- ✅ API rate limiting (60 requests/minute per IP)
- ✅ Security headers (X-Frame-Options, X-Content-Type-Options, etc.)
- ✅ Protected configuration files via .htaccess

### Security Best Practices

1. **Change default credentials** immediately after installation
2. **Use HTTPS** for all traffic (Let's Encrypt recommended)
3. **Set strong admin passwords** (12+ characters, mixed case, numbers, symbols)
4. **Keep backups** of database and files
5. **Monitor logs** regularly for suspicious activity
6. **Update PHP/MySQL** to latest stable versions
7. **Restrict admin access** to specific IPs if possible

See [docs/ADMIN_AUTHENTICATION.md](docs/ADMIN_AUTHENTICATION.md) for security details.

---

## 🧪 Testing

### Automated Tests (PHPUnit)

```bash
# Run all unit and integration tests
composer test

# Run specific test suites
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Integration

# Run with coverage (requires xdebug)
composer test-coverage

# Run specific test file
vendor/bin/phpunit tests/Unit/SettingsServiceTest.php
```

**Unit Test Coverage:**
- ✅ **Settings Service** - Typed casting, caching, audit logging, validation
- ✅ **Form Validation** - Field types, rules, relationships, scopes
- ✅ **Admin Auth Service** - Authentication, rate limiting, CSRF, session management
- ✅ **Content Controllers** - Slug generation, featured content, media metadata
- ✅ **CSRF Protection** - Token generation, validation, rotation
- ✅ **Rate Limiter** - Profiles, limits, violations, cleanup
- ✅ **Order Export** - CSV/PDF generation, signed URLs, field selection
- ✅ **Formula Validator** - Calculator formula validation and security
- ✅ **Media Upload** - File validation, size limits, MIME types

**Integration Test Coverage:**
- ✅ **Form Submission** - End-to-end submission flow, order linking, status updates
- ✅ **Form Builder** - Field management, conditional logic, notifications
- ✅ **Admin Auth** - Login/logout flow, lockout, audit logging
- ✅ **Orders Flow** - Status history, notes, archiving, exports
- ✅ **Content API** - CRUD operations, slugs, featured content, cache
- ✅ **Calculator Settings** - Configuration management, validation, caching
- ✅ **Base API Controller** - Pagination, validation, slug management

### Smoke Tests

```bash
# Admin authentication smoke test
php scripts/admin-auth-smoke.php

# Content API smoke test (all content types)
php scripts/content-api-smoke.php

# Orders export service smoke test
php scripts/orders-export-smoke.php

# Form API smoke test
php scripts/form-api-smoke.php

# Orders domain smoke test
php scripts/orders-smoke-test.php

# Settings service smoke test
php scripts/test-settings-service.php

# Eloquent ORM smoke test
php scripts/eloquent-smoke.php

# General API smoke test
php scripts/api_smoke.php
```

**Smoke Test Coverage:**
- ✅ **Admin Auth** - Login, rate limiting, lockout, session management, CSRF
- ✅ **Content API** - Services, portfolio, FAQ, testimonials, content blocks
- ✅ **Orders Export** - CSV/PDF generation, signed URLs, filters, field selection
- ✅ **Form API** - Form builder, validation, submission processing
- ✅ **Orders Domain** - Status history, notes, archiving, filtering
- ✅ **Settings** - Cache operations, type casting, validation, audit
- ✅ **Eloquent** - Model CRUD, relationships, scopes

### Manual Testing & QA

**Quick Smoke Test (5 minutes):**
1. Login to admin panel
2. Create/edit a service
3. Submit contact form on public site
4. View new order in admin
5. Export orders to CSV

**Full Regression Test (2 hours):**
- See [docs/QA_REGRESSION.md](docs/QA_REGRESSION.md) for complete manual test cases
- Covers admin panel, forms, orders, security, real-time sync, and more

**Testing Checklist:**
- See [docs/TEST_CHECKLIST.md](docs/TEST_CHECKLIST.md) for systematic testing procedures

---

## 🐛 Troubleshooting

### Quick Fixes

| Issue | Solution |
|-------|----------|
| Database connection failed | Check credentials in `api/config.php` |
| Tables not found | Run `mysql -u user -p db < database/schema.sql` |
| No data showing | Run `https://your-domain.com/api/init-database.php` |
| Cannot login to admin | Run `php scripts/create-admin.php` to create user |
| Telegram test fails | Check bot token and chat ID in settings |
| Forms not submitting | Check browser console for errors |
| Session expired immediately | Use HTTPS and enable cookies |
| Account locked out | Wait 15 min or manual unlock in `admin_users` table |

See [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) for detailed troubleshooting guide.

---

## 📊 Production Deployment

### Pre-Deployment Checklist

- [ ] All files uploaded
- [ ] Database created and seeded
- [ ] `api/config.php` configured
- [ ] Admin credentials set
- [ ] HTTPS enabled
- [ ] Telegram configured
- [ ] Forms tested
- [ ] Mobile responsive verified
- [ ] SEO tags configured

### Deployment Steps

1. Upload files via FTP/SFTP
2. Import database schema
3. Configure `api/config.php`
4. Setup admin credentials
5. Seed initial data
6. Configure Telegram
7. Test thoroughly
8. Go live!

See [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) for complete deployment guide.

---

## 🔄 Maintenance

### Regular Tasks

**Daily:**
- Check new orders in admin panel
- Monitor Telegram notifications

**Weekly:**
- Review error logs: `tail -f logs/api.log`
- Update content via admin panel
- Check database backups

**Monthly:**
- Change admin password
- Optimize database tables
- Review security logs
- Update services/pricing

### Backup

```bash
# Database backup
php database/backup.php

# Or manual backup
mysqldump -u user -p database > backup_$(date +%Y%m%d).sql
```

---

## 📞 Support

### Documentation

- [Setup Guide](docs/SETUP_GUIDE.md) - Installation and configuration
- [Deployment Guide](docs/DEPLOYMENT.md) - Production deployment
- [API Reference](docs/API_REFERENCE.md) - API documentation
- [Admin Guide](docs/ADMIN_GUIDE.md) - Admin panel usage
- [Database Schema](docs/DATABASE_SCHEMA.md) - Database reference
- [Troubleshooting](docs/TROUBLESHOOTING.md) - Common issues

### Diagnostics

```bash
# Full database audit
php scripts/db_audit.php

# API health check
curl https://your-domain.com/api/test.php?audit=full

# Check logs
tail -f logs/api.log
```

---

## 📝 License

Proprietary. All rights reserved.

---

## 🎉 Credits

**Version:** 4.0 (January 2025)  
**Architecture:** Complete rewrite with MySQL + PHP REST API  
**Status:** Production Ready ✅

**Features:**
- 16-table database architecture (content + forms + RBAC)
- 8 REST API endpoints with rate limiting
- Role-based access control with audit logging
- Admin panel with modular JavaScript
- Telegram integration with database-driven config
- PHPUnit test suite with 40+ tests
- Complete documentation suite

---

**Made with ❤️ for 3D printing enthusiasts in Omsk**
