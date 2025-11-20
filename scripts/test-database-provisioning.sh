#!/bin/bash
# Comprehensive test suite for database provisioning automation

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Database Provisioning Automation - Test Suite"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

ERRORS=0

# Test 1: Check provision script exists
echo "Test 1: Provision script exists..."
if [ ! -f "scripts/provision-database.php" ]; then
    echo "  ❌ FAIL: scripts/provision-database.php not found"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

# Test 2: Check script is executable
echo "Test 2: Script is executable..."
if [ ! -x "scripts/provision-database.php" ]; then
    echo "  ❌ FAIL: script is not executable"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

# Test 3: Check shebang
echo "Test 3: Correct shebang..."
SHEBANG=$(head -1 scripts/provision-database.php)
if [ "$SHEBANG" != "#!/usr/bin/env php" ]; then
    echo "  ❌ FAIL: Invalid shebang: $SHEBANG"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

# Test 4: Check DATABASE_OPERATIONS.md exists
echo "Test 4: DATABASE_OPERATIONS.md exists..."
if [ ! -f "docs/DATABASE_OPERATIONS.md" ]; then
    echo "  ❌ FAIL: docs/DATABASE_OPERATIONS.md not found"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

# Test 5: Check documentation size (should be comprehensive)
echo "Test 5: DATABASE_OPERATIONS.md is comprehensive..."
LINES=$(wc -l < docs/DATABASE_OPERATIONS.md)
if [ "$LINES" -lt 500 ]; then
    echo "  ❌ FAIL: Documentation too short ($LINES lines)"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS ($LINES lines)"
fi

# Test 6: Check for key documentation sections
echo "Test 6: Documentation contains required sections..."
SECTIONS=("Database Provisioning" "Backup Management" "Restore Operations" "Maintenance Tasks" "Troubleshooting")
for section in "${SECTIONS[@]}"; do
    if ! grep -q "$section" docs/DATABASE_OPERATIONS.md; then
        echo "  ❌ FAIL: Missing section: $section"
        ERRORS=$((ERRORS + 1))
    fi
done
if [ $ERRORS -eq 0 ] || grep -q "Database Provisioning" docs/DATABASE_OPERATIONS.md; then
    echo "  ✅ PASS"
fi

# Test 7: Check DEPLOYMENT.md references DATABASE_OPERATIONS.md
echo "Test 7: DEPLOYMENT.md references DATABASE_OPERATIONS.md..."
if ! grep -q "DATABASE_OPERATIONS.md" docs/DEPLOYMENT.md; then
    echo "  ❌ FAIL: DEPLOYMENT.md does not reference DATABASE_OPERATIONS.md"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

# Test 8: Check for required CLI flags
echo "Test 8: Provision script has required flags..."
FLAGS=("--admin-user" "--admin-password" "--create-only" "--import-only" "--seed" "--force" "--help")
MISSING_FLAGS=0
for flag in "${FLAGS[@]}"; do
    if ! grep -q "$flag" scripts/provision-database.php; then
        echo "  ⚠️  Missing flag: $flag"
        MISSING_FLAGS=$((MISSING_FLAGS + 1))
    fi
done
if [ $MISSING_FLAGS -eq 0 ]; then
    echo "  ✅ PASS"
else
    echo "  ❌ FAIL: Missing $MISSING_FLAGS flags"
    ERRORS=$((ERRORS + 1))
fi

# Test 9: Check for seeder script references
echo "Test 9: Provision script references seeder scripts..."
SEEDERS=("seed-forms.php" "seed-calculator-settings.php" "seed-global-settings.php")
MISSING_SEEDERS=0
for seeder in "${SEEDERS[@]}"; do
    if ! grep -q "$seeder" scripts/provision-database.php; then
        echo "  ⚠️  Missing seeder reference: $seeder"
        MISSING_SEEDERS=$((MISSING_SEEDERS + 1))
    fi
done
if [ $MISSING_SEEDERS -eq 0 ]; then
    echo "  ✅ PASS"
else
    echo "  ❌ FAIL: Missing $MISSING_SEEDERS seeder references"
    ERRORS=$((ERRORS + 1))
fi

# Test 10: Check for backup automation section
echo "Test 10: Provision script includes backup automation..."
if ! grep -q "Backup Automation" scripts/provision-database.php; then
    echo "  ❌ FAIL: Missing backup automation section"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

# Test 11: Check for cron examples
echo "Test 11: Provision script includes cron examples..."
if ! grep -q "0 2 \* \* \*" scripts/provision-database.php; then
    echo "  ❌ FAIL: Missing cron examples"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

# Test 12: Check for schema verification
echo "Test 12: Provision script calls verify-schema.php..."
if ! grep -q "verify-schema.php" scripts/provision-database.php; then
    echo "  ❌ FAIL: Missing schema verification"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

# Test 13: Check for UTF8MB4 collation
echo "Test 13: Provision script uses UTF8MB4 collation..."
if ! grep -q "utf8mb4" scripts/provision-database.php; then
    echo "  ❌ FAIL: Missing UTF8MB4 collation"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

# Test 14: Check for restricted user privileges
echo "Test 14: Provision script creates restricted user..."
if ! grep -q "GRANT SELECT, INSERT, UPDATE, DELETE" scripts/provision-database.php; then
    echo "  ❌ FAIL: Missing user privilege grants"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

# Test 15: Check for idempotent operations
echo "Test 15: Provision script is idempotent..."
if ! grep -q "IF NOT EXISTS" scripts/provision-database.php; then
    echo "  ❌ FAIL: Missing idempotent operations"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

# Test 16: Check documentation covers backup rotation
echo "Test 16: Documentation covers backup rotation..."
if ! grep -q "Backup Rotation Strategy" docs/DATABASE_OPERATIONS.md; then
    echo "  ❌ FAIL: Missing backup rotation strategy"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

# Test 17: Check documentation covers restore procedures
echo "Test 17: Documentation covers restore procedures..."
if ! grep -q "Restore Operations" docs/DATABASE_OPERATIONS.md; then
    echo "  ❌ FAIL: Missing restore procedures"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

# Test 18: Check documentation covers storage location
echo "Test 18: Documentation specifies storage/backups/ location..."
if ! grep -q "storage/backups" docs/DATABASE_OPERATIONS.md; then
    echo "  ❌ FAIL: Missing storage location"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

# Test 19: Check for exit codes
echo "Test 19: Provision script has proper exit codes..."
EXIT_CODES=("exit(0)" "exit(1)" "exit(2)" "exit(3)")
MISSING_CODES=0
for code in "${EXIT_CODES[@]}"; do
    if ! grep -q "$code" scripts/provision-database.php; then
        MISSING_CODES=$((MISSING_CODES + 1))
    fi
done
if [ $MISSING_CODES -eq 0 ]; then
    echo "  ✅ PASS"
else
    echo "  ❌ FAIL: Missing exit codes"
    ERRORS=$((ERRORS + 1))
fi

# Test 20: Check for environment variable support
echo "Test 20: Provision script supports .env configuration..."
if ! grep -q "DB_ADMIN_USER" scripts/provision-database.php && ! grep -q "\$_ENV" scripts/provision-database.php; then
    echo "  ❌ FAIL: Missing environment variable support"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

# Test 21: Check backup.php exists
echo "Test 21: Backup script exists..."
if [ ! -f "database/backup.php" ]; then
    echo "  ❌ FAIL: database/backup.php not found"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

# Test 22: Check verify-schema.php exists
echo "Test 22: Verification script exists..."
if [ ! -f "database/verify-schema.php" ]; then
    echo "  ❌ FAIL: database/verify-schema.php not found"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

# Test 23: Check seeder scripts exist
echo "Test 23: Seeder scripts exist..."
MISSING_SCRIPTS=0
for seeder in "${SEEDERS[@]}"; do
    if [ ! -f "scripts/$seeder" ]; then
        echo "  ⚠️  Missing script: $seeder"
        MISSING_SCRIPTS=$((MISSING_SCRIPTS + 1))
    fi
done
if [ $MISSING_SCRIPTS -eq 0 ]; then
    echo "  ✅ PASS"
else
    echo "  ⚠️  WARN: $MISSING_SCRIPTS seeder scripts missing"
fi

# Test 24: Check documentation table of contents
echo "Test 24: Documentation has table of contents..."
if ! grep -q "## Table of Contents" docs/DATABASE_OPERATIONS.md; then
    echo "  ⚠️  WARN: Missing table of contents"
else
    echo "  ✅ PASS"
fi

# Test 25: Check for troubleshooting section
echo "Test 25: Documentation has troubleshooting section..."
if ! grep -q "## Troubleshooting" docs/DATABASE_OPERATIONS.md; then
    echo "  ❌ FAIL: Missing troubleshooting section"
    ERRORS=$((ERRORS + 1))
else
    echo "  ✅ PASS"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test Results"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Total tests: 25"
echo "Failed: $ERRORS"
echo ""

if [ $ERRORS -eq 0 ]; then
    echo "✅ All tests passed!"
    echo ""
    exit 0
else
    echo "❌ $ERRORS test(s) failed"
    echo ""
    exit 1
fi
