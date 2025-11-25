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

<!-- JSON-LD Structured Data: Service with Enhanced OfferCatalog -->
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
    "name": "Услуги 3D печати в Омске",
    "itemListElement": [
      {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Service",
          "name": "FDM 3D печать в Омске",
          "description": "Профессиональная FDM печать методом послойного наплавления пластика из 15+ материалов (PLA, ABS, PETG, Nylon, TPU). Точность ±0.2-0.5 мм, размер до 300×300×400 мм. Для прототипов, функциональных деталей, корпусов.",
          "priceRange": "от 150₽/час"
        }
      },
      {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Service",
          "name": "SLA 3D печать в Омске",
          "description": "Высокоточная SLA печать фотополимерной смолой. Точность ±0.05-0.1 мм, высота слоя 0.025-0.1 мм, разрешение XY 47 микрон. Идеально для ювелирных восковок, стоматологии, медицинских моделей, миниатюр.",
          "priceRange": "от 300₽/час"
        }
      },
      {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Service",
          "name": "SLS 3D печать в Омске",
          "description": "Промышленная SLS печать лазерным спеканием порошкового нейлона PA12. Прочность 48 МПа, термостойкость +170°C, без поддержек. Для функциональных прототипов, малых серий, механических узлов.",
          "priceRange": "от 500₽/час"
        }
      },
      {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Service",
          "name": "3D моделирование в Омске",
          "description": "Создание 3D моделей в CAD (SolidWorks, Fusion 360) и полигональных редакторах (Blender, ZBrush). По чертежам, эскизам, фотографиям. Реверс-инжиниринг с 3D сканирования. Оптимизация для печати.",
          "priceRange": "от 500₽/час"
        }
      },
      {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Service",
          "name": "Постобработка 3D изделий в Омске",
          "description": "Финишная обработка 3D печатных деталей: механическая шлифовка абразивами P100-P2000, химическая полировка ацетоном, покраска акрилом/эмалью, нанесение логотипов, термообработка, склейка модулей.",
          "priceRange": "от 200₽/час"
        }
      },
      {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Service",
          "name": "Цветная 3D печать в Омске",
          "description": "Многоцветная FDM печать (до 4 цветов) или профессиональная покраска готовых моделей по RAL/Pantone. Палитра 50+ оттенков. Аэрография, металлизация, лакирование.",
          "priceRange": "от 200₽/час"
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
      "item": "<?= $site['url'] ?>/about.html"
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

<?php if ($page_meta_key === 'home'): ?>
<!-- JSON-LD Structured Data: FAQPage -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Сколько стоит 3D печать в Омске и от чего зависит цена?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Стоимость 3D печати в Омске зависит от 5 факторов: (1) Технология — FDM от 150 ₽/час, SLA от 300 ₽/час, SLS от 500 ₽/час; (2) Материал — PLA дешевле (5 ₽/г), Nylon и фотополимеры дороже (15-30 ₽/г); (3) Размер модели и время печати (от 1 до 48 часов); (4) Сложность — нависающие элементы требуют поддержек; (5) Постобработка (шлифовка, покраска +200-500 ₽). Средняя деталь 50×50×50 мм из PLA стоит 300-500 ₽, из SLA смолы — 800-1200 ₽."
      }
    },
    {
      "@type": "Question",
      "name": "Какие материалы используются для 3D печати?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Используем 15+ материалов для разных задач. FDM пластики: PLA (биоразлагаемый, до +60°C), ABS (термостойкий до +100°C), PETG (химически стойкий, ударопрочный), TPU (гибкий), Nylon PA12 (износостойкий). Фотополимеры SLA: Standard (универсальный), Tough (ударопрочный), Flexible (гибкий), Castable (литьевой для ювелирки), Dental (биосовместимый для медицины)."
      }
    },
    {
      "@type": "Question",
      "name": "Как долго изготавливается заказ?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Сроки 3D печати в Омске: малые детали до 50 мм — готовность за 1-2 дня; средние детали 50-150 мм — готовность за 2-4 дня; крупные детали 150-300 мм — готовность за 4-7 дней. Срочное изготовление за 24 часа возможно для мелких деталей (+50% к стоимости). На время влияет технология, высота модели, качество печати."
      }
    },
    {
      "@type": "Question",
      "name": "Как подготовить 3D модель к печати?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Для 3D печати нужны файлы STL, OBJ, 3MF, STEP. Требования: замкнутая геометрия (Watertight), правильные нормали, минимальная толщина стенок — FDM от 0.8 мм, SLA от 0.4 мм, отсутствие пересекающихся граней. Если модели нет — создадим по чертежам (от 500 ₽/час) или выполним 3D сканирование (от 1000 ₽). Бесплатная проверка файлов."
      }
    },
    {
      "@type": "Question",
      "name": "Какая точность 3D печати?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Точность зависит от технологии: FDM — точность ±0.2-0.5 мм, высота слоя 0.1-0.4 мм; SLA — точность ±0.05-0.1 мм, высота слоя 0.025-0.1 мм, разрешение XY 47 микрон; SLS — точность ±0.1-0.3 мм, высота слоя 0.1-0.15 мм. Для ответственных деталей с посадками рекомендуем SLA."
      }
    },
    {
      "@type": "Question",
      "name": "Предоставляете ли гарантию на напечатанные изделия?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Да, предоставляем гарантию качества: 100% возврат средств при отклонении размеров, бесплатная перепечать при браке печати (расслоение, непропечатка, деформация), гарантия 6 месяцев на детали из Nylon PA12 и PETG при соблюдении условий эксплуатации, бесплатная перепечать при обнаружении дефектов в течение 14 дней."
      }
    },
    {
      "@type": "Question",
      "name": "Доставка и самовывоз в Омске — как получить заказ?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Способы получения: самовывоз из офиса по адресу ул. Ленина, 15 (бесплатно, Пн-Пт 09:00-18:00); доставка курьером по Омску — 300 ₽; доставка Яндекс.Доставка/СДЭК; Почта России. Для постоянных клиентов — бесплатная доставка по Омску при заказе от 5000 ₽."
      }
    },
    {
      "@type": "Question",
      "name": "Печатаете ли мелкими сериями?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Да, специализируемся на малосерийном производстве от 10 до 1000 деталей. Система скидок: 10-50 шт — 10%; 51-200 шт — 15%; 201-500 шт — 20%; 501-1000 шт — 25% + бесплатная доставка. Серийная печать на SLS оптимальна для партий 50+ шт. Сроки: 100 деталей — 7-14 дней, 500 деталей — 3-4 недели."
      }
    }
  ]
}
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
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/responsive.css">
<link rel="stylesheet" href="css/animations.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Favicon -->
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🖨️</text></svg>">
