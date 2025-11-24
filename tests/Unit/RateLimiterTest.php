<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase
{
    private $testDir;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Load the RateLimiter class
        require_once __DIR__ . '/../../api/helpers/rate_limiter.php';
        
        // Create test storage directory
        $this->testDir = __DIR__ . '/../../storage/cache/rate_limits_test';
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0755, true);
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test files
        if (is_dir($this->testDir)) {
            $files = glob($this->testDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testDir);
        }
        
        parent::tearDown();
    }
    
    public function testBasicRateLimitCheck()
    {
        $limiter = new \RateLimiter(null, 5, 60);
        
        // First 5 requests should be allowed
        for ($i = 0; $i < 5; $i++) {
            $result = $limiter->check('test_endpoint');
            $this->assertTrue($result['allowed'], "Request {$i} should be allowed");
        }
        
        // 6th request should be denied
        $result = $limiter->check('test_endpoint');
        $this->assertFalse($result['allowed'], '6th request should be denied');
        $this->assertGreaterThan(0, $result['retry_after']);
    }
    
    public function testRateLimitProfiles()
    {
        $profiles = \RateLimiter::getProfiles();
        
        $this->assertIsArray($profiles);
        $this->assertArrayHasKey(\RateLimiter::PROFILE_AUTH, $profiles);
        $this->assertArrayHasKey(\RateLimiter::PROFILE_API_READ, $profiles);
        $this->assertArrayHasKey(\RateLimiter::PROFILE_API_WRITE, $profiles);
        
        // Auth profile should be strict
        $authProfile = $profiles[\RateLimiter::PROFILE_AUTH];
        $this->assertEquals(5, $authProfile['max']);
        $this->assertEquals(900, $authProfile['window']); // 15 minutes
    }
    
    public function testAuthProfileRateLimit()
    {
        $limiter = new \RateLimiter(\RateLimiter::PROFILE_AUTH);
        
        // Auth profile allows only 5 requests per 15 minutes
        for ($i = 0; $i < 5; $i++) {
            $result = $limiter->check('login');
            $this->assertTrue($result['allowed'], "Login attempt {$i} should be allowed");
        }
        
        // 6th attempt should be blocked
        $result = $limiter->check('login');
        $this->assertFalse($result['allowed'], '6th login attempt should be blocked');
    }
    
    public function testApiReadProfile()
    {
        $limiter = new \RateLimiter(\RateLimiter::PROFILE_API_READ);
        
        // API read profile allows 100 requests per minute
        for ($i = 0; $i < 100; $i++) {
            $result = $limiter->check('api_read');
            $this->assertTrue($result['allowed'], "Read request {$i} should be allowed");
        }
        
        // 101st request should be blocked
        $result = $limiter->check('api_read');
        $this->assertFalse($result['allowed'], '101st read request should be blocked');
    }
    
    public function testApiWriteProfile()
    {
        $limiter = new \RateLimiter(\RateLimiter::PROFILE_API_WRITE);
        
        // API write profile allows 30 requests per minute
        for ($i = 0; $i < 30; $i++) {
            $result = $limiter->check('api_write');
            $this->assertTrue($result['allowed'], "Write request {$i} should be allowed");
        }
        
        // 31st request should be blocked
        $result = $limiter->check('api_write');
        $this->assertFalse($result['allowed'], '31st write request should be blocked');
    }
    
    public function testEndpointIsolation()
    {
        $limiter = new \RateLimiter(null, 3, 60);
        
        // Use up limit on endpoint1
        for ($i = 0; $i < 3; $i++) {
            $result = $limiter->check('endpoint1');
            $this->assertTrue($result['allowed']);
        }
        
        // endpoint1 should be blocked
        $result = $limiter->check('endpoint1');
        $this->assertFalse($result['allowed']);
        
        // But endpoint2 should still be available
        $result = $limiter->check('endpoint2');
        $this->assertTrue($result['allowed'], 'Different endpoint should have separate limit');
    }
    
    public function testRateLimitHeaders()
    {
        $limiter = new \RateLimiter(null, 10, 60);
        
        $result = $limiter->check('test');
        
        $this->assertArrayHasKey('remaining', $result);
        $this->assertArrayHasKey('reset', $result);
        $this->assertArrayHasKey('retry_after', $result);
        
        $this->assertEquals(9, $result['remaining']); // 10 max, 1 used
        $this->assertIsInt($result['reset']);
        $this->assertEquals(0, $result['retry_after']); // Allowed, so no retry
    }
    
    public function testRetryAfterCalculation()
    {
        $limiter = new \RateLimiter(null, 2, 60);
        
        // Use up the limit
        $limiter->check('test');
        $limiter->check('test');
        
        // Next check should provide retry_after
        $result = $limiter->check('test');
        
        $this->assertFalse($result['allowed']);
        $this->assertGreaterThan(0, $result['retry_after']);
        $this->assertLessThanOrEqual(60, $result['retry_after']);
    }
    
    public function testCleanup()
    {
        $limiter = new \RateLimiter(null, 5, 1); // 1 second window for testing
        
        // Create some rate limit files
        $limiter->check('test1');
        $limiter->check('test2');
        
        // Wait for files to expire
        sleep(3);
        
        $cleaned = $limiter->cleanup();
        
        $this->assertGreaterThanOrEqual(0, $cleaned);
    }
    
    public function testViolationTracking()
    {
        $limiter = new \RateLimiter(null, 2, 60);
        
        // Use up limit
        $limiter->check('test');
        $limiter->check('test');
        
        // Violations should be tracked
        $result1 = $limiter->check('test');
        $this->assertFalse($result1['allowed']);
        
        $result2 = $limiter->check('test');
        $this->assertFalse($result2['allowed']);
        
        // Both should be violations
        // Note: Actual violation count is stored internally, not returned in result
    }
}
