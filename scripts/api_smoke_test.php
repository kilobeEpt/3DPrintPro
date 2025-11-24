<?php
// ========================================
// API Smoke Test Script
// Tests unified REST API endpoints
// ========================================

define('BASE_URL', 'http://localhost');
define('API_BASE', BASE_URL . '/api');

$results = [];
$passed = 0;
$failed = 0;

function testEndpoint($name, $url, $method = 'GET', $data = null) {
    global $results, $passed, $failed;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);
    
    $json = json_decode($body, true);
    
    // Check for required response structure
    $hasSuccessField = isset($json['success']);
    $hasProperStructure = $hasSuccessField && (
        ($json['success'] && isset($json['data'])) ||
        (!$json['success'] && isset($json['error']))
    );
    
    // Check for security headers
    $hasSecurityHeaders = 
        strpos($headers, 'X-Content-Type-Options') !== false &&
        strpos($headers, 'X-Frame-Options') !== false &&
        strpos($headers, 'Referrer-Policy') !== false;
    
    // Check for rate limit headers (on write operations)
    $hasRateLimitHeaders = true;
    if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
        $hasRateLimitHeaders = 
            strpos($headers, 'X-RateLimit-Limit') !== false &&
            strpos($headers, 'X-RateLimit-Remaining') !== false &&
            strpos($headers, 'X-RateLimit-Reset') !== false;
    }
    
    $success = $hasProperStructure && $hasSecurityHeaders && $hasRateLimitHeaders;
    
    if ($success) {
        $passed++;
    } else {
        $failed++;
    }
    
    $results[] = [
        'name' => $name,
        'method' => $method,
        'url' => $url,
        'http_code' => $httpCode,
        'has_structure' => $hasProperStructure,
        'has_security_headers' => $hasSecurityHeaders,
        'has_rate_limit_headers' => $hasRateLimitHeaders,
        'success' => $success
    ];
    
    return $success;
}

echo "🧪 API Smoke Test - Unified REST API\n";
echo "=====================================\n\n";

// Test GET endpoints (should not have rate limiting on reads)
echo "Testing GET endpoints...\n";
testEndpoint('Get Orders', API_BASE . '/orders.php?limit=5', 'GET');
testEndpoint('Get Services', API_BASE . '/services.php', 'GET');
testEndpoint('Get Portfolio', API_BASE . '/portfolio.php', 'GET');
testEndpoint('Get Testimonials', API_BASE . '/testimonials.php', 'GET');
testEndpoint('Get FAQ', API_BASE . '/faq.php', 'GET');
testEndpoint('Get Content', API_BASE . '/content.php', 'GET');
testEndpoint('Get Settings', API_BASE . '/settings.php', 'GET');

// Test that deprecated endpoints are gone
echo "\nTesting deprecated endpoints (should fail)...\n";
$deprecatedTests = [
    'submit-form.php' => API_BASE . '/submit-form.php',
    'get-orders.php' => API_BASE . '/get-orders.php'
];

foreach ($deprecatedTests as $name => $url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 404 || $httpCode === 0) {
        echo "✅ $name correctly returns 404 or not found\n";
        $passed++;
    } else {
        echo "❌ $name still accessible (should be removed)\n";
        $failed++;
    }
}

// Print results
echo "\n=====================================\n";
echo "📊 Test Results\n";
echo "=====================================\n\n";

foreach ($results as $result) {
    $icon = $result['success'] ? '✅' : '❌';
    echo "$icon {$result['name']} ({$result['method']} {$result['http_code']})\n";
    
    if (!$result['success']) {
        echo "   └─ Issues:\n";
        if (!$result['has_structure']) echo "      • Missing proper response structure\n";
        if (!$result['has_security_headers']) echo "      • Missing security headers\n";
        if (!$result['has_rate_limit_headers']) echo "      • Missing rate limit headers\n";
    }
}

echo "\n=====================================\n";
echo "Summary: $passed passed, $failed failed\n";
echo "=====================================\n";

exit($failed > 0 ? 1 : 0);
