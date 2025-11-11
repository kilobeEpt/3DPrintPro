<?php
// ========================================
// API Smoke Test Script
// Tests GET/POST/PUT/DELETE flows for all API endpoints
// ========================================

class ApiSmokeTest {
    private $baseUrl;
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    
    public function __construct($baseUrl = null) {
        if ($baseUrl === null) {
            $baseUrl = $this->detectBaseUrl();
        }
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    private function detectBaseUrl() {
        if (isset($_SERVER['HTTP_HOST'])) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            return $protocol . '://' . $_SERVER['HTTP_HOST'];
        }
        return 'http://localhost';
    }
    
    public function run($verbose = true) {
        echo "🧪 API Smoke Test Suite\n";
        echo "Base URL: {$this->baseUrl}\n";
        echo str_repeat('=', 80) . "\n\n";
        
        $this->testHealthEndpoint($verbose);
        $this->testServicesEndpoint($verbose);
        $this->testPortfolioEndpoint($verbose);
        $this->testTestimonialsEndpoint($verbose);
        $this->testFaqEndpoint($verbose);
        $this->testContentEndpoint($verbose);
        $this->testSettingsEndpoint($verbose);
        $this->testOrdersEndpoint($verbose);
        
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "📊 Test Summary\n";
        echo str_repeat('=', 80) . "\n";
        echo "Total Tests:  {$this->totalTests}\n";
        echo "✅ Passed:    {$this->passedTests}\n";
        echo "❌ Failed:    {$this->failedTests}\n";
        
        $successRate = $this->totalTests > 0 ? round(($this->passedTests / $this->totalTests) * 100, 2) : 0;
        echo "Success Rate: {$successRate}%\n";
        
        if ($this->failedTests > 0) {
            echo "\n❌ SMOKE TEST FAILED\n";
            exit(1);
        } else {
            echo "\n✅ ALL SMOKE TESTS PASSED\n";
            exit(0);
        }
    }
    
    private function testHealthEndpoint($verbose) {
        $this->testGroup('Health/Test Endpoint', function() use ($verbose) {
            $response = $this->request('GET', '/api/test.php');
            $this->assert('GET /api/test.php returns 200', $response['status'] === 200, $verbose, [
                'status' => $response['status'],
                'success' => $response['data']['success'] ?? false
            ]);
            
            $this->assert('Response has success field', isset($response['data']['success']), $verbose);
            $this->assert('Response success is true', $response['data']['success'] === true, $verbose);
            $this->assert('Response has database_status', isset($response['data']['database_status']), $verbose);
        });
    }
    
    private function testServicesEndpoint($verbose) {
        $this->testGroup('Services Endpoint', function() use ($verbose) {
            $response = $this->request('GET', '/api/services.php');
            $this->assert('GET /api/services.php returns 200', $response['status'] === 200, $verbose, [
                'status' => $response['status']
            ]);
            
            $this->assert('Response has success field', isset($response['data']['success']), $verbose);
            $this->assert('Response has data.services array', isset($response['data']['data']['services']), $verbose);
            
            $response = $this->request('GET', '/api/services.php?active=true');
            $this->assert('GET with filter active=true returns 200', $response['status'] === 200, $verbose);
        });
    }
    
    private function testPortfolioEndpoint($verbose) {
        $this->testGroup('Portfolio Endpoint', function() use ($verbose) {
            $response = $this->request('GET', '/api/portfolio.php');
            $this->assert('GET /api/portfolio.php returns 200', $response['status'] === 200, $verbose);
            $this->assert('Response has success field', isset($response['data']['success']), $verbose);
            $this->assert('Response has data.items array', isset($response['data']['data']['items']), $verbose);
        });
    }
    
    private function testTestimonialsEndpoint($verbose) {
        $this->testGroup('Testimonials Endpoint', function() use ($verbose) {
            $response = $this->request('GET', '/api/testimonials.php');
            $this->assert('GET /api/testimonials.php returns 200', $response['status'] === 200, $verbose);
            $this->assert('Response has success field', isset($response['data']['success']), $verbose);
            $this->assert('Response has data.testimonials array', isset($response['data']['data']['testimonials']), $verbose);
        });
    }
    
    private function testFaqEndpoint($verbose) {
        $this->testGroup('FAQ Endpoint', function() use ($verbose) {
            $response = $this->request('GET', '/api/faq.php');
            $this->assert('GET /api/faq.php returns 200', $response['status'] === 200, $verbose);
            $this->assert('Response has success field', isset($response['data']['success']), $verbose);
            $this->assert('Response has data.items array', isset($response['data']['data']['items']), $verbose);
        });
    }
    
    private function testContentEndpoint($verbose) {
        $this->testGroup('Content Endpoint', function() use ($verbose) {
            $response = $this->request('GET', '/api/content.php');
            $this->assert('GET /api/content.php returns 200', $response['status'] === 200, $verbose);
            $this->assert('Response has success field', isset($response['data']['success']), $verbose);
            $this->assert('Response has data.blocks array', isset($response['data']['data']['blocks']), $verbose);
        });
    }
    
    private function testSettingsEndpoint($verbose) {
        $this->testGroup('Settings Endpoint', function() use ($verbose) {
            $response = $this->request('GET', '/api/settings.php');
            $this->assert('GET /api/settings.php returns 200', $response['status'] === 200, $verbose);
            $this->assert('Response has success field', isset($response['data']['success']), $verbose);
            $this->assert('Response has data.settings object', isset($response['data']['data']['settings']), $verbose);
        });
    }
    
    private function testOrdersEndpoint($verbose) {
        $this->testGroup('Orders Endpoint (CRUD)', function() use ($verbose) {
            $response = $this->request('GET', '/api/orders.php?limit=5');
            $this->assert('GET /api/orders.php returns 200', $response['status'] === 200, $verbose);
            $this->assert('Response has success field', isset($response['data']['success']), $verbose);
            $this->assert('Response has data.orders array', isset($response['data']['data']['orders']), $verbose);
            
            $testOrder = [
                'name' => 'Test Order ' . date('His'),
                'phone' => '+79991234567',
                'email' => 'test@example.com',
                'message' => 'Smoke test order - can be deleted'
            ];
            
            $response = $this->request('POST', '/api/orders.php', $testOrder);
            $this->assert('POST /api/orders.php returns 201', $response['status'] === 201, $verbose, [
                'status' => $response['status'],
                'success' => $response['data']['success'] ?? false
            ]);
            
            if ($response['status'] === 201 && isset($response['data']['data']['order_id'])) {
                $orderId = $response['data']['data']['order_id'];
                $this->assert('POST response includes order_id', !empty($orderId), $verbose);
                
                $response = $this->request('GET', "/api/orders.php?id=$orderId");
                $this->assert('GET single order returns 200', $response['status'] === 200, $verbose);
                
                $updateData = [
                    'id' => $orderId,
                    'status' => 'processed'
                ];
                $response = $this->request('PUT', '/api/orders.php', $updateData);
                $this->assert('PUT /api/orders.php returns 200', $response['status'] === 200, $verbose);
                
                $response = $this->request('DELETE', "/api/orders.php?id=$orderId");
                $this->assert('DELETE /api/orders.php returns 200', $response['status'] === 200, $verbose);
                
                $response = $this->request('GET', "/api/orders.php?id=$orderId");
                $this->assert('GET deleted order returns 404', $response['status'] === 404, $verbose);
            } else {
                $this->assert('POST failed - skipping CRUD tests', false, $verbose, [
                    'response' => $response
                ]);
            }
        });
    }
    
    private function testGroup($name, $callback) {
        echo "\n📦 Testing: $name\n";
        echo str_repeat('-', 80) . "\n";
        $callback();
    }
    
    private function assert($description, $condition, $verbose = true, $context = []) {
        $this->totalTests++;
        
        if ($condition) {
            $this->passedTests++;
            if ($verbose) {
                echo "  ✅ $description\n";
            }
        } else {
            $this->failedTests++;
            echo "  ❌ $description\n";
            if (!empty($context)) {
                echo "     Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
            }
        }
    }
    
    private function request($method, $path, $data = null) {
        $url = $this->baseUrl . $path;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                }
                break;
            
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                }
                break;
            
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            
            case 'GET':
            default:
                break;
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'status' => 0,
                'data' => ['error' => $error],
                'raw' => null
            ];
        }
        
        $decoded = json_decode($response, true);
        
        return [
            'status' => $httpCode,
            'data' => $decoded ?? [],
            'raw' => $response
        ];
    }
}

// ========================================
// CLI Execution
// ========================================

if (php_sapi_name() === 'cli') {
    $options = getopt('', ['url:', 'verbose', 'quiet']);
    
    $baseUrl = $options['url'] ?? null;
    $verbose = isset($options['verbose']) || !isset($options['quiet']);
    
    if (!$baseUrl) {
        echo "Usage: php api_smoke.php --url=<base_url> [--verbose|--quiet]\n";
        echo "Example: php api_smoke.php --url=https://example.com\n";
        echo "Example: php api_smoke.php --url=http://localhost:8000\n\n";
        echo "If no URL provided, will attempt to detect from environment.\n\n";
    }
    
    $tester = new ApiSmokeTest($baseUrl);
    $tester->run($verbose);
}
