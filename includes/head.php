<?php
// Load content data
$CONTENT = require __DIR__ . '/../data/content.php';
$site = $CONTENT['site'];
$meta = $CONTENT['meta'][$page_meta_key] ?? $CONTENT['meta']['home'];

// Determine canonical URL (handle root/index special case)
$canonical_path = ($canonical_url === 'index.php' || $canonical_url === '') ? '' : $canonical_url;
$full_canonical = $site['url'] . ($canonical_path ? '/' . $canonical_path : '/');

// Determine OG type and image
$og_type = ($page_meta_key === 'blog') ? 'article' : 'website';
$og_image = $meta['og_image'] ?? $site['url'] . '/images/og-default.svg';
$twitter_image = $meta['twitter_image'] ?? $og_image;
$twitter_card = 'summary_large_image';

// Format keywords
$keywords_string = is_array($meta['keywords']) ? implode(', ', $meta['keywords']) : $meta['keywords'];
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Primary Meta Tags -->
<title><?= htmlspecialchars($meta['title']) ?></title>
<meta name="description" content="<?= htmlspecialchars($meta['description']) ?>">
<meta name="keywords" content="<?= htmlspecialchars($keywords_string) ?>">

<!-- Canonical & Alternate Links -->
<link rel="canonical" href="<?= $full_canonical ?>">
<link rel="alternate" hreflang="ru-RU" href="<?= $full_canonical ?>">
<link rel="alternate" hreflang="x-default" href="<?= $full_canonical ?>">

<!-- Geo Tags -->
<meta name="geo.region" content="RU-OMS">
<meta name="geo.placename" content="<?= $site['city'] ?>">
<meta name="geo.position" content="<?= $site['geo']['latitude'] ?>;<?= $site['geo']['longitude'] ?>">
<meta name="ICBM" content="<?= $site['geo']['latitude'] ?>, <?= $site['geo']['longitude'] ?>">

<!-- Business Contact Meta -->
<meta name="city" content="<?= $site['city'] ?>">
<meta name="country" content="<?= $site['country'] ?>">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="<?= $og_type ?>">
<meta property="og:url" content="<?= $full_canonical ?>">
<meta property="og:site_name" content="<?= $site['name'] ?>">
<meta property="og:title" content="<?= htmlspecialchars($meta['title']) ?>">
<meta property="og:description" content="<?= htmlspecialchars($meta['description']) ?>">
<meta property="og:image" content="<?= $og_image ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="ru_RU">
<?php if ($page_meta_key === 'blog' && !empty($CONTENT['blog_posts'])): ?>
<?php foreach ($CONTENT['blog_posts'] as $post): ?>
<meta property="article:tag" content="<?= htmlspecialchars($post['tags'][0] ?? '') ?>">
<?php endforeach; ?>
<meta property="article:author" content="3D Print Pro">
<meta property="article:section" content="3D печать">
<?php endif; ?>

<!-- Twitter Card -->
<meta name="twitter:card" content="<?= $twitter_card ?>">
<meta name="twitter:url" content="<?= $full_canonical ?>">
<meta name="twitter:title" content="<?= htmlspecialchars($meta['title']) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($meta['description']) ?>">
<meta name="twitter:image" content="<?= $twitter_image ?>">

<!-- JSON-LD Structured Data: Organization -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "@id": "<?= $site['url'] ?>/#organization",
  "name": "<?= $site['name'] ?>",
  "description": "Профессиональная 3D печать в Омске: FDM, SLA, SLS технологии, 3D моделирование, постобработка",
  "url": "<?= $site['url'] ?>/",
  "logo": {
    "@type": "ImageObject",
    "url": "<?= $site['url'] ?>/images/og-default.svg",
    "width": 1200,
    "height": 630
  },
  "image": "<?= $site['url'] ?>/images/og-default.svg",
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
  "foundingDate": "<?= $site['year_founded'] ?>",
  "sameAs": [
<?php 
$social_urls = array_column($site['social_links'], 'url');
$social_urls[] = $site['telegram'];
$social_urls[] = $site['whatsapp'];
$social_urls = array_unique(array_filter($social_urls));
echo '    "' . implode("\",\n    \"", $social_urls) . '"';
?>

  ]
}
</script>

<!-- JSON-LD Structured Data: LocalBusiness -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "@id": "<?= $site['url'] ?>/#localbusiness",
  "name": "<?= $site['name'] ?>",
  "description": "Профессиональная 3D печать в Омске: FDM, SLA, SLS технологии, 3D моделирование, постобработка",
  "url": "<?= $site['url'] ?>/",
  "logo": "<?= $site['url'] ?>/images/og-default.svg",
  "image": "<?= $site['url'] ?>/images/og-default.svg",
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
<?php echo '    "' . implode("\",\n    \"", $social_urls) . '"'; ?>

  ]
}
</script>

<!-- JSON-LD Structured Data: Website -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "@id": "<?= $site['url'] ?>/#website",
  "url": "<?= $site['url'] ?>/",
  "name": "<?= $site['name'] ?>",
  "description": "Профессиональная 3D печать в Омске",
  "publisher": {
    "@id": "<?= $site['url'] ?>/#organization"
  },
  "potentialAction": {
    "@type": "SearchAction",
    "target": "<?= $site['url'] ?>/search?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>

<!-- JSON-LD Structured Data: Service Catalog -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "itemListElement": [
<?php 
$services = $CONTENT['services'];
$service_count = count($services);
foreach ($services as $index => $service): 
$is_last = ($index === $service_count - 1);
?>
    {
      "@type": "ListItem",
      "position": <?= $index + 1 ?>,
      "item": {
        "@type": "Service",
        "serviceType": "<?= htmlspecialchars($service['name'], ENT_QUOTES) ?>",
        "name": "<?= htmlspecialchars($service['name'], ENT_QUOTES) ?> в Омске",
        "description": "<?= htmlspecialchars($service['description'], ENT_QUOTES) ?>",
        "provider": {
          "@id": "<?= $site['url'] ?>/#organization"
        },
        "areaServed": {
          "@type": "City",
          "name": "<?= $site['city'] ?>"
        },
        "offers": {
          "@type": "Offer",
          "price": "<?= htmlspecialchars($service['price'], ENT_QUOTES) ?>",
          "priceCurrency": "RUB"
        }
      }
    }<?= $is_last ? '' : ',' ?>

<?php endforeach; ?>
  ]
}
</script>

<!-- JSON-LD Structured Data: FAQPage -->
<?php if (!empty($CONTENT['faq'])): ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
<?php 
$faq = $CONTENT['faq'];
$faq_count = count($faq);
foreach ($faq as $index => $item): 
$is_last = ($index === $faq_count - 1);
?>
    {
      "@type": "Question",
      "name": "<?= htmlspecialchars($item['question'], ENT_QUOTES) ?>",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "<?= htmlspecialchars($item['answer'], ENT_QUOTES) ?>"
      }
    }<?= $is_last ? '' : ',' ?>

<?php endforeach; ?>
  ]
}
</script>
<?php endif; ?>

<!-- JSON-LD Structured Data: BlogPosting (for blog page) -->
<?php if ($page_meta_key === 'blog' && !empty($CONTENT['blog_posts'])): ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Blog",
  "name": "Блог 3D Print Pro",
  "url": "<?= $site['url'] ?>/blog.php",
  "publisher": {
    "@id": "<?= $site['url'] ?>/#organization"
  },
  "blogPost": [
<?php 
$posts = $CONTENT['blog_posts'];
$post_count = count($posts);
foreach ($posts as $index => $post): 
$is_last = ($index === $post_count - 1);
?>
    {
      "@type": "BlogPosting",
      "headline": "<?= htmlspecialchars($post['title'], ENT_QUOTES) ?>",
      "description": "<?= htmlspecialchars($post['description'], ENT_QUOTES) ?>",
      "url": "<?= $site['url'] ?>/blog/<?= $post['slug'] ?>",
      "datePublished": "<?= $post['publish_date'] ?>",
      "dateModified": "<?= $post['modified_date'] ?>",
      "author": {
        "@type": "Organization",
        "@id": "<?= $site['url'] ?>/#organization"
      },
      "publisher": {
        "@id": "<?= $site['url'] ?>/#organization"
      },
      "image": {
        "@type": "ImageObject",
        "url": "<?= $post['image'] ?>",
        "width": 800,
        "height": 600
      },
      "keywords": "<?= implode(', ', $post['tags']) ?>"
    }<?= $is_last ? '' : ',' ?>

<?php endforeach; ?>
  ]
}
</script>
<?php endif; ?>

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

<!-- Yandex.Metrika counter -->
<script type="text/javascript">
    (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
    })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=105404239', 'ym');

    ym(105404239, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", accurateTrackBounce:true, trackLinks:true});
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/105404239" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->

<!-- Styles -->
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/cta-components.css">
<link rel="stylesheet" href="css/contact-page.css">
<link rel="stylesheet" href="css/responsive.css">
<link rel="stylesheet" href="css/animations.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Favicon -->
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🖨️</text></svg>">
