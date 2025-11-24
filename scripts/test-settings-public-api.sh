#!/bin/bash
# ========================================
# Test Settings API Public Access
# Verifies that public groups can be accessed without authentication
# ========================================

set -e

echo "========================================"
echo "Testing Settings API Public Access"
echo "========================================"
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
TOTAL_TESTS=0
PASSED_TESTS=0

# Base URL (can be overridden with environment variable)
BASE_URL="${API_BASE_URL:-http://localhost:8000}"

# Function to test endpoint
test_endpoint() {
    local description="$1"
    local url="$2"
    local expected_status="$3"
    local should_have_data="$4"
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    echo -n "Test ${TOTAL_TESTS}: ${description}... "
    
    # Make request without session cookie
    response=$(curl -s -w "\n%{http_code}" "${BASE_URL}${url}" 2>/dev/null || echo "000")
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | head -n-1)
    
    # Check HTTP status code
    if [ "$http_code" != "$expected_status" ]; then
        echo -e "${RED}FAIL${NC}"
        echo "  Expected HTTP $expected_status, got $http_code"
        echo "  Response: $body"
        return 1
    fi
    
    # Check if response has data (for successful requests)
    if [ "$should_have_data" = "yes" ]; then
        if echo "$body" | grep -q '"success":true'; then
            echo -e "${GREEN}PASS${NC}"
            PASSED_TESTS=$((PASSED_TESTS + 1))
            return 0
        else
            echo -e "${RED}FAIL${NC}"
            echo "  Expected success:true in response"
            echo "  Response: $body"
            return 1
        fi
    else
        echo -e "${GREEN}PASS${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
        return 0
    fi
}

echo "Testing Public Groups (should work WITHOUT auth):"
echo "----------------------------------------------------"
test_endpoint "GET contact group" "/api/settings.php?group=contact" "200" "yes" || true
test_endpoint "GET social group" "/api/settings.php?group=social" "200" "yes" || true
test_endpoint "GET seo group" "/api/settings.php?group=seo" "200" "yes" || true

echo ""
echo "Testing Private Groups (should require auth):"
echo "----------------------------------------------------"
test_endpoint "GET smtp group" "/api/settings.php?group=smtp" "401" "no" || true
test_endpoint "GET telegram group" "/api/settings.php?group=telegram" "401" "no" || true
test_endpoint "GET logging group" "/api/settings.php?group=logging" "401" "no" || true
test_endpoint "GET all settings" "/api/settings.php" "401" "no" || true

echo ""
echo "========================================"
echo "Summary: ${PASSED_TESTS}/${TOTAL_TESTS} tests passed"
echo "========================================"

if [ "$PASSED_TESTS" -eq "$TOTAL_TESTS" ]; then
    echo -e "${GREEN}✅ All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}❌ Some tests failed${NC}"
    exit 1
fi
