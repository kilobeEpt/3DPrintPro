<?php
// Set page identifiers for includes
$page_meta_key = 'contact';
$canonical_url = 'contact.php';
$active_page = 'contact';

// Load content data
$CONTENT = require __DIR__ . '/data/content.php';
$site = $CONTENT['site'];
$faq = $CONTENT['faq'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body data-page="contact">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="page-hero">
        <div class="container">
            <div class="breadcrumbs">
                <a href="index.php">Главная</a>
                <span>/</span>
                <span>Контакты</span>
            </div>
            <h1>Контакты <?= $site['name'] ?> — FDM, SLA, SLS печать в <?= $site['city'] ?></h1>
            <p>Профессиональная послойная 3D печать в Омске с доставкой по всем округам города. Звоните, пишите или приезжайте в нашу мастерскую</p>
        </div>
    </section>

    <!-- Two-Column Contact Layout -->
    <section class="contact-main-section">
        <div class="container">
            <div class="contact-layout">
                <!-- Left Column: Order Form -->
                <div class="contact-form-column">
                    <?php
                    $form_heading = 'Отправьте сообщение';
                    $form_description = 'Заполните форму, и мы свяжемся с вами в течение 15 минут';
                    $form_label = 'Напишите нам';
                    $section_id = 'contact-form-section';
                    $form_id = 'contactForm';
                    $preselect_service = 'Консультация';
                    $cta_source = 'contact';
                    $show_info = false;
                    include __DIR__ . '/includes/order-form.php';
                    ?>
                </div>

                <!-- Right Column: Contact Info -->
                <div class="contact-info-column">
                    <div class="contact-panel" itemscope itemtype="https://schema.org/LocalBusiness">
                        <!-- Contact Details Cards -->
                        <div class="contact-details">
                            <h2 class="contact-panel-title">Контактная информация</h2>
                            
                            <meta itemprop="name" content="<?= $site['name'] ?>">
                            <meta itemprop="description" content="<?= $site['business_blurb'] ?>">
                            
                            <!-- Address -->
                            <div class="contact-info-item" itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                                <div class="contact-info-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="contact-info-content">
                                    <h3>Адрес</h3>
                                    <p itemprop="streetAddress"><?= $site['address'] ?></p>
                                    <span class="text-muted">
                                        <span itemprop="addressLocality"><?= $site['city'] ?></span>, 
                                        <span itemprop="postalCode"><?= $site['postal_code'] ?></span>
                                    </span>
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="contact-info-item">
                                <div class="contact-info-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="contact-info-content">
                                    <h3>Телефон</h3>
                                    <p><a href="tel:<?= str_replace([' ', '(', ')', '-'], '', $site['phone']) ?>" itemprop="telephone"><?= $site['phone'] ?></a></p>
                                    <span class="text-muted">Звоните с 9:00 до 18:00</span>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="contact-info-item">
                                <div class="contact-info-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-info-content">
                                    <h3>Email</h3>
                                    <p><a href="mailto:<?= $site['email'] ?>" itemprop="email"><?= $site['email'] ?></a></p>
                                    <span class="text-muted">Ответим в течение 2 часов</span>
                                </div>
                            </div>

                            <!-- Telegram -->
                            <div class="contact-info-item">
                                <div class="contact-info-icon">
                                    <i class="fab fa-telegram"></i>
                                </div>
                                <div class="contact-info-content">
                                    <h3>Telegram</h3>
                                    <p><a href="<?= $site['telegram'] ?>" target="_blank" rel="noopener">@PrintPro_Omsk</a></p>
                                    <span class="text-muted">Быстрая связь 24/7</span>
                                </div>
                            </div>

                            <!-- Working Hours -->
                            <div class="contact-info-item" itemprop="openingHoursSpecification" itemscope itemtype="https://schema.org/OpeningHoursSpecification">
                                <div class="contact-info-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="contact-info-content">
                                    <h3>Режим работы</h3>
                                    <p><?= $site['working_hours']['weekdays'] ?></p>
                                    <p><?= $site['working_hours']['weekend'] ?></p>
                                    <span class="text-muted">Заказы онлайн круглосуточно</span>
                                    <meta itemprop="dayOfWeek" content="Monday,Tuesday,Wednesday,Thursday,Friday">
                                    <meta itemprop="opens" content="09:00">
                                    <meta itemprop="closes" content="18:00">
                                </div>
                            </div>
                            
                            <!-- Geo Coordinates -->
                            <div itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates" style="display: none;">
                                <meta itemprop="latitude" content="<?= $site['geo']['latitude'] ?>">
                                <meta itemprop="longitude" content="<?= $site['geo']['longitude'] ?>">
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="contact-actions">
                            <h3 class="contact-actions-title">Быстрые действия</h3>
                            <div class="contact-actions-buttons">
                                <a href="tel:<?= str_replace([' ', '(', ')', '-'], '', $site['phone']) ?>" 
                                   class="btn-cta-secondary btn-sm contact-action-btn"
                                   aria-label="Позвонить нам">
                                    <i class="fas fa-phone"></i>
                                    <span>Позвонить</span>
                                </a>
                                <a href="mailto:<?= $site['email'] ?>" 
                                   class="btn-cta-secondary btn-sm contact-action-btn"
                                   aria-label="Написать на email">
                                    <i class="fas fa-envelope"></i>
                                    <span>Email</span>
                                </a>
                                <a href="<?= $site['telegram'] ?>" 
                                   class="btn-cta-secondary btn-sm contact-action-btn"
                                   target="_blank" 
                                   rel="noopener"
                                   aria-label="Написать в Telegram">
                                    <i class="fab fa-telegram"></i>
                                    <span>Telegram</span>
                                </a>
                                <a href="<?= $site['whatsapp'] ?>" 
                                   class="btn-cta-secondary btn-sm contact-action-btn"
                                   target="_blank" 
                                   rel="noopener"
                                   aria-label="Написать в WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                    <span>WhatsApp</span>
                                </a>
                            </div>
                        </div>

                        <!-- Business Listings -->
                        <div class="contact-business-listings">
                            <h3 class="contact-actions-title">Найдите нас на картах</h3>
                            <p class="text-muted" style="margin-bottom: var(--space-16); font-size: 0.875rem;">
                                Оставьте отзыв о нашей 3D печати в Омске
                            </p>
                            <div class="contact-actions-buttons">
                                <a href="<?= $site['gmb_url'] ?>" 
                                   class="btn-cta-secondary btn-sm contact-action-btn"
                                   target="_blank"
                                   rel="noopener"
                                   aria-label="Наша страница на Google Maps">
                                    <i class="fab fa-google"></i>
                                    <span>Google Maps</span>
                                </a>
                                <a href="<?= $site['yandex_maps_url'] ?>" 
                                   class="btn-cta-secondary btn-sm contact-action-btn"
                                   target="_blank"
                                   rel="noopener"
                                   aria-label="Наша страница на Яндекс Картах">
                                    <i class="fas fa-map-marked-alt"></i>
                                    <span>Яндекс Карты</span>
                                </a>
                            </div>
                        </div>

                        <!-- Social Links -->
                        <div class="contact-social">
                            <h3 class="contact-social-title">Мы в соцсетях</h3>
                            <div class="contact-social-list">
                                <?php foreach ($site['social_links'] as $social): ?>
                                <a href="<?= $social['url'] ?>" 
                                   class="contact-social-link"
                                   target="_blank" 
                                   rel="noopener"
                                   aria-label="<?= $social['name'] ?>">
                                    <i class="<?= $social['icon'] ?>"></i>
                                    <span><?= $social['name'] ?></span>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
            <h2>Как нас найти</h2>
            <div class="map-embed-container">
                <?php if (!empty($site['map_url'])): ?>
                <iframe 
                    class="map-embed"
                    src="<?= $site['map_url'] ?>"
                    loading="lazy"
                    title="Карта с расположением <?= $site['name'] ?>"
                    allowfullscreen
                    aria-label="Интерактивная карта">
                </iframe>
                <?php else: ?>
                <div class="map-fallback">
                    <i class="fas fa-map-marked-alt"></i>
                    <p>Карта недоступна</p>
                    <p class="coord-text">
                        Координаты: <?= $site['geo']['latitude'] ?>, <?= $site['geo']['longitude'] ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
            <div class="map-directions">
                <h3>Как добраться:</h3>
                <ul>
                    <li><i class="fas fa-subway"></i> От метро "Библиотека им. Пушкина" — 5 минут пешком</li>
                    <li><i class="fas fa-bus"></i> Автобусы: 12, 15, 23, 45 — остановка "ул. Ленина"</li>
                    <li><i class="fas fa-car"></i> Парковка для клиентов доступна во дворе здания</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Service Coverage Section -->
    <section class="content-section" style="background: var(--bg-secondary);">
        <div class="container">
            <div class="content-wrapper">
                <h2>3D печать в Омске с доставкой по всем округам</h2>
                <p>
                    <strong>3D Print Pro</strong> обслуживает клиентов из всех районов Омска: Центральный, Советский, 
                    Кировский, Ленинский и Октябрьский округа. Предлагаем полный спектр услуг послойной 3D печати 
                    по технологиям FDM, SLA, SLS, а также 3D моделирование, 3D сканирование и постобработку изделий.
                </p>
                <p>
                    <strong>Быстрая доставка готовых изделий:</strong> от 30 минут в Центральном округе до 3 часов 
                    в отдаленные районы. Бесплатная курьерская доставка при заказе от 3000₽. Для клиентов из 
                    Омской области — доставка через Почту России или транспортные компании СДЭК, ПЭК.
                </p>
                <p>
                    Наша мастерская находится в центре Омска по адресу <strong>ул. Ленина, д. 15</strong>. 
                    Удобный самовывоз в рабочее время, парковка для клиентов. <a href="districts.php">Подробнее о доставке по районам →</a>
                </p>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq" id="faq">
        <div class="container">
            <div class="section-header">
                <span class="section-label">FAQ</span>
                <h2 class="section-title">Часто задаваемые вопросы</h2>
                <p class="section-description">
                    Ответы на популярные вопросы о 3D печати в Омске
                </p>
            </div>
            <div class="faq-container">
                <?php foreach (array_slice($faq, 0, 6) as $index => $item): ?>
                <div class="faq-item <?= $index === 0 ? 'active' : '' ?>">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <h3><?= htmlspecialchars($item['question']) ?></h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer" <?= $index === 0 ? 'style="display: block;"' : '' ?>>
                        <p><?= htmlspecialchars($item['answer']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Contact Page Specific JSON-LD Schema -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "LocalBusiness",
      "@id": "<?= $site['url'] ?>/#localbusiness-contact",
      "name": "<?= $site['name'] ?>",
      "description": "<?= htmlspecialchars($site['business_blurb'], ENT_QUOTES) ?>",
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
      "hasMap": "<?= $site['yandex_maps_url'] ?>",
      "openingHoursSpecification": [
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
          "opens": "09:00",
          "closes": "18:00"
        }
      ],
      "priceRange": "₽₽",
      "areaServed": [
        <?php foreach ($site['service_areas'] as $index => $area): ?>
        {
          "@type": "City",
          "name": "<?= htmlspecialchars($area['name'], ENT_QUOTES) ?>, Омск"
        }<?= $index < count($site['service_areas']) - 1 ? ',' : '' ?>
        
        <?php endforeach; ?>
      ],
      "makesOffer": [
        <?php 
        $services = $CONTENT['services'];
        $service_count = count($services);
        foreach ($services as $index => $service): 
        ?>
        {
          "@type": "Offer",
          "itemOffered": {
            "@type": "Service",
            "name": "<?= htmlspecialchars($service['name'], ENT_QUOTES) ?> в Омске",
            "description": "<?= htmlspecialchars($service['short_description'], ENT_QUOTES) ?>",
            "serviceType": "<?= htmlspecialchars($service['name'], ENT_QUOTES) ?>"
          },
          "areaServed": {
            "@type": "City",
            "name": "Омск"
          }
        }<?= $index < $service_count - 1 ? ',' : '' ?>
        
        <?php endforeach; ?>
      ],
      "sameAs": [
        <?php 
        $all_profiles = array_merge(
            array_column($site['social_links'], 'url'),
            [$site['telegram'], $site['whatsapp'], $site['gmb_url'], $site['yandex_maps_url']]
        );
        $all_profiles = array_unique(array_filter($all_profiles));
        echo '"' . implode("\",\n        \"", $all_profiles) . '"';
        ?>
      ]
    }
    </script>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
