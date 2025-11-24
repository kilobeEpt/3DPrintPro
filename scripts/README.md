# Scripts Directory

Utility scripts for database operations, seeding, testing, deployment, and maintenance.

## Table of Contents

- [Database Operations](#database-operations)
- [Data Seeding](#data-seeding)
- [Testing & Validation](#testing--validation)
- [Deployment](#deployment)
- [Admin Management](#admin-management)
- [Migrations](#migrations)

---

## Database Operations

### provision-database.php

Comprehensive database provisioning and setup.

```bash
# Full setup with seeding
php scripts/provision-database.php --seed

# Create database only
php scripts/provision-database.php --create-only

# Import schema only
php scripts/provision-database.php --import-only

# Force recreate (DESTRUCTIVE)
php scripts/provision-database.php --force --seed
```

**Features:**
- Creates database with UTF8MB4 collation
- Creates restricted application user
- Imports schema (18 tables)
- Seeds baseline data
- Verifies schema integrity

**Documentation:** [docs/DATABASE_OPERATIONS.md](../docs/DATABASE_OPERATIONS.md)

### db_audit.php

Database health check and diagnostics.

```bash
php scripts/db_audit.php
```

**Checks:**
- Connection status
- Table existence (18 tables)
- Row counts
- Index status
- Foreign key constraints
- Character set and collation

---

## Data Seeding

### seed-demo-data.php ⭐ NEW

**Comprehensive demo data seeder** - populates all public and admin surfaces with realistic sample data.

```bash
# Full demo data (recommended)
php scripts/seed-demo-data.php

# Force reseed (DESTRUCTIVE - truncates existing data)
php scripts/seed-demo-data.php --force

# Skip specific sections
php scripts/seed-demo-data.php --skip-orders --skip-admin-users

# Verbose output
php scripts/seed-demo-data.php --verbose
```

**Creates:**
- 8 services (FDM, SLA, SLS, modeling, etc.)
- 8 portfolio items (various categories)
- 10 testimonials (5-star Russian reviews)
- 12 FAQ entries (common questions)
- 7 content blocks (hero, features, about)
- 8 orders with history and notes (various statuses)
- Form submissions (linked to orders)
- Order status history (status tracking)
- Order notes (admin comments)
- 3 demo admin users (optional)

**Flags:**
- `--force` - Truncate and reseed (⚠️ DESTRUCTIVE)
- `--skip-settings` - Skip global settings
- `--skip-calculator` - Skip calculator settings
- `--skip-forms` - Skip forms
- `--skip-services` - Skip services
- `--skip-portfolio` - Skip portfolio
- `--skip-testimonials` - Skip testimonials
- `--skip-faq` - Skip FAQ
- `--skip-content` - Skip content blocks
- `--skip-orders` - Skip orders and submissions
- `--skip-admin-users` - Skip demo admin accounts
- `--verbose` - Detailed output

**Demo Admin Credentials:**
- `admin@3dprint-omsk.ru` / `admin123` (super_admin)
- `manager@3dprint-omsk.ru` / `manager123` (admin)
- `editor@3dprint-omsk.ru` / `editor123` (editor)

⚠️ **Change passwords in production!**

**Logging:** `storage/logs/seed_demo_data.log`

**Exit codes:** 0 (success), 1 (errors), 2 (invalid usage)

**Use cases:**
- Quick dev environment setup
- QA pre-testing data preparation
- Demo environment population
- Testing specific features

**Documentation:** [docs/DATABASE_OPERATIONS.md - Demo Data Seeding](../docs/DATABASE_OPERATIONS.md#demo-data-seeding)

---

### seed-global-settings.php

Seeds default global settings (contact, social, SEO, SMTP, Telegram, etc.).

```bash
php scripts/seed-global-settings.php
```

**Creates:** 70+ settings including contact info, social links, SEO metadata, email/Telegram config placeholders.

---

### seed-calculator-settings.php

Seeds calculator configuration (materials, services, quality, discounts, formulas).

```bash
# Initial seed
php scripts/seed-calculator-settings.php

# Overwrite existing
php scripts/seed-calculator-settings.php --force
```

**Creates:** Materials (PLA, ABS, PETG, resins, nylon), services (modeling, post-processing), quality multipliers, discount tiers, pricing formulas.

---

### seed-forms.php

Seeds default forms (contact, order) with fields and validation rules.

```bash
php scripts/seed-forms.php
```

**Creates:** Contact form (6 fields), Order form (7 fields) with validation rules and notification settings.

---

### seed-order-status-history.php

Backfills order status history for existing orders (migration helper).

```bash
# Dry run
php scripts/seed-order-status-history.php --dry-run

# Execute
php scripts/seed-order-status-history.php
```

---

## Testing & Validation

### api_smoke.php (v2.0)

Comprehensive API endpoint testing with admin authentication.

```bash
# Read-only mode (safe for production)
php scripts/api_smoke.php --url=https://3dprint-omsk.ru --readonly

# Full CRUD mode (dev/staging only)
php scripts/api_smoke.php --url=http://localhost:8000 \
  --admin-email=admin@test.com --admin-password=pass123

# Verbose output
php scripts/api_smoke.php --url=http://localhost --verbose
```

**Documentation:** [scripts/README_API_SMOKE.md](README_API_SMOKE.md)

---

### hosting-audit.php

Validates hosting environment readiness before deployment.

```bash
# Standard audit
php scripts/hosting-audit.php

# Strict mode (warnings = failures)
php scripts/hosting-audit.php --strict

# JSON output for CI/CD
php scripts/hosting-audit.php --format=json

# Skip Redis checks (shared hosting)
php scripts/hosting-audit.php --skip-redis
```

**Checks:** PHP version, extensions, CLI tools, services, disk/memory, permissions.

**Documentation:** [docs/HOSTING_AUDIT.md](../docs/HOSTING_AUDIT.md)

---

### eloquent-smoke.php

Tests Eloquent ORM setup, facades, and basic operations.

```bash
php scripts/eloquent-smoke.php
```

**Tests:** DB facade, Schema facade, Capsule, model CRUD, relationships (17 tests).

---

### Other Testing Scripts

- `content-api-smoke.php` - Tests content API endpoints
- `form-api-smoke.php` - Tests forms API
- `orders-smoke-test.php` - Tests orders domain
- `orders-export-smoke.php` - Tests order exports
- `admin-auth-smoke.php` - Tests admin authentication
- `test-admin-carbon.php` - Validates Carbon usage
- `verify-facade-fix.php` - Quick facade verification

---

## Deployment

### deploy.sh

Automated production deployment with validation and testing.

```bash
# Full deployment
bash scripts/deploy.sh

# Dry run (preview only)
bash scripts/deploy.sh --dry-run

# CI mode (non-interactive)
bash scripts/deploy.sh --ci

# Skip specific checks
bash scripts/deploy.sh --skip-audit --skip-tests
```

**Documentation:** [docs/PRODUCTION_RUNBOOK.md](../docs/PRODUCTION_RUNBOOK.md)

---

### post-deploy.sh

Post-deployment operations (release management, shared directories).

```bash
# Setup shared directories
bash scripts/post-deploy.sh --setup-shared

# List releases
bash scripts/post-deploy.sh --list-releases

# Cleanup old releases (keep 5)
bash scripts/post-deploy.sh --cleanup
```

---

## Admin Management

### create-admin.php

Creates admin user accounts.

```bash
# Interactive mode
php scripts/create-admin.php

# With arguments
php scripts/create-admin.php admin@test.com "Admin Name" password123 super_admin active
```

**Roles:** super_admin, admin, editor  
**Status:** active, inactive, locked

---

### setup-admin-credentials.php

**DEPRECATED** - Use `create-admin.php` instead.

---

## Migrations

### migrate-orders-domain.php

Adds order status history, notes, and archiving support.

```bash
php scripts/migrate-orders-domain.php
```

---

### migrate-content-fields.php

Adds slug, featured, and media columns to content tables.

```bash
php scripts/migrate-content-fields.php
```

---

### migrate-orders-to-forms.php

Migrates legacy orders to new forms system.

```bash
# Dry run
php scripts/migrate-orders-to-forms.php --dry-run

# Limit migration
php scripts/migrate-orders-to-forms.php --limit=100
```

---

## Quick Reference

### Fresh Development Setup

```bash
# 1. Provision database
php scripts/provision-database.php --seed

# 2. Load comprehensive demo data
php scripts/seed-demo-data.php

# 3. Create your admin account
php scripts/create-admin.php admin@local.test "Dev Admin" devpass123
```

### Demo Environment Reset

```bash
# Reset to clean demo state
php scripts/seed-demo-data.php --force
```

### Pre-Deployment Validation

```bash
# 1. Validate hosting environment
php scripts/hosting-audit.php --strict

# 2. Run API smoke tests
php scripts/api_smoke.php --url=https://3dprint-omsk.ru --readonly

# 3. Deploy
bash scripts/deploy.sh
```

### Testing Workflow

```bash
# 1. Populate test data
php scripts/seed-demo-data.php --skip-admin-users

# 2. Run QA checklist
# See docs/QA_DB_SYNC_CHECKLIST.md (45-60 min)

# 3. Verify APIs
php scripts/api_smoke.php --url=http://localhost --readonly
```

---

## Environment Requirements

- PHP 7.4+ CLI
- Composer installed
- MySQL 5.7+ or MariaDB 10.2+
- `.env` file configured
- Write permissions on `storage/` directories

---

## Exit Codes

Most scripts follow this convention:
- `0` - Success
- `1` - General errors / Insert failures
- `2` - Invalid usage / Configuration errors
- `3+` - Script-specific error codes

Check script `--help` output for specific exit codes.

---

## Additional Documentation

- [Database Operations Guide](../docs/DATABASE_OPERATIONS.md)
- [QA Database Sync Checklist](../docs/QA_DB_SYNC_CHECKLIST.md)
- [Production Runbook](../docs/PRODUCTION_RUNBOOK.md)
- [Hosting Audit Guide](../docs/HOSTING_AUDIT.md)
- [API Reference](../docs/API_REFERENCE.md)
- [Testing Guide](../docs/TESTING.md)

---

**Last Updated:** January 19, 2025  
**Version:** 1.0
