<?php
// Load content data
$CONTENT = require __DIR__ . '/../data/content.php';
$site = $CONTENT['site'];
$meta = $CONTENT['meta'][$page_meta_key] ?? $CONTENT['meta']['home'];
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Primary Meta Tags -->
<title><?= htmlspecialchars($meta['title']) ?></title>
<meta name="description" content="<?= htmlspecialchars($meta['description']) ?>">
<meta name="keywords" content="<?= htmlspecialchars($meta['keywords']) ?>">

<!-- Canonical & Alternate Links -->
<link rel="canonical" href="<?= $site['url'] ?>/<?= $canonical_url ?>">
<link rel="alternate" hreflang="ru-RU" href="<?= $site['url'] ?>/<?= $canonical_url ?>">
<link rel="alternate" hreflang="x-default" href="<?= $site['url'] ?>/<?= $canonical_url ?>">

<!-- Geo Tags -->
<meta name="geo.region" content="RU-OMS">
<meta name="geo.placename" content="<?= $site['city'] ?>">
<meta name="geo.position" content="<?= $site['geo']['latitude'] ?>;<?= $site['geo']['longitude'] ?>">
<meta name="ICBM" content="<?= $site['geo']['latitude'] ?>, <?= $site['geo']['longitude'] ?>">

<!-- Business Contact Meta -->
<meta name="city" content="<?= $site['city'] ?>">
<meta name="country" content="<?= $site['country'] ?>">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?= $site['url'] ?>/<?= $canonical_url ?>">
<meta property="og:site_name" content="<?= $site['name'] ?>">
<meta property="og:title" content="<?= htmlspecialchars($meta['title']) ?>">
<meta property="og:description" content="<?= htmlspecialchars($meta['description']) ?>">
<meta property="og:image" content="<?= $site['url'] ?>/images/og-image.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="ru_RU">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="<?= $site['url'] ?>/<?= $canonical_url ?>">
<meta name="twitter:title" content="<?= htmlspecialchars($meta['title']) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($meta['description']) ?>">
<meta name="twitter:image" content="<?= $site['url'] ?>/images/og-image.jpg">

<!-- JSON-LD Structured Data: LocalBusiness -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "@id": "<?= $site['url'] ?>/#organization",
  "name": "<?= $site['name'] ?>",
  "description": "Профессиональная 3D печать в Омске: FDM, SLA, SLS технологии, 3D моделирование, постобработка",
  "url": "<?= $site['url'] ?>/",
  "logo": "<?= $site['url'] ?>/images/logo.png",
  "image": "<?= $site['url'] ?>/images/og-image.jpg",
  "telephone": "<?= $site['phone'] ?>",
  "email": "<?= $site['email'] ?>",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "<?= $site['address'] ?>",
    "addressLocality": "<?= $site['city'] ?>",
    "addressRegion": "<?= $site['region'] ?>",
    "postalCode": "<?= $site['postal_code'] ?>",
    "addressCountry": "RU"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": <?= $site['geo']['latitude'] ?>,
    "longitude": <?= $site['geo']['longitude'] ?>
  },
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
      "opens": "09:00",
      "closes": "18:00"
    }
  ],
  "priceRange": "₽₽",
  "areaServed": {
    "@type": "City",
    "name": "<?= $site['city'] ?>"
  },
  "sameAs": [
    "<?= $site['telegram'] ?>"
  ]
}
</script>

<!-- JSON-LD Structured Data: Service -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "serviceType": "3D печать и 3D моделирование",
  "provider": {
    "@id": "<?= $site['url'] ?>/#organization"
  },
  "areaServed": {
    "@type": "City",
    "name": "<?= $site['city'] ?>"
  },
  "hasOfferCatalog": {
    "@type": "OfferCatalog",
    "name": "Услуги 3D печати",
    "itemListElement": [
      {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Service",
          "name": "FDM 3D печать в Омске",
          "description": "Печать методом послойного наплавления для прототипов и функциональных деталей"
        }
      },
      {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Service",
          "name": "SLA/SLS 3D печать в Омске",
          "description": "Высокоточная печать с высокой детализацией для требовательных проектов"
        }
      },
      {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Service",
          "name": "3D моделирование в Омске",
          "description": "Создание 3D моделей по эскизам, чертежам или идеям"
        }
      },
      {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Service",
          "name": "Постобработка 3D изделий",
          "description": "Шлифовка, покраска, химическая обработка и сборка 3D печатных деталей"
        }
      }
    ]
  }
}
</script>

<!-- JSON-LD Structured Data: BreadcrumbList -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Главная",
      "item": "<?= $site['url'] ?>/"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Услуги 3D печати в Омске",
      "item": "<?= $site['url'] ?>/services.php"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "Портфолио работ",
      "item": "<?= $site['url'] ?>/portfolio.php"
    },
    {
      "@type": "ListItem",
      "position": 4,
      "name": "О компании",
      "item": "<?= $site['url'] ?>/about.php"
    },
    {
      "@type": "ListItem",
      "position": 5,
      "name": "Контакты в Омске",
      "item": "<?= $site['url'] ?>/contact.php"
    }
  ]
}
</script>

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

<!-- Theme initialization - Prevents FOUC -->
<script>
(function() {
    const savedTheme = localStorage.getItem('theme');
    const systemPreference = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    const theme = savedTheme || systemPreference;
    document.documentElement.setAttribute('data-theme', theme);
})();
</script>

<!-- Styles -->
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/responsive.css">
<link rel="stylesheet" href="css/animations.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Favicon -->
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🖨️</text></svg>">
