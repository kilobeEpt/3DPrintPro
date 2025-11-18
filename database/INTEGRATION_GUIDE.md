# Migration System Integration Guide

## Overview

This guide covers integrating the new v3.0 migration system with the existing application setup.

## Architecture

### Two Parallel Systems

The application now supports **two database setup approaches**:

1. **Legacy System (v2.0)** - `database/schema.sql` + `api/init-database.php`
2. **Migration System (v3.0)** - `scripts/migrate` + `scripts/seed`

Both systems are **independent and can coexist**.

## Migration Path Options

### Option 1: Fresh Installation (Recommended for New Projects)

Use the migration system for new installations:

```bash
# 1. Configure database
cp .env.example .env
# Edit .env with database credentials

# 2. Run migrations
php scripts/migrate up

# 3. Seed reference data
php scripts/seed

# 4. Verify
php scripts/migrate status
```

**Result:** Full v3.0 normalized schema with 19 tables.

### Option 2: Legacy Installation (v2.0 Schema)

Continue using the existing system:

```bash
# 1. Import schema
mysql -u USER -p DATABASE < database/schema.sql

# 2. Seed data via web
curl http://localhost/api/init-database.php

# Or run directly
php api/init-database.php
```

**Result:** Original 7-table schema (v2.0).

### Option 3: Migration from v2.0 to v3.0 (Future)

**Status:** Data migration scripts not yet implemented.

**Required Steps:**
1. Backup existing database
2. Export data from v2.0 tables
3. Run v3.0 migrations (`scripts/migrate fresh`)
4. Transform and import data
5. Update application code to use new schema

**Complexity:** High - requires custom data transformation scripts.

## Updating api/init-database.php (Optional)

If you want to update the existing init script to use migrations:

### Option A: Replace with Migration Calls

Edit `api/init-database.php`:

```php
<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../vendor/autoload.php';

$response = ['status' => 'OK', 'actions' => []];

try {
    // Run migrations
    $output = [];
    exec('php ' . __DIR__ . '/../scripts/migrate up 2>&1', $output, $exitCode);
    
    if ($exitCode === 0) {
        $response['actions'][] = '✅ Migrations completed';
        
        // Run seeders
        exec('php ' . __DIR__ . '/../scripts/seed 2>&1', $output, $exitCode);
        
        if ($exitCode === 0) {
            $response['actions'][] = '✅ Seeders completed';
            $response['summary'] = '✅ Database v3.0 initialized successfully';
        } else {
            $response['status'] = 'Error';
            $response['error'] = 'Seeder failed';
            $response['output'] = implode("\n", $output);
        }
    } else {
        $response['status'] = 'Error';
        $response['error'] = 'Migration failed';
        $response['output'] = implode("\n", $output);
    }
} catch (Exception $e) {
    $response['status'] = 'Error';
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
```

### Option B: Keep Both Systems

**Recommended for transition period:**

Keep `api/init-database.php` as-is for v2.0 compatibility and add a new endpoint:

Create `api/init-database-v3.php`:

```php
<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../vendor/autoload.php';

$response = ['status' => 'OK', 'version' => 'v3.0', 'actions' => []];

try {
    // Check if migrations already run
    require_once __DIR__ . '/../bootstrap/eloquent.php';
    
    $hasUsers = \Illuminate\Database\Capsule\Manager::schema()->hasTable('users');
    
    if ($hasUsers) {
        $response['actions'][] = '✓ Database already initialized (v3.0)';
        $response['mode'] = 'already_initialized';
    } else {
        // Run migrations
        $output = [];
        $migrateScript = __DIR__ . '/../scripts/migrate';
        exec("php $migrateScript up 2>&1", $output, $exitCode);
        
        if ($exitCode !== 0) {
            throw new Exception('Migration failed: ' . implode("\n", $output));
        }
        
        $response['actions'][] = '✅ Migrations completed (19 tables created)';
        
        // Run seeders
        $seedScript = __DIR__ . '/../scripts/seed';
        exec("php $seedScript 2>&1", $output, $exitCode);
        
        if ($exitCode !== 0) {
            throw new Exception('Seeder failed: ' . implode("\n", $output));
        }
        
        $response['actions'][] = '✅ Seeders completed (reference data populated)';
        $response['mode'] = 'fresh_install';
    }
    
    // Get table counts
    $stats = [
        'users' => \Illuminate\Database\Capsule\Manager::table('users')->count(),
        'customers' => \Illuminate\Database\Capsule\Manager::table('customers')->count(),
        'orders' => \Illuminate\Database\Capsule\Manager::table('orders')->count(),
        'services' => \Illuminate\Database\Capsule\Manager::table('services')->count(),
        'categories' => \Illuminate\Database\Capsule\Manager::table('categories')->count(),
        'materials' => \Illuminate\Database\Capsule\Manager::table('materials')->count(),
    ];
    
    $response['database_stats'] = $stats;
    $response['summary'] = '✅ Database v3.0 initialized successfully';
    $response['production_ready'] = true;
    
} catch (Exception $e) {
    http_response_code(500);
    $response['status'] = 'Error';
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
```

## CLI Wrapper Script (Optional)

Create a unified setup script at `scripts/setup`:

```bash
#!/bin/bash
# Database Setup Wrapper
# Provides interactive setup for v2.0 or v3.0 schema

set -e

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║         3D PrintPro Database Setup                           ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""
echo "Choose schema version:"
echo "  1) v2.0 (Legacy - 7 tables)"
echo "  2) v3.0 (New - 19 tables, normalized)"
echo ""
read -p "Enter choice [1-2]: " choice

case $choice in
    1)
        echo ""
        echo "Setting up v2.0 schema..."
        echo ""
        
        # Check for MySQL connection
        read -p "MySQL host [localhost]: " host
        host=${host:-localhost}
        
        read -p "MySQL database: " database
        read -p "MySQL username: " username
        read -sp "MySQL password: " password
        echo ""
        
        # Import schema
        mysql -h "$host" -u "$username" -p"$password" "$database" < database/schema.sql
        
        echo ""
        echo "✓ Schema imported"
        echo ""
        echo "Seeding data..."
        php api/init-database.php
        
        echo ""
        echo "✅ v2.0 database setup complete!"
        ;;
        
    2)
        echo ""
        echo "Setting up v3.0 schema..."
        echo ""
        
        # Check .env exists
        if [ ! -f .env ]; then
            echo "⚠ .env file not found. Creating from example..."
            cp .env.example .env
            echo "✓ Created .env - please edit with your database credentials"
            echo ""
            read -p "Press Enter after editing .env..."
        fi
        
        # Run migrations
        echo "Running migrations..."
        php scripts/migrate up
        
        echo ""
        echo "Seeding reference data..."
        php scripts/seed
        
        echo ""
        echo "✅ v3.0 database setup complete!"
        echo ""
        echo "Default admin credentials:"
        echo "  Username: admin"
        echo "  Password: admin123"
        echo ""
        echo "⚠ CHANGE PASSWORD IMMEDIATELY!"
        ;;
        
    *)
        echo "Invalid choice"
        exit 1
        ;;
esac

echo ""
echo "Database setup complete!"
```

Make it executable:
```bash
chmod +x scripts/setup
```

## Testing Both Systems

### Test v2.0 (Legacy)

```bash
# Create test database
mysql -u root -p -e "CREATE DATABASE test_v2;"

# Import schema
mysql -u root -p test_v2 < database/schema.sql

# Verify
mysql -u root -p test_v2 -e "SHOW TABLES;"
# Should show 7 tables: orders, settings, services, portfolio, testimonials, faq, content_blocks
```

### Test v3.0 (Migrations)

```bash
# Create test database
mysql -u root -p -e "CREATE DATABASE test_v3;"

# Configure .env for test database
# DB_DATABASE=test_v3

# Run migrations
php scripts/migrate fresh
php scripts/seed

# Verify
php scripts/migrate status
# Should show 19 migrations ran

# Check tables
mysql -u root -p test_v3 -e "SHOW TABLES;"
# Should show 20 tables (19 schema + 1 migrations tracking)
```

## Coexistence Notes

### Database Schema Compatibility

v2.0 and v3.0 schemas are **NOT compatible**:

| Feature | v2.0 | v3.0 |
|---------|------|------|
| Tables | 7 | 19 |
| Foreign Keys | 0 | 34 |
| Normalization | Partial | 3NF |
| Structure | Denormalized | Normalized |

**You cannot run both on the same database.**

### Application Code

To support both schemas, application code needs to:

1. **Detect schema version**
   ```php
   $hasUsers = $db->tableExists('users');
   $version = $hasUsers ? 'v3' : 'v2';
   ```

2. **Use appropriate models/queries**
   ```php
   if ($version === 'v3') {
       $customer = Customer::find($id);
   } else {
       // Legacy query
       $customer = extractCustomerFromOrder($orderId);
   }
   ```

3. **Provide migration tools**
   - Data export from v2.0
   - Data transformation scripts
   - Import to v3.0

## Deployment Strategy

### For New Projects

✅ Use migration system (v3.0):
- Better structure
- Full referential integrity
- Audit trail
- Easier to maintain

### For Existing Projects

**Phase 1:** Development
- Set up v3.0 on dev environment
- Test all functionality
- Update application code

**Phase 2:** Staging
- Migrate staging data
- End-to-end testing
- Performance testing

**Phase 3:** Production
- Schedule maintenance window
- Backup database
- Run migration
- Verify functionality
- Monitor performance

## Troubleshooting

### "Table already exists"

**Cause:** Running migrations on database with existing tables.

**Solution:**
```bash
# Either drop existing tables
php scripts/migrate fresh

# Or check what's there
php scripts/migrate status
```

### "Foreign key constraint fails"

**Cause:** Data violates FK constraints or wrong migration order.

**Solution:**
1. Check migration order (parents before children)
2. Verify existing data integrity
3. May need data cleanup before migration

### "Can't find driver"

**Cause:** PDO MySQL extension not installed.

**Solution:**
```bash
# Ubuntu/Debian
sudo apt-get install php-mysql
sudo service apache2 restart  # or php-fpm

# Verify
php -m | grep pdo_mysql
```

## Best Practices

### For Development

1. **Use version control** for migrations
2. **Test rollback** before committing
3. **Keep migrations small** and focused
4. **Document breaking changes** in commit messages
5. **Run seeders** after fresh migrations

### For Production

1. **Always backup** before migrations
2. **Test on staging** first
3. **Schedule maintenance window**
4. **Have rollback plan** ready
5. **Monitor after deployment**
6. **Keep backups** for 30+ days

## Summary

- **New projects:** Use migration system (v3.0)
- **Existing projects:** Can continue with v2.0 or plan migration
- **Both systems:** Independent and can coexist (different databases)
- **Integration:** Optional - update init-database.php or keep both
- **Documentation:** Complete guides in `database/` directory

## Quick Reference

```bash
# v2.0 (Legacy)
mysql -u USER -p DB < database/schema.sql
php api/init-database.php

# v3.0 (Migrations)
php scripts/migrate up
php scripts/seed

# Check status
php scripts/migrate status

# Rollback
php scripts/migrate down

# Fresh start
php scripts/migrate fresh
php scripts/seed
```

---

**See Also:**
- `database/MIGRATIONS.md` - Complete migration documentation
- `database/README_MIGRATIONS.md` - Quick start guide
- `docs/db-overhaul/schema-design.md` - Schema specification
