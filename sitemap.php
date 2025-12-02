<?php
/**
 * Dynamic XML Sitemap Generator
 * 
 * Generates sitemap with:
 * - All public .php pages
 * - Service anchor URLs for each service slug
 * - Accurate lastmod timestamps from filemtime
 * - Proper changefreq and priority values
 */

header('Content-Type: application/xml; charset=utf-8');

// Load content data for services
$CONTENT = require __DIR__ . '/data/content.php';
$site = $CONTENT['site'];
$services = $CONTENT['services'];
$baseUrl = $site['url'];

// Define pages with their properties
$pages = [
    [
        'loc' => '',  // Homepage
        'file' => 'index.php',
        'changefreq' => 'weekly',
        'priority' => '1.0'
    ],
    [
        'loc' => 'services.php',
        'file' => 'services.php',
        'changefreq' => 'monthly',
        'priority' => '0.9'
    ],
    [
        'loc' => 'portfolio.php',
        'file' => 'portfolio.php',
        'changefreq' => 'weekly',
        'priority' => '0.8'
    ],
    [
        'loc' => 'contact.php',
        'file' => 'contact.php',
        'changefreq' => 'monthly',
        'priority' => '0.8'
    ],
    [
        'loc' => 'about.php',
        'file' => 'about.php',
        'changefreq' => 'monthly',
        'priority' => '0.7'
    ],
    [
        'loc' => 'blog.php',
        'file' => 'blog.php',
        'changefreq' => 'weekly',
        'priority' => '0.7'
    ],
    [
        'loc' => 'why-us.php',
        'file' => 'why-us.php',
        'changefreq' => 'monthly',
        'priority' => '0.6'
    ],
    [
        'loc' => 'districts.php',
        'file' => 'districts.php',
        'changefreq' => 'monthly',
        'priority' => '0.6'
    ]
];

// Start XML output
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Add pages
foreach ($pages as $page) {
    $filePath = __DIR__ . '/' . $page['file'];
    $lastmod = file_exists($filePath) ? date('Y-m-d', filemtime($filePath)) : date('Y-m-d');
    $loc = $page['loc'] ? $baseUrl . '/' . $page['loc'] : $baseUrl . '/';
    
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
    echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "    <changefreq>{$page['changefreq']}</changefreq>\n";
    echo "    <priority>{$page['priority']}</priority>\n";
    echo "  </url>\n";
}

// Add service anchor URLs
foreach ($services as $service) {
    $slug = $service['slug'] ?? $service['id'];
    $loc = $baseUrl . '/services.php#' . $slug;
    
    // Use services.php file modification time
    $filePath = __DIR__ . '/services.php';
    $lastmod = file_exists($filePath) ? date('Y-m-d', filemtime($filePath)) : date('Y-m-d');
    
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
    echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    echo "  </url>\n";
}

echo "</urlset>\n";
