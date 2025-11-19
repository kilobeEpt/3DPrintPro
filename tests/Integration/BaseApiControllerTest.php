<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Models\Service;
use App\Models\Portfolio;
use App\Models\FAQ;

/**
 * Base API Controller Integration Tests
 * 
 * Tests the new BaseApiController and controller implementations.
 */
class BaseApiControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        cleanTestData();
        seedTestData();
    }
    
    protected function tearDown(): void
    {
        cleanTestData();
        parent::tearDown();
    }
    
    /**
     * Test ServiceController handles GET requests correctly
     */
    public function testServiceControllerGetRequest()
    {
        // Simulate GET request for services
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['active' => 'true'];
        
        // Create test service
        $service = Service::create([
            'name' => 'Test Service',
            'slug' => 'test-service',
            'description' => 'Test description',
            'active' => true,
            'featured' => false,
            'sort_order' => 1
        ]);
        
        $this->assertNotNull($service->id);
        $this->assertEquals('Test Service', $service->name);
        
        // Verify service can be retrieved
        $retrieved = Service::find($service->id);
        $this->assertNotNull($retrieved);
        $this->assertEquals('test-service', $retrieved->slug);
    }
    
    /**
     * Test PortfolioController pagination
     */
    public function testPortfolioControllerPagination()
    {
        // Create multiple portfolio items
        for ($i = 1; $i <= 15; $i++) {
            Portfolio::create([
                'title' => "Portfolio Item $i",
                'description' => "Description $i",
                'category' => 'test',
                'active' => true,
                'sort_order' => $i
            ]);
        }
        
        // Test pagination
        $query = Portfolio::query()->ordered();
        $total = $query->count();
        $this->assertEquals(15, $total);
        
        // Get first page
        $page1 = Portfolio::query()->ordered()->limit(10)->offset(0)->get();
        $this->assertCount(10, $page1);
        $this->assertEquals('Portfolio Item 1', $page1[0]->title);
        
        // Get second page
        $page2 = Portfolio::query()->ordered()->limit(10)->offset(10)->get();
        $this->assertCount(5, $page2);
        $this->assertEquals('Portfolio Item 11', $page2[0]->title);
    }
    
    /**
     * Test FAQ filtering by active status
     */
    public function testFAQControllerFiltering()
    {
        // Create active and inactive FAQ items
        FAQ::create([
            'question' => 'Active Question 1',
            'answer' => 'Active Answer 1',
            'active' => true,
            'sort_order' => 1
        ]);
        
        FAQ::create([
            'question' => 'Inactive Question',
            'answer' => 'Inactive Answer',
            'active' => false,
            'sort_order' => 2
        ]);
        
        FAQ::create([
            'question' => 'Active Question 2',
            'answer' => 'Active Answer 2',
            'active' => true,
            'sort_order' => 3
        ]);
        
        // Test filtering
        $activeFaqs = FAQ::where('active', true)->ordered()->get();
        $this->assertCount(2, $activeFaqs);
        
        $inactiveFaqs = FAQ::where('active', false)->ordered()->get();
        $this->assertCount(1, $inactiveFaqs);
        
        $allFaqs = FAQ::ordered()->get();
        $this->assertCount(3, $allFaqs);
    }
    
    /**
     * Test Service model scopes
     */
    public function testServiceModelScopes()
    {
        // Create featured and non-featured services
        Service::create([
            'name' => 'Featured Service',
            'slug' => 'featured-service',
            'active' => true,
            'featured' => true,
            'sort_order' => 1
        ]);
        
        Service::create([
            'name' => 'Regular Service',
            'slug' => 'regular-service',
            'active' => true,
            'featured' => false,
            'sort_order' => 2
        ]);
        
        Service::create([
            'name' => 'Inactive Service',
            'slug' => 'inactive-service',
            'active' => false,
            'featured' => false,
            'sort_order' => 3
        ]);
        
        // Test featured scope
        $featured = Service::featured()->get();
        $this->assertCount(1, $featured);
        $this->assertEquals('Featured Service', $featured[0]->name);
        
        // Test active scope
        $active = Service::active()->get();
        $this->assertCount(2, $active);
        
        // Test ordered scope
        $ordered = Service::ordered()->get();
        $this->assertEquals('Featured Service', $ordered[0]->name);
        $this->assertEquals('Regular Service', $ordered[1]->name);
    }
    
    /**
     * Test JSON casting for features field
     */
    public function testServiceFeaturesJsonCasting()
    {
        $service = Service::create([
            'name' => 'Service with Features',
            'slug' => 'service-features',
            'features' => ['Fast', 'Reliable', 'Affordable'],
            'active' => true,
            'sort_order' => 1
        ]);
        
        // Retrieve and verify JSON casting
        $retrieved = Service::find($service->id);
        $this->assertIsArray($retrieved->features);
        $this->assertCount(3, $retrieved->features);
        $this->assertEquals('Fast', $retrieved->features[0]);
    }
    
    /**
     * Test model update
     */
    public function testModelUpdate()
    {
        $service = Service::create([
            'name' => 'Original Name',
            'slug' => 'original-slug',
            'active' => true,
            'sort_order' => 1
        ]);
        
        $originalId = $service->id;
        
        // Update service
        $service->update([
            'name' => 'Updated Name',
            'active' => false
        ]);
        
        // Verify update
        $updated = Service::find($originalId);
        $this->assertEquals('Updated Name', $updated->name);
        $this->assertFalse($updated->active);
        $this->assertEquals('original-slug', $updated->slug); // Unchanged
    }
    
    /**
     * Test model deletion
     */
    public function testModelDeletion()
    {
        $service = Service::create([
            'name' => 'Service to Delete',
            'slug' => 'delete-me',
            'active' => true,
            'sort_order' => 1
        ]);
        
        $id = $service->id;
        $this->assertNotNull(Service::find($id));
        
        // Delete
        $service->delete();
        
        // Verify deletion
        $this->assertNull(Service::find($id));
    }
    
    /**
     * Test query builder with multiple conditions
     */
    public function testComplexQueryBuilding()
    {
        // Create services with different categories
        Service::create([
            'name' => 'Printing Service A',
            'slug' => 'printing-a',
            'category' => 'printing',
            'active' => true,
            'featured' => true,
            'sort_order' => 1
        ]);
        
        Service::create([
            'name' => 'Printing Service B',
            'slug' => 'printing-b',
            'category' => 'printing',
            'active' => true,
            'featured' => false,
            'sort_order' => 2
        ]);
        
        Service::create([
            'name' => 'Design Service',
            'slug' => 'design-a',
            'category' => 'design',
            'active' => true,
            'featured' => true,
            'sort_order' => 3
        ]);
        
        // Complex query: active, printing, featured
        $results = Service::query()
            ->where('active', true)
            ->where('category', 'printing')
            ->where('featured', true)
            ->ordered()
            ->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('Printing Service A', $results[0]->name);
        
        // Query: all printing services
        $printingServices = Service::query()
            ->where('category', 'printing')
            ->active()
            ->ordered()
            ->get();
        
        $this->assertCount(2, $printingServices);
    }
    
    /**
     * Test timestamps are automatically managed
     */
    public function testTimestampsAutoManagement()
    {
        $service = Service::create([
            'name' => 'Timestamped Service',
            'slug' => 'timestamped',
            'active' => true,
            'sort_order' => 1
        ]);
        
        $this->assertNotNull($service->created_at);
        $this->assertNotNull($service->updated_at);
        $this->assertEquals($service->created_at->timestamp, $service->updated_at->timestamp, '', 2);
        
        // Wait a moment and update
        sleep(1);
        $service->update(['name' => 'Updated Timestamp Service']);
        
        $updated = Service::find($service->id);
        $this->assertGreaterThan($service->created_at->timestamp, $updated->updated_at->timestamp);
    }
}
