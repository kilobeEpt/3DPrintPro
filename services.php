<?php
/**
 * Services Page
 */

// Load content data
$content = require __DIR__ . '/data/content.php';

// Page metadata
$page_title = 'Услуги 3D печати в Омске — FDM, SLA, SLS, моделирование | ' . $content['site']['name'];
$page_description = 'Полный спектр услуг 3D печати в Омске: FDM, SLA, SLS технологии, 3D моделирование, постобработка, покраска. 15+ материалов. Печать от 1 часа. Звоните: ' . $content['contact']['phone'];
$page_keywords = 'услуги 3D печати Омск, FDM печать, SLA печать, SLS печать, 3D моделирование Омск, постобработка 3D, покраска изделий';
$canonical_url = $content['site']['url'] . '/services.php';
$active_page = 'services';
$body_data_page = 'services';

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Главная', 'url' => $content['site']['url'] . '/'],
    ['name' => 'Услуги 3D печати', 'url' => $canonical_url],
];

// Structured data for services
$structured_data = [
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    'serviceType' => '3D печать и 3D моделирование',
    'provider' => [
        '@type' => 'LocalBusiness',
        'name' => $content['site']['name'],
        'telephone' => $content['contact']['phone'],
        'email' => $content['contact']['email'],
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $content['contact']['address']['street'],
            'addressLocality' => $content['contact']['address']['city'],
            'addressRegion' => $content['contact']['address']['region'],
            'postalCode' => $content['contact']['address']['postal_code'],
            'addressCountry' => $content['contact']['address']['country'],
        ],
    ],
    'areaServed' => [
        '@type' => 'City',
        'name' => $content['contact']['address']['city'],
    ],
    'hasOfferCatalog' => [
        '@type' => 'OfferCatalog',
        'name' => 'Услуги 3D печати',
        'itemListElement' => array_map(function($service) {
            return [
                '@type' => 'Offer',
                'itemOffered' => [
                    '@type' => 'Service',
                    'name' => $service['name'] . ' в Омске',
                    'description' => $service['short_description'],
                ],
            ];
        }, $content['services']),
    ],
];

// Include head
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

    <!-- Page Hero -->
    <section class="page-hero">
        <div class="container">
            <h1 class="page-title">Услуги 3D печати в Омске</h1>
            <p class="page-description">Полный спектр услуг 3D печати: FDM, SLA, SLS технологии, 3D моделирование, постобработка</p>
        </div>
    </section>

    <!-- Services Detailed Section -->
    <section class="services-detailed">
        <div class="container">
            <?php foreach ($content['services'] as $service): ?>
            <div class="service-detailed" id="<?= htmlspecialchars($service['slug']) ?>">
                <div class="service-detailed-header">
                    <div class="service-icon-large">
                        <i class="<?= htmlspecialchars($service['icon']) ?>"></i>
                    </div>
                    <div class="service-header-content">
                        <h2><?= htmlspecialchars($service['name']) ?></h2>
                        <p class="service-tagline"><?= htmlspecialchars($service['short_description']) ?></p>
                        <div class="service-meta">
                            <span class="meta-item">
                                <i class="fas fa-ruble-sign"></i>
                                <strong>от <?= $service['price_from'] ?>₽</strong> <?= htmlspecialchars($service['price_unit']) ?>
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-clock"></i>
                                <strong><?= htmlspecialchars($service['delivery_time']) ?></strong>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="service-detailed-body">
                    <div class="service-description">
                        <h3>Описание</h3>
                        <p><?= htmlspecialchars($service['description']) ?></p>
                    </div>

                    <div class="service-grid-info">
                        <div class="service-info-card">
                            <h4><i class="fas fa-check-circle"></i> Преимущества</h4>
                            <ul>
                                <?php foreach ($service['features'] as $feature): ?>
                                <li><?= htmlspecialchars($feature) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <?php if ($service['materials']): ?>
                        <div class="service-info-card">
                            <h4><i class="fas fa-layer-group"></i> Материалы</h4>
                            <ul>
                                <?php foreach ($service['materials'] as $material): ?>
                                <li><?= htmlspecialchars($material) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <div class="service-info-card">
                            <h4><i class="fas fa-tasks"></i> Применение</h4>
                            <ul>
                                <?php foreach ($service['applications'] as $application): ?>
                                <li><?= htmlspecialchars($application) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="service-actions">
                        <a href="/index.php#calculator" class="btn btn-primary">
                            <i class="fas fa-calculator"></i>
                            Рассчитать стоимость
                        </a>
                        <a href="/contact.php" class="btn btn-outline">
                            <i class="fas fa-envelope"></i>
                            Связаться с нами
                        </a>
                        <a href="<?= htmlspecialchars($content['contact']['telegram']) ?>" target="_blank" class="btn btn-outline">
                            <i class="fab fa-telegram"></i>
                            Telegram
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Готовы начать свой проект?</h2>
                <p>Свяжитесь с нами для консультации и расчета стоимости</p>
                <div class="cta-buttons">
                    <a href="/index.php#calculator" class="btn btn-primary btn-lg">
                        <i class="fas fa-calculator"></i>
                        Рассчитать стоимость
                    </a>
                    <a href="/contact.php" class="btn btn-outline btn-lg">
                        <i class="fas fa-phone"></i>
                        Связаться
                    </a>
                </div>
            </div>
        </div>
    </section>

<?php require __DIR__ . '/includes/footer.php'; ?>
