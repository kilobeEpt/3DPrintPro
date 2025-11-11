<?php
// ========================================
// Database Initialization Script v2.0
// Idempotent seed process with optional hard reset
// ========================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Security token for hard reset (change this to a secure random string in production)
define('RESET_TOKEN', 'CHANGE_ME_IN_PRODUCTION_123456');

$response = [
    'status' => 'OK',
    'mode' => 'normal',
    'actions' => []
];

try {
    $db = new Database();
    $pdo = $db->getPDO();
    
    // Check for reset mode
    $resetMode = isset($_GET['reset']) && $_GET['reset'] === RESET_TOKEN;
    
    if ($resetMode) {
        $response['mode'] = 'hard_reset';
        $response['actions'][] = '⚠️ HARD RESET MODE ENABLED';
        
        // Delete all data from tables (keep structure)
        $tables = ['services', 'portfolio', 'testimonials', 'faq', 'content_blocks'];
        foreach ($tables as $table) {
            $pdo->exec("DELETE FROM `$table`");
            $response['actions'][] = "🗑️ Cleared table: $table";
        }
        
        // Reset auto-increment counters
        foreach ($tables as $table) {
            $pdo->exec("ALTER TABLE `$table` AUTO_INCREMENT = 1");
        }
        
        $response['actions'][] = '✅ Tables cleared and ready for fresh seed';
    }
    
    // Load seed data
    $seedData = require __DIR__ . '/../database/seed-data.php';
    
    // ========================================
    // SEED SERVICES
    // ========================================
    $servicesSeeded = 0;
    $servicesUpdated = 0;
    
    foreach ($seedData['services'] as $service) {
        // Check if service exists by slug
        $stmt = $pdo->prepare("SELECT id FROM services WHERE slug = ?");
        $stmt->execute([$service['slug']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing service
            $db->updateRecord('services', $existing['id'], $service);
            $servicesUpdated++;
        } else {
            // Insert new service
            $db->insertRecord('services', $service);
            $servicesSeeded++;
        }
    }
    
    if ($servicesSeeded > 0 || $servicesUpdated > 0) {
        $response['actions'][] = "✅ Services: $servicesSeeded added, $servicesUpdated updated";
    } else {
        $response['actions'][] = "✓ Services already up to date";
    }
    
    // ========================================
    // SEED PORTFOLIO
    // ========================================
    $portfolioSeeded = 0;
    $portfolioUpdated = 0;
    
    foreach ($seedData['portfolio'] as $item) {
        // Check if portfolio item exists by title
        $stmt = $pdo->prepare("SELECT id FROM portfolio WHERE title = ?");
        $stmt->execute([$item['title']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing item
            $db->updateRecord('portfolio', $existing['id'], $item);
            $portfolioUpdated++;
        } else {
            // Insert new item
            $db->insertRecord('portfolio', $item);
            $portfolioSeeded++;
        }
    }
    
    if ($portfolioSeeded > 0 || $portfolioUpdated > 0) {
        $response['actions'][] = "✅ Portfolio: $portfolioSeeded added, $portfolioUpdated updated";
    } else {
        $response['actions'][] = "✓ Portfolio already up to date";
    }
    
    // ========================================
    // SEED TESTIMONIALS
    // ========================================
    $testimonialsSeeded = 0;
    $testimonialsUpdated = 0;
    
    foreach ($seedData['testimonials'] as $testimonial) {
        // Check if testimonial exists by name and text (first 50 chars)
        $stmt = $pdo->prepare("SELECT id FROM testimonials WHERE name = ? AND LEFT(text, 50) = ?");
        $stmt->execute([$testimonial['name'], substr($testimonial['text'], 0, 50)]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing testimonial
            $db->updateRecord('testimonials', $existing['id'], $testimonial);
            $testimonialsUpdated++;
        } else {
            // Insert new testimonial
            $db->insertRecord('testimonials', $testimonial);
            $testimonialsSeeded++;
        }
    }
    
    if ($testimonialsSeeded > 0 || $testimonialsUpdated > 0) {
        $response['actions'][] = "✅ Testimonials: $testimonialsSeeded added, $testimonialsUpdated updated";
    } else {
        $response['actions'][] = "✓ Testimonials already up to date";
    }
    
    // ========================================
    // SEED FAQ
    // ========================================
    $faqSeeded = 0;
    $faqUpdated = 0;
    
    foreach ($seedData['faq'] as $faqItem) {
        // Check if FAQ exists by question
        $stmt = $pdo->prepare("SELECT id FROM faq WHERE question = ?");
        $stmt->execute([$faqItem['question']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing FAQ
            $db->updateRecord('faq', $existing['id'], $faqItem);
            $faqUpdated++;
        } else {
            // Insert new FAQ
            $db->insertRecord('faq', $faqItem);
            $faqSeeded++;
        }
    }
    
    if ($faqSeeded > 0 || $faqUpdated > 0) {
        $response['actions'][] = "✅ FAQ: $faqSeeded added, $faqUpdated updated";
    } else {
        $response['actions'][] = "✓ FAQ already up to date";
    }
    
    // ========================================
    // SEED CONTENT BLOCKS
    // ========================================
    $contentSeeded = 0;
    $contentUpdated = 0;
    
    foreach ($seedData['content_blocks'] as $block) {
        // Check if content block exists by block_name
        $stmt = $pdo->prepare("SELECT id FROM content_blocks WHERE block_name = ?");
        $stmt->execute([$block['block_name']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing block
            $db->updateRecord('content_blocks', $existing['id'], $block);
            $contentUpdated++;
        } else {
            // Insert new block
            $db->insertRecord('content_blocks', $block);
            $contentSeeded++;
        }
    }
    
    if ($contentSeeded > 0 || $contentUpdated > 0) {
        $response['actions'][] = "✅ Content blocks: $contentSeeded added, $contentUpdated updated";
    } else {
        $response['actions'][] = "✓ Content blocks already up to date";
    }
    
    // ========================================
    // SEED SETTINGS
    // ========================================
    $settingsSeeded = 0;
    $settingsUpdated = 0;
    
    foreach ($seedData['settings'] as $key => $value) {
        $existing = $db->getSetting($key);
        
        if ($existing === null) {
            // Insert new setting
            $db->saveSetting($key, $value);
            $settingsSeeded++;
        } else {
            // Setting exists - in normal mode, keep existing value
            // In reset mode, update with seed value
            if ($resetMode) {
                $db->saveSetting($key, $value);
                $settingsUpdated++;
            }
        }
    }
    
    if ($settingsSeeded > 0) {
        $response['actions'][] = "✅ Settings: $settingsSeeded new keys added";
    }
    if ($settingsUpdated > 0) {
        $response['actions'][] = "✅ Settings: $settingsUpdated keys reset to defaults";
    }
    if ($settingsSeeded === 0 && $settingsUpdated === 0) {
        $response['actions'][] = "✓ Settings already up to date";
    }
    
    // ========================================
    // VERIFY AND REPORT
    // ========================================
    $stats = [
        'services' => $db->getCount('services'),
        'portfolio' => $db->getCount('portfolio'),
        'testimonials' => $db->getCount('testimonials'),
        'faq' => $db->getCount('faq'),
        'content_blocks' => $db->getCount('content_blocks'),
        'settings' => $db->getCount('settings')
    ];
    
    $response['database_stats'] = $stats;
    
    // Check if all tables have minimum data
    $minimumViable = 
        $stats['services'] >= 1 &&
        $stats['portfolio'] >= 1 &&
        $stats['testimonials'] >= 1 &&
        $stats['faq'] >= 1 &&
        $stats['content_blocks'] >= 1 &&
        $stats['settings'] >= 5;
    
    if ($minimumViable) {
        $response['summary'] = '✅ Database initialized successfully - Ready for production';
        $response['production_ready'] = true;
    } else {
        $response['summary'] = '⚠️ Database initialized but some tables may need more data';
        $response['production_ready'] = false;
    }
    
    $db->close();
    
} catch (Exception $e) {
    http_response_code(500);
    $response['status'] = 'Error';
    $response['error'] = $e->getMessage();
    $response['trace'] = $e->getTraceAsString();
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
