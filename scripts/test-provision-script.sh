#!/bin/bash
# Test script to verify provision-database.php structure

echo "Testing provision-database.php script..."
echo ""

# Check if file exists
if [ ! -f "scripts/provision-database.php" ]; then
    echo "❌ provision-database.php not found"
    exit 1
fi

echo "✅ File exists"

# Check if executable
if [ ! -x "scripts/provision-database.php" ]; then
    echo "❌ File is not executable"
    exit 1
fi

echo "✅ File is executable"

# Check shebang
SHEBANG=$(head -1 scripts/provision-database.php)
if [ "$SHEBANG" != "#!/usr/bin/env php" ]; then
    echo "❌ Invalid shebang: $SHEBANG"
    exit 1
fi

echo "✅ Shebang is correct"

# Check for key sections
grep -q "Database Provisioning Script" scripts/provision-database.php
if [ $? -ne 0 ]; then
    echo "❌ Missing banner"
    exit 1
fi

echo "✅ Banner present"

# Check for CLI argument parsing
grep -q "Parse CLI Arguments" scripts/provision-database.php
if [ $? -ne 0 ]; then
    echo "❌ Missing CLI argument parsing"
    exit 1
fi

echo "✅ CLI argument parsing present"

# Check for help option
grep -q "\-\-help" scripts/provision-database.php
if [ $? -ne 0 ]; then
    echo "❌ Missing --help option"
    exit 1
fi

echo "✅ Help option present"

# Check for required flags
grep -q "\-\-seed" scripts/provision-database.php && \
grep -q "\-\-create-only" scripts/provision-database.php && \
grep -q "\-\-import-only" scripts/provision-database.php && \
grep -q "\-\-force" scripts/provision-database.php
if [ $? -ne 0 ]; then
    echo "❌ Missing required flags"
    exit 1
fi

echo "✅ All required flags present"

# Check for backup automation section
grep -q "Backup Automation" scripts/provision-database.php
if [ $? -ne 0 ]; then
    echo "❌ Missing backup automation section"
    exit 1
fi

echo "✅ Backup automation section present"

# Check for cron examples
grep -q "0 2 \* \* \*" scripts/provision-database.php
if [ $? -ne 0 ]; then
    echo "❌ Missing cron examples"
    exit 1
fi

echo "✅ Cron examples present"

# Check for seeder references
grep -q "seed-forms.php" scripts/provision-database.php && \
grep -q "seed-calculator-settings.php" scripts/provision-database.php && \
grep -q "seed-global-settings.php" scripts/provision-database.php
if [ $? -ne 0 ]; then
    echo "❌ Missing seeder references"
    exit 1
fi

echo "✅ All seeder references present"

# Check for verification step
grep -q "verify-schema.php" scripts/provision-database.php
if [ $? -ne 0 ]; then
    echo "❌ Missing schema verification"
    exit 1
fi

echo "✅ Schema verification present"

# Check for exit codes
grep -q "exit(0)" scripts/provision-database.php && \
grep -q "exit(1)" scripts/provision-database.php && \
grep -q "exit(2)" scripts/provision-database.php && \
grep -q "exit(3)" scripts/provision-database.php
if [ $? -ne 0 ]; then
    echo "❌ Missing exit codes"
    exit 1
fi

echo "✅ Exit codes present"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ All structure checks passed!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

exit 0
