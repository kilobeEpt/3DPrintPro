<?php
/**
 * Head Include
 * 
 * Meta tags, SEO, structured data, and stylesheet includes
 * 
 * Variables expected:
 * - $page_title: Page title
 * - $page_description: Meta description
 * - $page_keywords: Meta keywords (optional)
 * - $canonical_url: Canonical URL
 * - $og_image: Open Graph image (optional, defaults to site default)
 * - $breadcrumbs: Array of breadcrumb items (optional)
 * - $structured_data: Additional structured data (optional)
 */

$content = require __DIR__ . '/../data/content.php';
$site = $content['site'];
$contact = $content['contact'];

// Defaults
$og_image = $og_image ?? $site['url'] . '/images/og-image.jpg';
$page_keywords = $page_keywords ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Primary Meta Tags -->
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <?php if ($page_keywords): ?>
    <meta name="keywords" content="<?= htmlspecialchars($page_keywords) ?>">
    <?php endif; ?>
    
    <!-- Canonical & Alternate Links -->
    <link rel="canonical" href="<?= htmlspecialchars($canonical_url) ?>">
    <link rel="alternate" hreflang="ru-RU" href="<?= htmlspecialchars($canonical_url) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($canonical_url) ?>">
    
    <!-- Geo Tags -->
    <meta name="geo.region" content="RU-OMS">
    <meta name="geo.placename" content="<?= htmlspecialchars($contact['address']['city']) ?>">
    <meta name="geo.position" content="<?= $contact['geo']['latitude'] ?>;<?= $contact['geo']['longitude'] ?>">
    <meta name="ICBM" content="<?= $contact['geo']['latitude'] ?>, <?= $contact['geo']['longitude'] ?>">
    
    <!-- Business Contact Meta -->
    <meta name="city" content="<?= htmlspecialchars($contact['address']['city']) ?>">
    <meta name="country" content="Россия">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($canonical_url) ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($site['name']) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="ru_RU">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= htmlspecialchars($canonical_url) ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($og_image) ?>">
    
    <!-- JSON-LD Structured Data: LocalBusiness -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "LocalBusiness",
      "@id": "<?= $site['url'] ?>/#organization",
      "name": "<?= htmlspecialchars($site['name']) ?>",
      "description": "<?= htmlspecialchars($site['description']) ?>",
      "url": "<?= $site['url'] ?>/",
      "logo": "<?= $site['url'] ?>/images/logo.png",
      "image": "<?= htmlspecialchars($og_image) ?>",
      "telephone": "<?= htmlspecialchars($contact['phone']) ?>",
      "email": "<?= htmlspecialchars($contact['email']) ?>",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "<?= htmlspecialchars($contact['address']['street']) ?>",
        "addressLocality": "<?= htmlspecialchars($contact['address']['city']) ?>",
        "addressRegion": "<?= htmlspecialchars($contact['address']['region']) ?>",
        "postalCode": "<?= htmlspecialchars($contact['address']['postal_code']) ?>",
        "addressCountry": "<?= htmlspecialchars($contact['address']['country']) ?>"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": <?= $contact['geo']['latitude'] ?>,
        "longitude": <?= $contact['geo']['longitude'] ?>
      },
      "openingHoursSpecification": [
        <?php foreach ($contact['working_hours']['structured'] as $index => $hours): ?>
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": <?= json_encode($hours['days']) ?>,
          "opens": "<?= $hours['opens'] ?>",
          "closes": "<?= $hours['closes'] ?>"
        }<?= $index < count($contact['working_hours']['structured']) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
      ],
      "priceRange": "₽₽",
      "areaServed": {
        "@type": "City",
        "name": "<?= htmlspecialchars($contact['address']['city']) ?>"
      },
      "sameAs": [
        "<?= htmlspecialchars($contact['telegram']) ?>"
      ]
    }
    </script>
    
    <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
    <!-- JSON-LD Structured Data: BreadcrumbList -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
        {
          "@type": "ListItem",
          "position": <?= $index + 1 ?>,
          "name": "<?= htmlspecialchars($crumb['name']) ?>",
          "item": "<?= htmlspecialchars($crumb['url']) ?>"
        }<?= $index < count($breadcrumbs) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
      ]
    }
    </script>
    <?php endif; ?>
    
    <?php if (isset($structured_data)): ?>
    <!-- Additional Structured Data -->
    <script type="application/ld+json">
    <?= json_encode($structured_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <?php endif; ?>
    
    <!-- Быстрое скрытие прелоадера -->
    <style>
        .preloader {
            animation: fadeOutPreloader 0.5s ease 0.2s forwards;
        }
        @keyframes fadeOutPreloader {
            to {
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
            }
        }
    </style>
    
    <!-- Styles -->
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/responsive.css">
    <link rel="stylesheet" href="/css/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🖨️</text></svg>">
</head>
<body<?= isset($body_data_page) ? ' data-page="' . htmlspecialchars($body_data_page) . '"' : '' ?>>
    <!-- Preloader -->
    <div class="preloader" id="preloader">
        <div class="loader">
            <div class="cube">
                <div class="face front"></div>
                <div class="face back"></div>
                <div class="face right"></div>
                <div class="face left"></div>
                <div class="face top"></div>
                <div class="face bottom"></div>
            </div>
        </div>
    </div>
