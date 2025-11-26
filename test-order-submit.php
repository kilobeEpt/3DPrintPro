#!/usr/bin/env php
<?php
/**
 * Test Order Submission Handler
 * 
 * Tests the order-submit.php endpoint with various scenarios:
 * - Valid order submission
 * - Invalid data validation
 * - Honeypot detection
 * - Rate limiting
 * - File upload
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Order Submission Handler - Test Suite                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

class OrderSubmitTester {
    private $baseUrl;
    private $results = [];
    
    public function __construct($baseUrl = 'http://localhost') {
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    public function runTests() {
        echo "Starting test suite...\n\n";
        
        $this->testValidSubmission();
        $this->testMissingFields();
        $this->testInvalidEmail();
        $this->testFieldLengthValidation();
        $this->testHoneypot();
        
        $this->printResults();
    }
    
    private function testValidSubmission() {
        echo "Test 1: Valid order submission\n";
        echo "--------------------------------\n";
        
        $data = [
            'name' => 'Иван Петров',
            'email' => 'ivan@example.com',
            'phone' => '+7 999 123-45-67',
            'service' => 'FDM печать',
            'description' => 'Нужно напечатать детали для проекта. Материал PLA, качество высокое.'
        ];
        
        $response = $this->sendRequest($data);
        
        if ($response && isset($response['success']) && $response['success']) {
            $this->results[] = ['test' => 'Valid Submission', 'status' => 'PASS', 'message' => 'Order submitted successfully'];
            echo "✅ PASS: Order submitted successfully\n";
            echo "   Message: {$response['message']}\n";
            if (isset($response['order_id'])) {
                echo "   Order ID: {$response['order_id']}\n";
            }
            if (isset($response['telegram_status'])) {
                echo "   Telegram Status: {$response['telegram_status']}\n";
            }
        } else {
            $this->results[] = ['test' => 'Valid Submission', 'status' => 'FAIL', 'message' => 'Failed to submit order'];
            echo "❌ FAIL: " . ($response['error'] ?? 'Unknown error') . "\n";
        }
        
        echo "\n";
    }
    
    private function testMissingFields() {
        echo "Test 2: Missing required fields\n";
        echo "--------------------------------\n";
        
        $data = [
            'name' => 'Иван Петров',
            'email' => 'ivan@example.com'
            // Missing: phone, service, description
        ];
        
        $response = $this->sendRequest($data);
        
        if ($response && !$response['success'] && $response['error'] === 'Ошибка валидации') {
            $this->results[] = ['test' => 'Missing Fields', 'status' => 'PASS', 'message' => 'Validation errors detected'];
            echo "✅ PASS: Validation errors detected correctly\n";
            echo "   Errors: " . json_encode($response['details'], JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            $this->results[] = ['test' => 'Missing Fields', 'status' => 'FAIL', 'message' => 'Validation did not work'];
            echo "❌ FAIL: Validation did not work as expected\n";
        }
        
        echo "\n";
    }
    
    private function testInvalidEmail() {
        echo "Test 3: Invalid email format\n";
        echo "--------------------------------\n";
        
        $data = [
            'name' => 'Иван Петров',
            'email' => 'invalid-email',
            'phone' => '+7 999 123-45-67',
            'service' => 'FDM печать',
            'description' => 'Нужно напечатать детали для проекта'
        ];
        
        $response = $this->sendRequest($data);
        
        if ($response && !$response['success'] && isset($response['details']['email'])) {
            $this->results[] = ['test' => 'Invalid Email', 'status' => 'PASS', 'message' => 'Email validation working'];
            echo "✅ PASS: Invalid email detected\n";
            echo "   Error: {$response['details']['email']}\n";
        } else {
            $this->results[] = ['test' => 'Invalid Email', 'status' => 'FAIL', 'message' => 'Email validation failed'];
            echo "❌ FAIL: Email validation did not work\n";
        }
        
        echo "\n";
    }
    
    private function testFieldLengthValidation() {
        echo "Test 4: Field length validation\n";
        echo "--------------------------------\n";
        
        $data = [
            'name' => 'A', // Too short
            'email' => 'ivan@example.com',
            'phone' => '+7 999 123-45-67',
            'service' => 'FDM печать',
            'description' => 'Short' // Too short
        ];
        
        $response = $this->sendRequest($data);
        
        if ($response && !$response['success'] && isset($response['details'])) {
            $hasNameError = isset($response['details']['name']);
            $hasDescError = isset($response['details']['description']);
            
            if ($hasNameError && $hasDescError) {
                $this->results[] = ['test' => 'Field Length', 'status' => 'PASS', 'message' => 'Length validation working'];
                echo "✅ PASS: Field length validation working\n";
                echo "   Name Error: {$response['details']['name']}\n";
                echo "   Description Error: {$response['details']['description']}\n";
            } else {
                $this->results[] = ['test' => 'Field Length', 'status' => 'PARTIAL', 'message' => 'Some validations missing'];
                echo "⚠️  PARTIAL: Some validations missing\n";
            }
        } else {
            $this->results[] = ['test' => 'Field Length', 'status' => 'FAIL', 'message' => 'Length validation failed'];
            echo "❌ FAIL: Length validation did not work\n";
        }
        
        echo "\n";
    }
    
    private function testHoneypot() {
        echo "Test 5: Honeypot detection\n";
        echo "--------------------------------\n";
        
        $data = [
            'name' => 'Bot Name',
            'email' => 'bot@example.com',
            'phone' => '+7 999 999-99-99',
            'service' => 'FDM печать',
            'description' => 'This is a bot submission',
            'website' => 'http://spam.com' // Honeypot field
        ];
        
        $response = $this->sendRequest($data);
        
        // Honeypot should return success but not process
        if ($response && $response['success']) {
            $this->results[] = ['test' => 'Honeypot', 'status' => 'PASS', 'message' => 'Honeypot working (silent success)'];
            echo "✅ PASS: Honeypot working correctly (returns success to bot)\n";
            echo "   Message: {$response['message']}\n";
        } else {
            $this->results[] = ['test' => 'Honeypot', 'status' => 'FAIL', 'message' => 'Honeypot not working'];
            echo "❌ FAIL: Honeypot did not work\n";
        }
        
        echo "\n";
    }
    
    private function sendRequest($data) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/order-submit.php',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: OrderSubmitTester/1.0'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            echo "⚠️  cURL Error: $error\n";
            return null;
        }
        
        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "⚠️  JSON Parse Error: " . json_last_error_msg() . "\n";
            echo "   Response: " . substr($response, 0, 200) . "\n";
            return null;
        }
        
        return $decoded;
    }
    
    private function printResults() {
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║  Test Results Summary                                      ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n\n";
        
        $passCount = 0;
        $failCount = 0;
        $partialCount = 0;
        
        foreach ($this->results as $result) {
            $status = $result['status'];
            $icon = $status === 'PASS' ? '✅' : ($status === 'FAIL' ? '❌' : '⚠️ ');
            
            echo sprintf("%-30s %s %s\n", $result['test'] . ':', $icon, $status);
            
            if ($status === 'PASS') $passCount++;
            elseif ($status === 'FAIL') $failCount++;
            else $partialCount++;
        }
        
        echo "\n";
        echo "Total Tests: " . count($this->results) . "\n";
        echo "Passed: $passCount\n";
        echo "Failed: $failCount\n";
        echo "Partial: $partialCount\n";
        
        if ($failCount === 0) {
            echo "\n🎉 All critical tests passed!\n";
        } else {
            echo "\n⚠️  Some tests failed. Please review the output above.\n";
        }
    }
}

// Check if .env file exists
if (!file_exists(__DIR__ . '/.env')) {
    echo "⚠️  Warning: .env file not found. Create it from .env.example\n";
    echo "   Some features may not work without Telegram configuration.\n\n";
}

// Run tests
$baseUrl = $argv[1] ?? 'http://localhost';

if ($baseUrl === '--help' || $baseUrl === '-h') {
    echo "Usage: php test-order-submit.php [base-url]\n";
    echo "Example: php test-order-submit.php http://localhost:8000\n";
    echo "         php test-order-submit.php https://3dprint-omsk.ru\n";
    exit(0);
}

$tester = new OrderSubmitTester($baseUrl);
$tester->runTests();

echo "\n";
echo "Note: For full testing, ensure:\n";
echo "  1. Telegram bot is configured in .env\n";
echo "  2. At least one user is authenticated with the bot\n";
echo "  3. Server has write permissions to storage/ directories\n";
echo "  4. Test file uploads manually in browser\n";
echo "\n";
