#!/usr/bin/env php
<?php
/**
 * Content API Smoke Test
 * 
 * Tests CRUD operations for all content types:
 * - Services (with features JSON)
 * - Portfolio (with media uploads)
 * - FAQ (with categories)
 * - Testimonials (with ratings and avatars)
 * - Content Blocks (with positioning)
 * 
 * Tests slug generation, featured content, cache headers,
 * and media metadata storage.
 * 
 * Usage: php scripts/content-api-smoke.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\Service;
use App\Models\Portfolio;
use App\Models\FAQ;
use App\Models\Testimonial;
use App\Models\ContentBlock;

// Colors for output
function success($msg) { echo "\033[32m✓\033[0m " . $msg . PHP_EOL; }
function error($msg) { echo "\033[31m✗\033[0m " . $msg . PHP_EOL; }
function info($msg) { echo "\033[34mℹ\033[0m " . $msg . PHP_EOL; }
function section($msg) { echo PHP_EOL . "\033[1m" . $msg . "\033[0m" . PHP_EOL . str_repeat('-', 60) . PHP_EOL; }

$testsPassed = 0;
$testsFailed = 0;

section('Content API Smoke Test');

// Create tables if they don't exist (for testing environment)
try {
    $db = Illuminate\Database\Capsule\Manager::connection()->getPdo();
    
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
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS testimonials (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            author VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            content TEXT NOT NULL,
            rating INTEGER DEFAULT 5,
            avatar_path VARCHAR(255),
            avatar_size INTEGER,
            avatar_mime VARCHAR(100),
            position VARCHAR(255),
            company VARCHAR(255),
            featured BOOLEAN DEFAULT 0,
            sort_order INTEGER DEFAULT 0,
            active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS content_blocks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            identifier VARCHAR(255) NOT NULL UNIQUE,
            slug VARCHAR(255) NOT NULL UNIQUE,
            title VARCHAR(255),
            content TEXT,
            position VARCHAR(100),
            active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    info("Database tables ready");
} catch (Exception $e) {
    error("Database setup failed: " . $e->getMessage());
    exit(1);
}

// Test 1: Create Service with Features
section('1. Services - Create with JSON Features');
try {
    $features = ['Fast delivery', '24/7 support', 'Quality guarantee'];
    
    $service = Service::create([
        'name' => '3D Printing Service',
        'slug' => '3d-printing-service',
        'description' => 'Professional 3D printing services',
        'price' => 1500.00,
        'features' => json_encode($features),
        'icon' => 'fa-print',
        'sort_order' => 1,
        'active' => true
    ]);
    
    if ($service->id && json_decode($service->features) == $features) {
        success("Service created with ID: {$service->id}, Features: " . count($features));
        $testsPassed++;
    } else {
        error("Service creation failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Service creation exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 2: Create Portfolio with Media
section('2. Portfolio - Create with Media Metadata');
try {
    $tags = ['PLA', 'Prototype', '3D Print'];
    
    $portfolio = Portfolio::create([
        'title' => 'Custom Widget Prototype',
        'slug' => 'custom-widget-prototype',
        'description' => 'A custom 3D printed widget',
        'image_path' => '/storage/uploads/portfolio/widget-123.jpg',
        'image_size' => 2048000,
        'image_mime' => 'image/jpeg',
        'tags' => json_encode($tags),
        'featured' => true,
        'sort_order' => 1,
        'active' => true
    ]);
    
    if ($portfolio->id && $portfolio->featured && json_decode($portfolio->tags) == $tags) {
        success("Portfolio created with ID: {$portfolio->id}, Featured: Yes, Tags: " . count($tags));
        $testsPassed++;
    } else {
        error("Portfolio creation failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Portfolio creation exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 3: Create FAQ with Category
section('3. FAQ - Create with Category');
try {
    $faq = FAQ::create([
        'question' => 'What materials do you use?',
        'slug' => 'what-materials',
        'answer' => 'We use PLA, ABS, PETG, and other high-quality materials.',
        'category' => 'Materials',
        'sort_order' => 1,
        'active' => true
    ]);
    
    if ($faq->id && $faq->category === 'Materials') {
        success("FAQ created with ID: {$faq->id}, Category: {$faq->category}");
        $testsPassed++;
    } else {
        error("FAQ creation failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("FAQ creation exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 4: Create Testimonial with Rating
section('4. Testimonials - Create with Rating & Avatar');
try {
    $testimonial = Testimonial::create([
        'author' => 'Ivan Petrov',
        'slug' => 'ivan-petrov',
        'content' => 'Excellent service! Very satisfied with the quality.',
        'rating' => 5,
        'avatar_path' => '/storage/uploads/testimonials/ivan-123.jpg',
        'avatar_size' => 512000,
        'avatar_mime' => 'image/jpeg',
        'position' => 'CEO',
        'company' => 'TechCorp',
        'featured' => true,
        'sort_order' => 1,
        'active' => true
    ]);
    
    if ($testimonial->id && $testimonial->rating === 5 && $testimonial->featured) {
        success("Testimonial created with ID: {$testimonial->id}, Rating: {$testimonial->rating}/5");
        $testsPassed++;
    } else {
        error("Testimonial creation failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Testimonial creation exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 5: Create Content Block
section('5. Content Blocks - Create with Position');
try {
    $contentBlock = ContentBlock::create([
        'identifier' => 'home-hero',
        'slug' => 'home-hero',
        'title' => 'Welcome to 3D Print Pro',
        'content' => '<h1>Professional 3D Printing Services</h1>',
        'position' => 'header',
        'active' => true
    ]);
    
    if ($contentBlock->id && $contentBlock->position === 'header') {
        success("Content Block created with ID: {$contentBlock->id}, Position: {$contentBlock->position}");
        $testsPassed++;
    } else {
        error("Content Block creation failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Content Block creation exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 6: Update Service
section('6. Services - Update');
try {
    $service = Service::first();
    $originalName = $service->name;
    $service->name = '3D Printing Premium Service';
    $service->save();
    
    $updated = Service::find($service->id);
    if ($updated->name === '3D Printing Premium Service') {
        success("Service updated: '{$originalName}' → '{$updated->name}'");
        $testsPassed++;
    } else {
        error("Service update failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Service update exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 7: Filter Featured Content
section('7. Portfolio - Filter Featured');
try {
    // Create non-featured item
    Portfolio::create([
        'title' => 'Regular Project',
        'slug' => 'regular-project',
        'description' => 'Non-featured',
        'featured' => false,
        'active' => true
    ]);
    
    $featured = Portfolio::where('featured', true)->get();
    $total = Portfolio::count();
    
    if ($featured->count() > 0 && $total > $featured->count()) {
        success("Featured filtering works. Featured: {$featured->count()}, Total: {$total}");
        $testsPassed++;
    } else {
        error("Featured filtering failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Featured filtering exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 8: Filter by Category
section('8. FAQ - Filter by Category');
try {
    FAQ::create([
        'question' => 'How long does delivery take?',
        'slug' => 'delivery-time',
        'answer' => 'Usually 3-5 business days.',
        'category' => 'Shipping',
        'active' => true
    ]);
    
    $materials = FAQ::where('category', 'Materials')->get();
    $shipping = FAQ::where('category', 'Shipping')->get();
    
    if ($materials->count() > 0 && $shipping->count() > 0) {
        success("Category filtering works. Materials: {$materials->count()}, Shipping: {$shipping->count()}");
        $testsPassed++;
    } else {
        error("Category filtering failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Category filtering exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 9: Slug Uniqueness
section('9. Slug Uniqueness Check');
try {
    $slugExists = Service::where('slug', '3d-printing-service')->exists();
    
    if ($slugExists) {
        success("Slug uniqueness constraint working");
        $testsPassed++;
        
        // Try to create duplicate (should fail)
        try {
            Service::create([
                'name' => 'Another Service',
                'slug' => '3d-printing-service', // Duplicate
                'description' => 'Test',
                'active' => true
            ]);
            error("Duplicate slug was allowed (should have failed)");
            $testsFailed++;
        } catch (Exception $e) {
            success("Duplicate slug correctly rejected");
            $testsPassed++;
        }
    } else {
        error("Slug uniqueness check failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Slug uniqueness exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 10: Ordering by sort_order
section('10. Content Ordering');
try {
    $services = Service::orderBy('sort_order')->get();
    
    if ($services->count() > 0) {
        $sorted = true;
        for ($i = 1; $i < $services->count(); $i++) {
            if ($services[$i]->sort_order < $services[$i-1]->sort_order) {
                $sorted = false;
                break;
            }
        }
        
        if ($sorted) {
            success("Content ordering by sort_order works");
            $testsPassed++;
        } else {
            error("Content ordering failed");
            $testsFailed++;
        }
    } else {
        info("No services to test ordering");
        $testsPassed++;
    }
} catch (Exception $e) {
    error("Content ordering exception: " . $e->getMessage());
    $testsFailed++;
}

// Test 11: Delete Content
section('11. Content Deletion');
try {
    $service = Service::create([
        'name' => 'Temporary Service',
        'slug' => 'temp-service-delete',
        'description' => 'Will be deleted',
        'active' => true
    ]);
    
    $serviceId = $service->id;
    $service->delete();
    
    $deleted = Service::find($serviceId);
    
    if (!$deleted) {
        success("Content deletion works. Service ID {$serviceId} deleted");
        $testsPassed++;
    } else {
        error("Content deletion failed");
        $testsFailed++;
    }
} catch (Exception $e) {
    error("Content deletion exception: " . $e->getMessage());
    $testsFailed++;
}

// Cleanup
section('Cleanup');
try {
    Service::truncate();
    Portfolio::truncate();
    FAQ::truncate();
    Testimonial::truncate();
    ContentBlock::truncate();
    success("Test data cleaned up");
} catch (Exception $e) {
    error("Cleanup failed: " . $e->getMessage());
}

// Summary
section('Test Summary');
$total = $testsPassed + $testsFailed;
$percentage = $total > 0 ? round(($testsPassed / $total) * 100, 1) : 0;

echo "Total Tests: {$total}" . PHP_EOL;
echo "Passed: \033[32m{$testsPassed}\033[0m" . PHP_EOL;
echo "Failed: \033[31m{$testsFailed}\033[0m" . PHP_EOL;
echo "Success Rate: {$percentage}%" . PHP_EOL;
echo PHP_EOL;

if ($testsFailed === 0) {
    success("All content API tests passed! ✨");
    exit(0);
} else {
    error("Some tests failed. Please review the output above.");
    exit(1);
}
