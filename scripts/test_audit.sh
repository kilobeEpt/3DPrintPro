#!/bin/bash
# ========================================
# Database Audit Tool - Test Script
# ========================================

echo "========================================="
echo "Testing Database Audit Tool"
echo "========================================="
echo ""

# Test 1: Check if audit script exists
echo "TEST 1: Checking if audit script exists..."
if [ -f "scripts/db_audit.php" ]; then
    echo "✅ PASS: scripts/db_audit.php exists"
else
    echo "❌ FAIL: scripts/db_audit.php not found"
    exit 1
fi
echo ""

# Test 2: Check script is readable
echo "TEST 2: Checking script permissions..."
if [ -r "scripts/db_audit.php" ]; then
    echo "✅ PASS: Script is readable"
else
    echo "❌ FAIL: Script is not readable"
    exit 1
fi
echo ""

# Test 3: Check file size (should be non-empty)
echo "TEST 3: Checking script content..."
filesize=$(wc -c < "scripts/db_audit.php")
if [ "$filesize" -gt 1000 ]; then
    echo "✅ PASS: Script has content ($filesize bytes)"
else
    echo "❌ FAIL: Script is too small or empty"
    exit 1
fi
echo ""

# Test 4: Check for DatabaseAuditor class
echo "TEST 4: Checking for DatabaseAuditor class..."
if grep -q "class DatabaseAuditor" scripts/db_audit.php; then
    echo "✅ PASS: DatabaseAuditor class found"
else
    echo "❌ FAIL: DatabaseAuditor class not found"
    exit 1
fi
echo ""

# Test 5: Check for expected methods
echo "TEST 5: Checking for expected methods..."
methods=(
    "loadConfig"
    "testConnection"
    "checkMySQLVersion"
    "checkPrivileges"
    "checkTables"
    "validateSchema"
    "outputHuman"
    "outputJSON"
)

for method in "${methods[@]}"; do
    if grep -q "function $method" scripts/db_audit.php; then
        echo "  ✅ Method $method found"
    else
        echo "  ❌ Method $method NOT found"
        exit 1
    fi
done
echo ""

# Test 6: Check test.php integration
echo "TEST 6: Checking test.php integration..."
if [ -f "api/test.php" ]; then
    if grep -q "db_audit.php" api/test.php; then
        echo "✅ PASS: test.php includes audit script"
    else
        echo "❌ FAIL: test.php does not include audit script"
        exit 1
    fi
else
    echo "❌ FAIL: api/test.php not found"
    exit 1
fi
echo ""

# Test 7: Check for audit parameter handling
echo "TEST 7: Checking audit parameter handling in test.php..."
if grep -q "audit.*full" api/test.php; then
    echo "✅ PASS: Audit parameter handling found"
else
    echo "❌ FAIL: Audit parameter handling not found"
    exit 1
fi
echo ""

# Test 8: Check documentation
echo "TEST 8: Checking documentation..."
docs_found=0

if grep -q "db_audit" README.md; then
    echo "  ✅ README.md mentions db_audit"
    docs_found=$((docs_found + 1))
fi

if grep -q "Database Audit" README.md; then
    echo "  ✅ README.md has Database Audit section"
    docs_found=$((docs_found + 1))
fi

if grep -q "audit" START_HERE.md; then
    echo "  ✅ START_HERE.md mentions audit"
    docs_found=$((docs_found + 1))
fi

if [ $docs_found -ge 2 ]; then
    echo "✅ PASS: Documentation updated ($docs_found files)"
else
    echo "❌ FAIL: Insufficient documentation"
    exit 1
fi
echo ""

# Test 9: Check PHP syntax (if PHP is available)
echo "TEST 9: Checking PHP syntax..."
if command -v php &> /dev/null; then
    if php -l scripts/db_audit.php > /dev/null 2>&1; then
        echo "✅ PASS: PHP syntax is valid"
    else
        echo "❌ FAIL: PHP syntax error"
        php -l scripts/db_audit.php
        exit 1
    fi
else
    echo "⚠️  SKIP: PHP not available for syntax check"
fi
echo ""

# Test 10: Check for security (no credential exposure)
echo "TEST 10: Checking for credential sanitization..."
if grep -q "sanitizedResults" api/test.php; then
    echo "✅ PASS: Credential sanitization found in test.php"
else
    echo "❌ FAIL: No credential sanitization in test.php"
    exit 1
fi
echo ""

echo "========================================="
echo "✅ ALL TESTS PASSED"
echo "========================================="
echo ""
echo "The database audit tool is ready to use:"
echo "  CLI:  php scripts/db_audit.php"
echo "  HTTP: /api/test.php?audit=full"
echo ""
exit 0
