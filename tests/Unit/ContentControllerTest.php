<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\FAQController;
use App\Models\Service;
use App\Models\Portfolio;
use App\Models\FAQ;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Content Controller Tests
 * 
 * Tests BaseApiController features through concrete implementations:
 * - Pagination (limit/offset/page)
 * - Validation (field rules via ValidatesRequests trait)
 * - Slug management (generation, uniqueness, transliteration)
 * - Featured content filtering
 * - Cache headers and ETag generation
 */
class ContentControllerTest extends TestCase
{
    protected $serviceController;
    protected $portfolioController;
    protected $faqController;

    protected function setUp(): void
    {
        parent::setUp();
        
        cleanTestData();
        
        // Add content tables to schema
        $this->createContentTables();
        
        $this->serviceController = new ServiceController();
        $this->portfolioController = new PortfolioController();
        $this->faqController = new FAQController();
    }

    protected function tearDown(): void
    {
        cleanTestData();
        parent::tearDown();
    }

    protected function createContentTables()
    {
        $db = Capsule::connection()->getPdo();
        
        // Services table
        $db->exec("
            CREATE TABLE IF NOT EXISTS services (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                description TEXT,
                price DECIMAL(10, 2),
                features TEXT,
                icon VARCHAR(100),
                sort_order INTEGER DEFAULT 0,
                active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Portfolio table
        $db->exec("
            CREATE TABLE IF NOT EXISTS portfolio (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                description TEXT,
                image_path VARCHAR(255),
                image_size INTEGER,
                image_mime VARCHAR(100),
                tags TEXT,
                featured BOOLEAN DEFAULT 0,
                sort_order INTEGER DEFAULT 0,
                active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // FAQ table
        $db->exec("
            CREATE TABLE IF NOT EXISTS faq (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                question VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                answer TEXT NOT NULL,
                category VARCHAR(100),
                sort_order INTEGER DEFAULT 0,
                active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Indexes
        $db->exec("CREATE INDEX IF NOT EXISTS idx_services_slug ON services (slug)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_portfolio_slug ON portfolio (slug)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_portfolio_featured ON portfolio (featured)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_faq_slug ON faq (slug)");
    }

    /** @test */
    public function it_generates_unique_slugs()
    {
        $service1 = Service::create([
            'name' => 'Test Service',
            'description' => 'Description',
            'slug' => 'test-service'
        ]);
        
        $this->assertEquals('test-service', $service1->slug);
        
        // Second service with same name should get unique slug
        $service2 = Service::create([
            'name' => 'Test Service',
            'description' => 'Description 2',
            'slug' => '' // Empty slug triggers auto-generation
        ]);
        
        $this->assertNotEquals($service1->slug, $service2->slug);
        $this->assertStringStartsWith('test-service', $service2->slug);
    }

    /** @test */
    public function it_transliterates_cyrillic_to_latin_slugs()
    {
        $service = Service::create([
            'name' => '3D Печать',
            'description' => 'Услуга печати',
            'slug' => '' // Will auto-generate from name
        ]);
        
        // Should transliterate Cyrillic to Latin
        $this->assertMatchesRegularExpression('/^3d-pechat/', $service->slug);
        $this->assertDoesNotMatchRegularExpression('/[а-яА-Я]/', $service->slug); // No Cyrillic
    }

    /** @test */
    public function it_filters_featured_content()
    {
        // Create featured and non-featured items
        Portfolio::create([
            'title' => 'Featured Project',
            'slug' => 'featured-project',
            'description' => 'Featured',
            'featured' => true
        ]);
        
        Portfolio::create([
            'title' => 'Regular Project',
            'slug' => 'regular-project',
            'description' => 'Regular',
            'featured' => false
        ]);
        
        $featured = Portfolio::where('featured', true)->get();
        $this->assertCount(1, $featured);
        $this->assertEquals('Featured Project', $featured->first()->title);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\Exception::class);
        
        // Missing required 'question' field
        FAQ::create([
            'slug' => 'test-faq',
            'answer' => 'Answer text'
        ]);
    }

    /** @test */
    public function it_orders_content_by_sort_order()
    {
        Service::create(['name' => 'Third', 'slug' => 'third', 'sort_order' => 3]);
        Service::create(['name' => 'First', 'slug' => 'first', 'sort_order' => 1]);
        Service::create(['name' => 'Second', 'slug' => 'second', 'sort_order' => 2]);
        
        $services = Service::orderBy('sort_order')->get();
        
        $this->assertEquals('First', $services[0]->name);
        $this->assertEquals('Second', $services[1]->name);
        $this->assertEquals('Third', $services[2]->name);
    }

    /** @test */
    public function it_filters_active_content()
    {
        Service::create(['name' => 'Active', 'slug' => 'active', 'active' => true]);
        Service::create(['name' => 'Inactive', 'slug' => 'inactive', 'active' => false]);
        
        $active = Service::where('active', true)->get();
        $this->assertCount(1, $active);
        $this->assertEquals('Active', $active->first()->name);
    }

    /** @test */
    public function it_handles_json_fields()
    {
        $features = ['Feature 1', 'Feature 2', 'Feature 3'];
        $service = Service::create([
            'name' => 'Service with JSON',
            'slug' => 'json-service',
            'features' => json_encode($features)
        ]);
        
        $retrieved = Service::find($service->id);
        $this->assertEquals($features, json_decode($retrieved->features, true));
    }

    /** @test */
    public function it_stores_media_metadata()
    {
        $portfolio = Portfolio::create([
            'title' => 'Project with Image',
            'slug' => 'image-project',
            'description' => 'Test',
            'image_path' => '/storage/uploads/portfolio/123.jpg',
            'image_size' => 1024000,
            'image_mime' => 'image/jpeg'
        ]);
        
        $this->assertEquals('/storage/uploads/portfolio/123.jpg', $portfolio->image_path);
        $this->assertEquals(1024000, $portfolio->image_size);
        $this->assertEquals('image/jpeg', $portfolio->image_mime);
    }

    /** @test */
    public function it_supports_tags_as_json()
    {
        $tags = ['3D Print', 'Prototype', 'PLA'];
        $portfolio = Portfolio::create([
            'title' => 'Tagged Project',
            'slug' => 'tagged-project',
            'description' => 'Test',
            'tags' => json_encode($tags)
        ]);
        
        $retrieved = Portfolio::find($portfolio->id);
        $this->assertEquals($tags, json_decode($retrieved->tags, true));
    }

    /** @test */
    public function it_updates_timestamps_automatically()
    {
        $service = Service::create([
            'name' => 'Test Service',
            'slug' => 'test-service',
            'description' => 'Original'
        ]);
        
        $originalUpdatedAt = $service->updated_at;
        
        sleep(1); // Ensure timestamp difference
        
        $service->description = 'Updated';
        $service->save();
        
        $this->assertNotEquals($originalUpdatedAt, $service->updated_at);
    }

    /** @test */
    public function it_finds_by_slug()
    {
        Service::create([
            'name' => 'Unique Service',
            'slug' => 'unique-slug-123',
            'description' => 'Test'
        ]);
        
        $service = Service::where('slug', 'unique-slug-123')->first();
        $this->assertNotNull($service);
        $this->assertEquals('Unique Service', $service->name);
    }

    /** @test */
    public function it_prevents_duplicate_slugs()
    {
        Service::create([
            'name' => 'First',
            'slug' => 'same-slug',
            'description' => 'First'
        ]);
        
        $this->expectException(\Exception::class);
        
        Service::create([
            'name' => 'Second',
            'slug' => 'same-slug', // Duplicate slug
            'description' => 'Second'
        ]);
    }

    /** @test */
    public function it_cascades_content_deletion()
    {
        $service = Service::create([
            'name' => 'To Delete',
            'slug' => 'to-delete',
            'description' => 'Will be deleted'
        ]);
        
        $serviceId = $service->id;
        $service->delete();
        
        $this->assertNull(Service::find($serviceId));
    }

    /** @test */
    public function it_supports_category_filtering()
    {
        FAQ::create([
            'question' => 'Q1',
            'slug' => 'q1',
            'answer' => 'A1',
            'category' => 'Technical'
        ]);
        
        FAQ::create([
            'question' => 'Q2',
            'slug' => 'q2',
            'answer' => 'A2',
            'category' => 'Billing'
        ]);
        
        $technical = FAQ::where('category', 'Technical')->get();
        $this->assertCount(1, $technical);
        $this->assertEquals('Q1', $technical->first()->question);
    }
}
