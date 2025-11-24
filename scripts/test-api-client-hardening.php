#!/usr/bin/env php
<?php
/**
 * API Client Hardening Test Script
 * 
 * This script verifies that the hardened API client changes are working correctly:
 * 1. CSRF token lookup (window.ADMIN_SESSION → meta tag → cache)
 * 2. Credentials: 'include' for all requests
 * 3. Proper header merging (Accept, Content-Type, X-CSRF-Token)
 * 4. FormData support
 * 5. DELETE method with optional data
 * 
 * Usage: php scripts/test-api-client-hardening.php
 */

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         API Client Hardening Verification Test Suite          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$testsPassed = 0;
$testsFailed = 0;

function testPass($message) {
    global $testsPassed;
    echo "✅ PASS: $message\n";
    $testsPassed++;
}

function testFail($message, $details = '') {
    global $testsFailed;
    echo "❌ FAIL: $message\n";
    if ($details) {
        echo "   Details: $details\n";
    }
    $testsFailed++;
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "Test 1: Check js/api-client.js exists\n";
echo "═══════════════════════════════════════════════════════════════\n";

$apiClientPath = __DIR__ . '/../js/api-client.js';
if (file_exists($apiClientPath)) {
    testPass("js/api-client.js exists");
} else {
    testFail("js/api-client.js not found", $apiClientPath);
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "Test 2: Check getCsrfToken() method exists\n";
echo "═══════════════════════════════════════════════════════════════\n";

$content = file_get_contents($apiClientPath);
if (strpos($content, 'getCsrfToken()') !== false) {
    testPass("getCsrfToken() method found");
    
    // Check if it has caching logic
    if (strpos($content, '_cachedCsrfToken') !== false) {
        testPass("CSRF token caching implemented");
    } else {
        testFail("CSRF token caching not found");
    }
    
    // Check if it checks window.ADMIN_SESSION first
    if (strpos($content, 'window.ADMIN_SESSION') !== false && 
        strpos($content, 'window.ADMIN_SESSION.csrfToken') !== false) {
        testPass("window.ADMIN_SESSION.csrfToken check found");
    } else {
        testFail("window.ADMIN_SESSION.csrfToken check not found");
    }
    
    // Check if it has meta tag fallback
    if (strpos($content, 'meta[name="csrf-token"]') !== false) {
        testPass("Meta tag fallback found");
    } else {
        testFail("Meta tag fallback not found");
    }
} else {
    testFail("getCsrfToken() method not found");
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "Test 3: Check credentials: 'include' in request method\n";
echo "═══════════════════════════════════════════════════════════════\n";

if (preg_match("/credentials:\s*['\"]include['\"]/", $content)) {
    testPass("credentials: 'include' found in request method");
} else {
    testFail("credentials: 'include' not found");
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "Test 4: Check Accept: application/json header\n";
echo "═══════════════════════════════════════════════════════════════\n";

if (preg_match("/['\"]Accept['\"]\s*:\s*['\"]application\/json['\"]/", $content)) {
    testPass("Accept: application/json header found");
} else {
    testFail("Accept: application/json header not found");
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "Test 5: Check proper header merging\n";
echo "═══════════════════════════════════════════════════════════════\n";

if (strpos($content, 'mergedHeaders') !== false || 
    strpos($content, '...defaultHeaders') !== false) {
    testPass("Header merging logic found");
} else {
    testFail("Header merging logic not found");
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "Test 6: Check FormData detection\n";
echo "═══════════════════════════════════════════════════════════════\n";

if (preg_match("/data\s+instanceof\s+FormData/", $content)) {
    testPass("FormData detection found");
} else {
    testFail("FormData detection not found");
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "Test 7: Check DELETE method accepts data parameter\n";
echo "═══════════════════════════════════════════════════════════════\n";

if (preg_match("/async\s+delete\s*\([^)]*data/", $content)) {
    testPass("DELETE method accepts data parameter");
} else {
    testFail("DELETE method doesn't accept data parameter");
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "Test 8: Check admin-api-client.js delegation\n";
echo "═══════════════════════════════════════════════════════════════\n";

$adminApiClientPath = __DIR__ . '/../admin/js/admin-api-client.js';
if (file_exists($adminApiClientPath)) {
    testPass("admin/js/admin-api-client.js exists");
    
    $adminContent = file_get_contents($adminApiClientPath);
    
    // Check if methods delegate properly (no manual request calls)
    $correctDelegation = [
        'createService' => 'this.client.createService',
        'updateService' => 'this.client.updateService',
        'deleteService' => 'this.client.deleteService',
        'createPortfolioItem' => 'this.client.createPortfolioItem',
        'updatePortfolioItem' => 'this.client.updatePortfolioItem',
        'deletePortfolioItem' => 'this.client.deletePortfolioItem',
    ];
    
    $delegationOk = true;
    foreach ($correctDelegation as $method => $delegation) {
        if (strpos($adminContent, $delegation) === false) {
            $delegationOk = false;
            testFail("$method doesn't properly delegate to APIClient");
            break;
        }
    }
    
    if ($delegationOk) {
        testPass("Admin API client methods properly delegate to APIClient");
    }
    
    // Check for incorrect manual request() calls
    if (preg_match("/this\.client\.request\s*\(\s*['\"]\/api\//", $adminContent)) {
        testFail("Found manual request() calls that should use helper methods");
    } else {
        testPass("No incorrect manual request() calls found");
    }
} else {
    testFail("admin/js/admin-api-client.js not found");
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "Test 9: Check checkConnectivity() uses credentials\n";
echo "═══════════════════════════════════════════════════════════════\n";

if (preg_match("/checkConnectivity.*?credentials:\s*['\"]include['\"]/s", $content)) {
    testPass("checkConnectivity() uses credentials: 'include'");
} else {
    testFail("checkConnectivity() doesn't use credentials: 'include'");
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "Test 10: Check documentation exists\n";
echo "═══════════════════════════════════════════════════════════════\n";

$docsPath = __DIR__ . '/../docs/API_CLIENT_HARDENING.md';
if (file_exists($docsPath)) {
    testPass("docs/API_CLIENT_HARDENING.md exists");
    
    $docsContent = file_get_contents($docsPath);
    $requiredSections = [
        'credentials: \'include\'',
        'getCsrfToken',
        'FormData',
        'Header Merging',
        'Testing'
    ];
    
    $allSectionsPresent = true;
    foreach ($requiredSections as $section) {
        if (stripos($docsContent, $section) === false) {
            $allSectionsPresent = false;
            testFail("Documentation missing section: $section");
            break;
        }
    }
    
    if ($allSectionsPresent) {
        testPass("All required documentation sections present");
    }
} else {
    testFail("docs/API_CLIENT_HARDENING.md not found");
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "Test 11: Check test page exists\n";
echo "═══════════════════════════════════════════════════════════════\n";

$testPagePath = __DIR__ . '/../test-api-client.html';
if (file_exists($testPagePath)) {
    testPass("test-api-client.html exists");
} else {
    testFail("test-api-client.html not found");
}

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                        Test Summary                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "Tests Passed: $testsPassed\n";
echo "Tests Failed: $testsFailed\n";
echo "Total Tests:  " . ($testsPassed + $testsFailed) . "\n\n";

if ($testsFailed === 0) {
    echo "🎉 All tests passed! API client hardening is complete.\n\n";
    echo "Next steps:\n";
    echo "1. Start PHP dev server: php -S localhost:8000\n";
    echo "2. Open test page: http://localhost:8000/test-api-client.html\n";
    echo "3. Login to admin: http://localhost:8000/admin/login.php\n";
    echo "4. Check Network tab for credentials & headers\n\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Please review the failures above.\n\n";
    exit(1);
}
