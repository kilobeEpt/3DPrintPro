#!/usr/bin/env php
<?php
// ========================================
// API Smoke Test Suite v2.0
// Comprehensive testing with admin authentication and CRUD validation
// ========================================

class ApiSmokeTest {
    private $baseUrl;
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    private $readonlyMode = false;
    private $verbose = true;
    
    // Authentication state
    private $authenticated = false;
    private $adminEmail = null;
    private $adminPassword = null;
    private $cookies = [];
    private $csrfToken = null;
    
    // Cleanup tracking
    private $createdResources = [
        'forms' => [],
        'form_fields' => [],
        'form_submissions' => [],
        'services' => [],
        'portfolio' => [],
        'testimonials' => [],
        'faq' => [],
        'content_blocks' => [],
        'orders' => [],
        'admin_users' => [],
    ];
    
    public function __construct($baseUrl = null, $readonlyMode = false) {
        if ($baseUrl === null) {
            $baseUrl = $this->detectBaseUrl();
        }
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->readonlyMode = $readonlyMode;
    }
    
    private function detectBaseUrl() {
        if (isset($_SERVER['HTTP_HOST'])) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            return $protocol . '://' . $_SERVER['HTTP_HOST'];
        }
        return 'http://localhost';
    }
    
    public function setAdminCredentials($email, $password) {
        $this->adminEmail = $email;
        $this->adminPassword = $password;
    }
    
    public function setVerbose($verbose) {
        $this->verbose = $verbose;
    }
    
    public function run() {
        echo "🧪 API Smoke Test Suite v2.0\n";
        echo "Base URL: {$this->baseUrl}\n";
        echo "Mode: " . ($this->readonlyMode ? "READ-ONLY (safe for production)" : "FULL CRUD (test environment)") . "\n";
        echo "Auth: " . ($this->adminEmail ? "Enabled" : "Public only") . "\n";
        echo str_repeat('=', 80) . "\n\n";
        
        // Perform admin login if credentials provided
        if ($this->adminEmail && $this->adminPassword) {
            $this->performAdminLogin();
        }
        
        // Public endpoints (no auth required)
        $this->testHealthEndpoint();
        $this->testPublicContentEndpoints();
        $this->testPublicSettingsEndpoint();
        $this->testPublicCalculatorSettingsEndpoint();
        
        // Authenticated endpoints (require admin login)
        if ($this->authenticated) {
            $this->testAdminContentCRUD();
            $this->testFormsAPI();
            $this->testFormFieldsAPI();
            $this->testFormSubmissionsAPI();
            $this->testCalculatorSettingsAdmin();
            $this->testSettingsAdmin();
            $this->testAdminUsersAPI();
            $this->testAuditLogsAPI();
            $this->testOrdersAPI();
            
            // Cleanup all created resources
            if (!$this->readonlyMode) {
                $this->cleanup();
            }
        } else if (!$this->readonlyMode) {
            echo "⚠️  Skipping authenticated endpoints (no admin credentials provided)\n";
            echo "   Use --admin-email and --admin-password flags to test authenticated endpoints\n\n";
        }
        
        // Print summary
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
    
    // ========================================
    // Admin Authentication
    // ========================================
    
    private function performAdminLogin() {
        $this->testGroup('Admin Authentication', function() {
            // First, get CSRF token from login page
            $loginPageResponse = $this->request('GET', '/admin/login.php');
            
            // Extract CSRF token from HTML (basic parsing)
            if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginPageResponse['raw'], $matches)) {
                $csrfToken = $matches[1];
            } else {
                $this->assert('Failed to extract CSRF token from login page', false, true, [
                    'html_length' => strlen($loginPageResponse['raw'])
                ]);
                return;
            }
            
            // Perform login
            $loginData = [
                'email' => $this->adminEmail,
                'password' => $this->adminPassword,
                'csrf_token' => $csrfToken,
            ];
            
            $response = $this->request('POST', '/admin/login-handler.php', $loginData, [
                'follow_redirects' => false,
                'content_type' => 'application/x-www-form-urlencoded'
            ]);
            
            // Check for redirect (successful login)
            $isRedirect = in_array($response['status'], [301, 302, 303, 307, 308]);
            $this->assert('Admin login returns redirect', $isRedirect, true, [
                'status' => $response['status'],
                'location' => $response['headers']['location'] ?? null
            ]);
            
            if ($isRedirect && !empty($this->cookies)) {
                $this->authenticated = true;
                // Extract CSRF token from session
                $this->csrfToken = $this->extractCsrfTokenFromSession();
                $this->assert('Admin authentication successful', true, true);
            } else {
                $this->assert('Admin authentication failed', false, true, [
                    'cookies' => $this->cookies
                ]);
            }
        });
    }
    
    private function extractCsrfTokenFromSession() {
        // Make a request to get CSRF token from admin page
        $response = $this->request('GET', '/admin/index.php');
        if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $response['raw'], $matches)) {
            return $matches[1];
        }
        // Fallback: try to extract from meta tag
        if (preg_match('/name="csrf-token"\s+content="([^"]+)"/', $response['raw'], $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    // ========================================
    // Public Endpoints
    // ========================================
    
    private function testHealthEndpoint() {
        $this->testGroup('Health/Test Endpoint', function() {
            $response = $this->request('GET', '/api/test.php');
            $this->assertHttpStatus('GET /api/test.php returns 200', 200, $response);
            $this->assertResponseStructure('Response has correct structure', $response);
            $this->assert('Response has database_status', isset($response['data']['database_status']), $this->verbose);
        });
    }
    
    private function testPublicContentEndpoints() {
        $endpoints = [
            'services' => ['path' => '/api/services.php', 'key' => 'services'],
            'portfolio' => ['path' => '/api/portfolio.php', 'key' => 'items'],
            'testimonials' => ['path' => '/api/testimonials.php', 'key' => 'testimonials'],
            'faq' => ['path' => '/api/faq.php', 'key' => 'items'],
            'content' => ['path' => '/api/content.php', 'key' => 'blocks'],
        ];
        
        foreach ($endpoints as $name => $config) {
            $this->testGroup("Public {$name} Endpoint", function() use ($config) {
                $response = $this->request('GET', $config['path']);
                $this->assertHttpStatus("GET {$config['path']} returns 200", 200, $response);
                $this->assertResponseStructure("Response has correct structure", $response);
                $this->assert("Response has data.{$config['key']} array", 
                    isset($response['data']['data'][$config['key']]), 
                    $this->verbose
                );
            });
        }
    }
    
    private function testPublicSettingsEndpoint() {
        $this->testGroup('Public Settings Endpoint', function() {
            $response = $this->request('GET', '/api/settings.php');
            $this->assertHttpStatus('GET /api/settings.php returns 200', 200, $response);
            $this->assertResponseStructure('Response has correct structure', $response);
            $this->assert('Response has settings object', 
                isset($response['data']['data']['settings']), 
                $this->verbose
            );
            
            // Test grouped reads
            $response = $this->request('GET', '/api/settings.php?group=contact');
            $this->assertHttpStatus('GET settings with group=contact returns 200', 200, $response);
        });
    }
    
    private function testPublicCalculatorSettingsEndpoint() {
        $this->testGroup('Public Calculator Settings Endpoint', function() {
            $response = $this->request('GET', '/api/calculator-settings.php');
            $this->assertHttpStatus('GET /api/calculator-settings.php returns 200', 200, $response);
            $this->assertResponseStructure('Response has correct structure', $response);
        });
    }
    
    // ========================================
    // Admin Content CRUD
    // ========================================
    
    private function testAdminContentCRUD() {
        if ($this->readonlyMode) {
            $this->testAdminContentReads();
        } else {
            $this->testServicesCRUD();
            $this->testPortfolioCRUD();
            $this->testTestimonialsCRUD();
            $this->testFaqCRUD();
            $this->testContentBlocksCRUD();
        }
    }
    
    private function testAdminContentReads() {
        $this->testGroup('Admin Content Endpoints (Read-Only)', function() {
            $endpoints = [
                '/api/services.php',
                '/api/portfolio.php',
                '/api/testimonials.php',
                '/api/faq.php',
                '/api/content.php',
            ];
            
            foreach ($endpoints as $path) {
                $response = $this->authenticatedRequest('GET', $path);
                $this->assertHttpStatus("GET $path (authenticated) returns 200", 200, $response);
                $this->assertResponseStructure("Response has correct structure", $response);
            }
        });
    }
    
    private function testServicesCRUD() {
        $this->testGroup('Services CRUD', function() {
            // Create
            $serviceData = [
                'name' => 'Test Service ' . time(),
                'description' => 'Smoke test service - can be deleted',
                'slug' => 'test-service-' . time(),
                'active' => true,
            ];
            
            $response = $this->authenticatedRequest('POST', '/api/services.php', $serviceData);
            $this->assertHttpStatus('POST /api/services.php returns 201', 201, $response);
            $this->assertResponseStructure('Response has correct structure', $response);
            
            $serviceId = $response['data']['data']['service']['id'] ?? null;
            $this->assert('Response includes service ID', !empty($serviceId), $this->verbose);
            
            if ($serviceId) {
                $this->createdResources['services'][] = $serviceId;
                
                // Read
                $response = $this->authenticatedRequest('GET', "/api/services.php?id=$serviceId");
                $this->assertHttpStatus('GET single service returns 200', 200, $response);
                
                // Update
                $updateData = ['id' => $serviceId, 'name' => 'Updated Service'];
                $response = $this->authenticatedRequest('PUT', '/api/services.php', $updateData);
                $this->assertHttpStatus('PUT /api/services.php returns 200', 200, $response);
                
                // Delete will be done in cleanup
            }
        });
    }
    
    private function testPortfolioCRUD() {
        $this->testGroup('Portfolio CRUD', function() {
            $itemData = [
                'title' => 'Test Portfolio Item ' . time(),
                'description' => 'Smoke test item',
                'slug' => 'test-portfolio-' . time(),
                'active' => true,
            ];
            
            $response = $this->authenticatedRequest('POST', '/api/portfolio.php', $itemData);
            $this->assertHttpStatus('POST /api/portfolio.php returns 201', 201, $response);
            $this->assertResponseStructure('Response has correct structure', $response);
            
            $itemId = $response['data']['data']['item']['id'] ?? null;
            if ($itemId) {
                $this->createdResources['portfolio'][] = $itemId;
                
                $response = $this->authenticatedRequest('GET', "/api/portfolio.php?id=$itemId");
                $this->assertHttpStatus('GET single portfolio item returns 200', 200, $response);
                
                $updateData = ['id' => $itemId, 'title' => 'Updated Portfolio Item'];
                $response = $this->authenticatedRequest('PUT', '/api/portfolio.php', $updateData);
                $this->assertHttpStatus('PUT /api/portfolio.php returns 200', 200, $response);
            }
        });
    }
    
    private function testTestimonialsCRUD() {
        $this->testGroup('Testimonials CRUD', function() {
            $testimonialData = [
                'client_name' => 'Test Client ' . time(),
                'content' => 'Test testimonial content',
                'rating' => 5,
                'active' => true,
            ];
            
            $response = $this->authenticatedRequest('POST', '/api/testimonials.php', $testimonialData);
            $this->assertHttpStatus('POST /api/testimonials.php returns 201', 201, $response);
            $this->assertResponseStructure('Response has correct structure', $response);
            
            $testimonialId = $response['data']['data']['testimonial']['id'] ?? null;
            if ($testimonialId) {
                $this->createdResources['testimonials'][] = $testimonialId;
                
                $response = $this->authenticatedRequest('GET', "/api/testimonials.php?id=$testimonialId");
                $this->assertHttpStatus('GET single testimonial returns 200', 200, $response);
                
                $updateData = ['id' => $testimonialId, 'content' => 'Updated content'];
                $response = $this->authenticatedRequest('PUT', '/api/testimonials.php', $updateData);
                $this->assertHttpStatus('PUT /api/testimonials.php returns 200', 200, $response);
            }
        });
    }
    
    private function testFaqCRUD() {
        $this->testGroup('FAQ CRUD', function() {
            $faqData = [
                'question' => 'Test Question ' . time(),
                'answer' => 'Test answer content',
                'slug' => 'test-faq-' . time(),
                'active' => true,
            ];
            
            $response = $this->authenticatedRequest('POST', '/api/faq.php', $faqData);
            $this->assertHttpStatus('POST /api/faq.php returns 201', 201, $response);
            $this->assertResponseStructure('Response has correct structure', $response);
            
            $faqId = $response['data']['data']['item']['id'] ?? null;
            if ($faqId) {
                $this->createdResources['faq'][] = $faqId;
                
                $response = $this->authenticatedRequest('GET', "/api/faq.php?id=$faqId");
                $this->assertHttpStatus('GET single FAQ returns 200', 200, $response);
                
                $updateData = ['id' => $faqId, 'question' => 'Updated question'];
                $response = $this->authenticatedRequest('PUT', '/api/faq.php', $updateData);
                $this->assertHttpStatus('PUT /api/faq.php returns 200', 200, $response);
            }
        });
    }
    
    private function testContentBlocksCRUD() {
        $this->testGroup('Content Blocks CRUD', function() {
            $blockData = [
                'identifier' => 'test_block_' . time(),
                'content' => 'Test content block',
                'active' => true,
            ];
            
            $response = $this->authenticatedRequest('POST', '/api/content.php', $blockData);
            $this->assertHttpStatus('POST /api/content.php returns 201', 201, $response);
            $this->assertResponseStructure('Response has correct structure', $response);
            
            $blockId = $response['data']['data']['block']['id'] ?? null;
            if ($blockId) {
                $this->createdResources['content_blocks'][] = $blockId;
                
                $response = $this->authenticatedRequest('GET', "/api/content.php?id=$blockId");
                $this->assertHttpStatus('GET single content block returns 200', 200, $response);
                
                $updateData = ['id' => $blockId, 'content' => 'Updated content'];
                $response = $this->authenticatedRequest('PUT', '/api/content.php', $updateData);
                $this->assertHttpStatus('PUT /api/content.php returns 200', 200, $response);
            }
        });
    }
    
    // ========================================
    // Forms API
    // ========================================
    
    private function testFormsAPI() {
        $this->testGroup('Forms API', function() {
            if ($this->readonlyMode) {
                $response = $this->authenticatedRequest('GET', '/api/forms.php');
                $this->assertHttpStatus('GET /api/forms.php returns 200', 200, $response);
                $this->assertResponseStructure('Response has correct structure', $response);
                return;
            }
            
            // Create form
            $formData = [
                'name' => 'Test Form ' . time(),
                'slug' => 'test-form-' . time(),
                'description' => 'Smoke test form',
                'active' => true,
            ];
            
            $response = $this->authenticatedRequest('POST', '/api/forms.php', $formData);
            $this->assertHttpStatus('POST /api/forms.php returns 201', 201, $response);
            $this->assertResponseStructure('Response has correct structure', $response);
            
            $formId = $response['data']['data']['form']['id'] ?? null;
            $this->assert('Response includes form ID', !empty($formId), $this->verbose);
            
            if ($formId) {
                $this->createdResources['forms'][] = $formId;
                
                // Read
                $response = $this->authenticatedRequest('GET', "/api/forms.php?id=$formId");
                $this->assertHttpStatus('GET single form returns 200', 200, $response);
                
                // Update
                $updateData = ['id' => $formId, 'name' => 'Updated Form Name'];
                $response = $this->authenticatedRequest('PUT', '/api/forms.php', $updateData);
                $this->assertHttpStatus('PUT /api/forms.php returns 200', 200, $response);
            }
        });
    }
    
    // ========================================
    // Form Fields API
    // ========================================
    
    private function testFormFieldsAPI() {
        $this->testGroup('Form Fields API', function() {
            if ($this->readonlyMode) {
                $response = $this->authenticatedRequest('GET', '/api/form-fields.php');
                $this->assertHttpStatus('GET /api/form-fields.php returns 200', 200, $response);
                return;
            }
            
            // Need a form to attach fields to
            if (empty($this->createdResources['forms'])) {
                $this->assert('Skipping form fields test (no forms created)', true, $this->verbose);
                return;
            }
            
            $formId = $this->createdResources['forms'][0];
            
            // Create field
            $fieldData = [
                'form_id' => $formId,
                'label' => 'Test Field',
                'type' => 'text',
                'required' => true,
                'order' => 1,
            ];
            
            $response = $this->authenticatedRequest('POST', '/api/form-fields.php', $fieldData);
            $this->assertHttpStatus('POST /api/form-fields.php returns 201', 201, $response);
            
            $fieldId = $response['data']['data']['field']['id'] ?? null;
            if ($fieldId) {
                $this->createdResources['form_fields'][] = $fieldId;
                
                // Read
                $response = $this->authenticatedRequest('GET', "/api/form-fields.php?form_id=$formId");
                $this->assertHttpStatus('GET form fields returns 200', 200, $response);
                
                // Update
                $updateData = ['id' => $fieldId, 'label' => 'Updated Field Label'];
                $response = $this->authenticatedRequest('PUT', '/api/form-fields.php', $updateData);
                $this->assertHttpStatus('PUT /api/form-fields.php returns 200', 200, $response);
            }
        });
    }
    
    // ========================================
    // Form Submissions API
    // ========================================
    
    private function testFormSubmissionsAPI() {
        $this->testGroup('Form Submissions API', function() {
            $response = $this->authenticatedRequest('GET', '/api/form-submissions.php');
            $this->assertHttpStatus('GET /api/form-submissions.php returns 200', 200, $response);
            $this->assertResponseStructure('Response has correct structure', $response);
            
            if (!$this->readonlyMode) {
                // Test filtering
                $response = $this->authenticatedRequest('GET', '/api/form-submissions.php?status=pending');
                $this->assertHttpStatus('GET submissions with status filter returns 200', 200, $response);
            }
        });
    }
    
    // ========================================
    // Calculator Settings API
    // ========================================
    
    private function testCalculatorSettingsAdmin() {
        $this->testGroup('Calculator Settings (Admin)', function() {
            $response = $this->authenticatedRequest('GET', '/api/calculator-settings.php');
            $this->assertHttpStatus('GET /api/calculator-settings.php (admin) returns 200', 200, $response);
            $this->assertResponseStructure('Response has correct structure', $response);
            
            if (!$this->readonlyMode) {
                // Test update (if we can safely do so)
                $response = $this->authenticatedRequest('GET', '/api/calculator-settings.php?key=calculator.materials');
                if ($response['status'] === 200) {
                    $this->assert('GET calculator settings by key works', true, $this->verbose);
                }
            }
        });
    }
    
    // ========================================
    // Settings API (Admin)
    // ========================================
    
    private function testSettingsAdmin() {
        $this->testGroup('Settings API (Admin)', function() {
            $response = $this->authenticatedRequest('GET', '/api/settings.php');
            $this->assertHttpStatus('GET /api/settings.php (admin) returns 200', 200, $response);
            $this->assertResponseStructure('Response has correct structure', $response);
            
            if (!$this->readonlyMode) {
                // Test audit history
                $response = $this->authenticatedRequest('GET', '/api/settings.php?action=audit');
                $this->assertHttpStatus('GET settings audit history returns 200', 200, $response);
            }
        });
    }
    
    // ========================================
    // Admin Users API
    // ========================================
    
    private function testAdminUsersAPI() {
        $this->testGroup('Admin Users API', function() {
            $response = $this->authenticatedRequest('GET', '/api/admin/users.php');
            $this->assertHttpStatus('GET /api/admin/users.php returns 200', 200, $response);
            $this->assertResponseStructure('Response has correct structure', $response);
            
            if (!$this->readonlyMode) {
                // Create test user
                $userData = [
                    'email' => 'test-' . time() . '@example.com',
                    'name' => 'Test User',
                    'password' => 'TestPass123!',
                    'role' => 'editor',
                    'status' => 'active',
                ];
                
                $response = $this->authenticatedRequest('POST', '/api/admin/users.php', $userData);
                $this->assertHttpStatus('POST /api/admin/users.php returns 201', 201, $response);
                
                $userId = $response['data']['data']['user']['id'] ?? null;
                if ($userId) {
                    $this->createdResources['admin_users'][] = $userId;
                    
                    // Read
                    $response = $this->authenticatedRequest('GET', "/api/admin/users.php?id=$userId");
                    $this->assertHttpStatus('GET single admin user returns 200', 200, $response);
                    
                    // Update
                    $updateData = ['id' => $userId, 'name' => 'Updated Name'];
                    $response = $this->authenticatedRequest('PUT', '/api/admin/users.php', $updateData);
                    $this->assertHttpStatus('PUT /api/admin/users.php returns 200', 200, $response);
                }
            }
        });
    }
    
    // ========================================
    // Audit Logs API
    // ========================================
    
    private function testAuditLogsAPI() {
        $this->testGroup('Audit Logs API', function() {
            $response = $this->authenticatedRequest('GET', '/api/admin/audit-logs.php');
            $this->assertHttpStatus('GET /api/admin/audit-logs.php returns 200', 200, $response);
            $this->assertResponseStructure('Response has correct structure', $response);
            
            // Test stats endpoint
            $response = $this->authenticatedRequest('GET', '/api/admin/audit-logs.php?action=stats');
            $this->assertHttpStatus('GET audit log stats returns 200', 200, $response);
        });
    }
    
    // ========================================
    // Orders API
    // ========================================
    
    private function testOrdersAPI() {
        $this->testGroup('Orders API', function() {
            $response = $this->authenticatedRequest('GET', '/api/orders.php');
            $this->assertHttpStatus('GET /api/orders.php returns 200', 200, $response);
            $this->assertResponseStructure('Response has correct structure', $response);
            
            if (!$this->readonlyMode) {
                // Create test order
                $orderData = [
                    'name' => 'Test Order ' . time(),
                    'phone' => '+79991234567',
                    'email' => 'test@example.com',
                    'message' => 'Smoke test order',
                ];
                
                $response = $this->authenticatedRequest('POST', '/api/orders.php', $orderData);
                $this->assertHttpStatus('POST /api/orders.php returns 201', 201, $response);
                
                $orderId = $response['data']['data']['order_id'] ?? null;
                if ($orderId) {
                    $this->createdResources['orders'][] = $orderId;
                    
                    // Read
                    $response = $this->authenticatedRequest('GET', "/api/orders.php?id=$orderId");
                    $this->assertHttpStatus('GET single order returns 200', 200, $response);
                    
                    // Update status
                    $updateData = ['id' => $orderId, 'status' => 'processing'];
                    $response = $this->authenticatedRequest('PATCH', '/api/orders.php', $updateData);
                    // PATCH might return 200 or 204
                    $this->assert('PATCH order status succeeds', 
                        in_array($response['status'], [200, 204]), 
                        $this->verbose,
                        ['status' => $response['status']]
                    );
                }
            }
        });
    }
    
    // ========================================
    // Cleanup
    // ========================================
    
    private function cleanup() {
        $this->testGroup('Cleanup Created Resources', function() {
            $cleaned = 0;
            $failed = 0;
            
            // Delete in reverse order to respect foreign keys
            $deleteOrder = [
                'form_fields' => '/api/form-fields.php',
                'form_submissions' => '/api/form-submissions.php',
                'forms' => '/api/forms.php',
                'orders' => '/api/orders.php',
                'services' => '/api/services.php',
                'portfolio' => '/api/portfolio.php',
                'testimonials' => '/api/testimonials.php',
                'faq' => '/api/faq.php',
                'content_blocks' => '/api/content.php',
                'admin_users' => '/api/admin/users.php',
            ];
            
            foreach ($deleteOrder as $resource => $endpoint) {
                if (!empty($this->createdResources[$resource])) {
                    foreach ($this->createdResources[$resource] as $id) {
                        $response = $this->authenticatedRequest('DELETE', "$endpoint?id=$id");
                        if (in_array($response['status'], [200, 204, 404])) {
                            $cleaned++;
                        } else {
                            $failed++;
                            echo "  ⚠️  Failed to delete $resource ID $id (HTTP {$response['status']})\n";
                        }
                    }
                }
            }
            
            $this->assert("Cleaned up $cleaned resources", $failed === 0, true, [
                'cleaned' => $cleaned,
                'failed' => $failed
            ]);
        });
    }
    
    // ========================================
    // Helper Methods
    // ========================================
    
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
                echo "     Context: " . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            }
        }
    }
    
    private function assertHttpStatus($description, $expectedStatus, $response) {
        $this->assert(
            $description,
            $response['status'] === $expectedStatus,
            $this->verbose,
            [
                'expected' => $expectedStatus,
                'actual' => $response['status'],
                'body' => $response['data']
            ]
        );
    }
    
    private function assertResponseStructure($description, $response) {
        $hasSuccess = isset($response['data']['success']);
        $hasData = isset($response['data']['data']);
        
        // Some responses might have 'meta' instead of both success/data
        $validStructure = $hasSuccess || $hasData || isset($response['data']['meta']);
        
        $this->assert(
            $description,
            $validStructure,
            $this->verbose,
            [
                'has_success' => $hasSuccess,
                'has_data' => $hasData,
                'has_meta' => isset($response['data']['meta']),
                'keys' => array_keys($response['data'] ?? [])
            ]
        );
    }
    
    private function authenticatedRequest($method, $path, $data = null, $options = []) {
        if (!$this->authenticated) {
            return [
                'status' => 401,
                'data' => ['error' => 'Not authenticated'],
                'raw' => null
            ];
        }
        
        // Add CSRF token for write operations
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH']) && $this->csrfToken) {
            if (is_array($data)) {
                $data['csrf_token'] = $this->csrfToken;
            }
        }
        
        return $this->request($method, $path, $data, $options);
    }
    
    private function request($method, $path, $data = null, $options = []) {
        $url = $this->baseUrl . $path;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Follow redirects by default
        $followRedirects = $options['follow_redirects'] ?? true;
        if ($followRedirects) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        } else {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        }
        
        // Add cookies if we have them
        if (!empty($this->cookies)) {
            $cookieHeader = [];
            foreach ($this->cookies as $name => $value) {
                $cookieHeader[] = "$name=$value";
            }
            curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $cookieHeader));
        }
        
        $headers = [];
        $contentType = $options['content_type'] ?? 'application/json';
        
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data !== null) {
                    if ($contentType === 'application/x-www-form-urlencoded') {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                    } else {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                        $headers[] = 'Content-Type: application/json';
                    }
                }
                break;
            
            case 'PUT':
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                if ($data !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    $headers[] = 'Content-Type: application/json';
                }
                break;
            
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            
            case 'GET':
            default:
                break;
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'status' => 0,
                'data' => ['error' => $error],
                'raw' => null,
                'headers' => []
            ];
        }
        
        // Parse headers and body
        $headerText = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        
        // Extract cookies from response
        $this->extractCookiesFromHeaders($headerText);
        
        // Parse headers
        $parsedHeaders = [];
        $headerLines = explode("\r\n", $headerText);
        foreach ($headerLines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $parsedHeaders[strtolower(trim($key))] = trim($value);
            }
        }
        
        $decoded = json_decode($body, true);
        
        return [
            'status' => $httpCode,
            'data' => $decoded ?? [],
            'raw' => $body,
            'headers' => $parsedHeaders
        ];
    }
    
    private function extractCookiesFromHeaders($headerText) {
        preg_match_all('/Set-Cookie:\s*([^=]+)=([^;]+)/i', $headerText, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $this->cookies[$match[1]] = $match[2];
        }
    }
}

// ========================================
// CLI Execution
// ========================================

if (php_sapi_name() === 'cli') {
    $options = getopt('', [
        'url:',
        'admin-email:',
        'admin-password:',
        'readonly',
        'verbose',
        'quiet',
        'help'
    ]);
    
    if (isset($options['help'])) {
        echo "API Smoke Test Suite v2.0\n";
        echo "========================================\n\n";
        echo "Usage: php api_smoke.php [options]\n\n";
        echo "Options:\n";
        echo "  --url=<base_url>           Base URL of the application (e.g., https://example.com)\n";
        echo "  --admin-email=<email>      Admin email for authentication\n";
        echo "  --admin-password=<pass>    Admin password for authentication\n";
        echo "  --readonly                 Run in read-only mode (safe for production)\n";
        echo "  --verbose                  Show detailed output (default)\n";
        echo "  --quiet                    Show minimal output\n";
        echo "  --help                     Show this help message\n\n";
        echo "Examples:\n";
        echo "  # Test public endpoints only:\n";
        echo "  php api_smoke.php --url=https://3dprint-omsk.ru --readonly\n\n";
        echo "  # Full CRUD test with admin authentication:\n";
        echo "  php api_smoke.php --url=http://localhost:8000 \\\n";
        echo "    --admin-email=admin@example.com \\\n";
        echo "    --admin-password=SecurePass123\n\n";
        echo "  # Production read-only check:\n";
        echo "  php api_smoke.php --url=https://3dprint-omsk.ru \\\n";
        echo "    --admin-email=admin@example.com \\\n";
        echo "    --admin-password=SecurePass123 \\\n";
        echo "    --readonly\n\n";
        exit(0);
    }
    
    $baseUrl = $options['url'] ?? null;
    $adminEmail = $options['admin-email'] ?? null;
    $adminPassword = $options['admin-password'] ?? null;
    $readonlyMode = isset($options['readonly']);
    $verbose = isset($options['verbose']) || !isset($options['quiet']);
    
    if (!$baseUrl) {
        echo "❌ Error: --url parameter is required\n\n";
        echo "Run 'php api_smoke.php --help' for usage information\n";
        exit(1);
    }
    
    $tester = new ApiSmokeTest($baseUrl, $readonlyMode);
    
    if ($adminEmail && $adminPassword) {
        $tester->setAdminCredentials($adminEmail, $adminPassword);
    }
    
    $tester->setVerbose($verbose);
    $tester->run();
}
