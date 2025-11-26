#!/bin/bash
# Validate Order Form Implementation
# This script checks that all requirements are met

echo "======================================"
echo "ORDER FORM VALIDATION"
echo "======================================"
echo ""

ERRORS=0
WARNINGS=0

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

check_pass() {
    echo -e "${GREEN}✅ PASS${NC}: $1"
}

check_fail() {
    echo -e "${RED}❌ FAIL${NC}: $1"
    ERRORS=$((ERRORS + 1))
}

check_warn() {
    echo -e "${YELLOW}⚠️  WARN${NC}: $1"
    WARNINGS=$((WARNINGS + 1))
}

echo "Checking files..."
echo "-----------------------------------"

# Check main files exist
if [ -f "js/form-handler.js" ]; then
    check_pass "js/form-handler.js exists"
else
    check_fail "js/form-handler.js missing"
fi

if [ -f "index.php" ] && grep -q 'id="order-form"' index.php; then
    check_pass "index.php contains order form"
else
    check_fail "index.php missing or no order form"
fi

if [ -f "order-submit.php" ]; then
    check_pass "order-submit.php exists"
else
    check_fail "order-submit.php missing"
fi

if [ -f "css/style.css" ] && grep -q "order-form-section" css/style.css; then
    check_pass "CSS contains order form styles"
else
    check_fail "CSS missing order form styles"
fi

echo ""
echo "Checking form fields..."
echo "-----------------------------------"

# Check all required fields
for field in fio email phone telegram service description files; do
    if grep -q "name=\"$field\"" index.php; then
        check_pass "Field '$field' present"
    else
        check_fail "Field '$field' missing"
    fi
done

# Check privacy checkbox
if grep -q 'name="privacy"' index.php; then
    check_pass "Privacy checkbox present"
else
    check_fail "Privacy checkbox missing"
fi

echo ""
echo "Checking services dropdown..."
echo "-----------------------------------"

# Check all services
for service in "FDM печать" "SLA печать" "SLS печать" "Цветная печать" "Постобработка"; do
    if grep -q "$service" index.php; then
        check_pass "Service '$service' present"
    else
        check_fail "Service '$service' missing"
    fi
done

echo ""
echo "Checking JavaScript implementation..."
echo "-----------------------------------"

# Check NO dependencies
if grep -qE "(CONFIG|ApiClient|OrderFormHandler)" js/form-handler.js; then
    check_fail "JavaScript has forbidden dependencies (CONFIG, ApiClient, etc.)"
else
    check_pass "No forbidden dependencies in JavaScript"
fi

# Check uses fetch()
if grep -q "fetch(" js/form-handler.js; then
    check_pass "Uses fetch() API"
else
    check_fail "Does not use fetch() API"
fi

# Check honeypot
if grep -q "honeypot\|website" js/form-handler.js; then
    check_pass "Honeypot implementation present"
else
    check_fail "Honeypot missing"
fi

# Check validation
if grep -q "validateForm" js/form-handler.js; then
    check_pass "Form validation present"
else
    check_fail "Form validation missing"
fi

# Check loading state
if grep -q "setLoading\|Loading\|spinner" js/form-handler.js; then
    check_pass "Loading state implementation present"
else
    check_fail "Loading state missing"
fi

# Check syntax
if node -c js/form-handler.js 2>/dev/null; then
    check_pass "JavaScript syntax valid"
else
    check_fail "JavaScript syntax errors"
fi

echo ""
echo "Checking integration..."
echo "-----------------------------------"

# Check form-handler.js is included in footer
if grep -q "form-handler.js" includes/footer.php; then
    check_pass "form-handler.js included in footer"
else
    check_fail "form-handler.js not included in footer"
fi

# Check form message container
if grep -q 'id="form-message"' index.php; then
    check_pass "Form message container present"
else
    check_fail "Form message container missing"
fi

# Check file info container
if grep -q 'id="file-info"' index.php; then
    check_pass "File info container present"
else
    check_warn "File info container missing (optional)"
fi

echo ""
echo "Checking CSS..."
echo "-----------------------------------"

# Check responsive design
if grep -q "@media.*max-width.*768px" css/style.css; then
    check_pass "Responsive design (768px breakpoint)"
else
    check_warn "No 768px breakpoint (responsive design)"
fi

if grep -q "@media.*max-width.*480px" css/style.css; then
    check_pass "Responsive design (480px breakpoint)"
else
    check_warn "No 480px breakpoint (responsive design)"
fi

# Check theme support
if grep -q "var(--" css/style.css; then
    check_pass "Uses CSS variables (theme support)"
else
    check_warn "No CSS variables (theme support)"
fi

echo ""
echo "======================================"
echo "SUMMARY"
echo "======================================"

if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✅ ALL CHECKS PASSED${NC}"
    echo "Warnings: $WARNINGS"
    echo ""
    echo "The order form implementation is complete and ready!"
    exit 0
else
    echo -e "${RED}❌ VALIDATION FAILED${NC}"
    echo "Errors: $ERRORS"
    echo "Warnings: $WARNINGS"
    echo ""
    echo "Please fix the errors above before deploying."
    exit 1
fi
