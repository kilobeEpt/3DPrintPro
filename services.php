<?php
// Set page identifiers for includes
$page_meta_key = 'services';
$canonical_url = 'services.php';
$active_page = 'services';

// Load content data
$CONTENT = require __DIR__ . '/data/content.php';
$site = $CONTENT['site'];
$services = $CONTENT['services'];
$portfolio = $CONTENT['portfolio'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body data-page="services">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="page-hero">
        <div class="container">
            <div class="breadcrumbs">
                <a href="index.php">Главная</a>
                <span>/</span>
                <span>Услуги</span>
            </div>
            <h1>Полный спектр услуг послойной 3D печати в <?= $site['city'] ?></h1>
            <p>FDM, SLA, SLS технологии, 3D моделирование, 3D сканирование и постобработка — всё для воплощения ваших идей</p>
        </div>
    </section>

    <!-- Services Quick Overview Grid -->
    <section class="services-overview">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Обзор услуг</span>
                <h2 class="section-title">Наши услуги 3D печати</h2>
                <p class="section-description">
                    Полный спектр услуг послойной 3D печати: FDM, SLA, SLS + сопутствующие сервисы
                </p>
            </div>
            <div class="services-grid">
                <?php foreach ($services as $service): ?>
                <a href="#<?= $service['slug'] ?>" class="service-card<?= isset($service['featured']) && $service['featured'] ? ' featured' : '' ?>" 
                   data-service-type="<?= htmlspecialchars($service['id']) ?>" 
                   data-price-range="<?= htmlspecialchars($service['price_range'] ?? $service['price']) ?>">
                    <?php if (isset($service['featured']) && $service['featured']): ?>
                    <span class="featured-badge">Популярно</span>
                    <?php endif; ?>
                    
                    <div class="service-icon">
                        <i class="fas <?= $service['icon'] ?>" aria-hidden="true"></i>
                        <span class="sr-only"><?= htmlspecialchars($service['name']) ?></span>
                    </div>
                    
                    <h3><?= htmlspecialchars($service['name']) ?></h3>
                    
                    <?php if (!empty($service['price'])): ?>
                    <div class="service-price"><?= htmlspecialchars($service['price']) ?></div>
                    <?php endif; ?>
                    
                    <p class="service-description"><?= htmlspecialchars($service['short_description']) ?></p>
                    
                    <?php if (!empty($service['features'])): ?>
                    <ul class="service-features">
                        <?php 
                        $featuresSlice = array_slice($service['features'], 0, 4);
                        foreach ($featuresSlice as $feature): 
                        ?>
                        <li>
                            <i class="fas fa-check-circle icon-success" aria-hidden="true"></i>
                            <span><?= htmlspecialchars($feature) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Detailed Service Sections -->
    <?php foreach ($services as $service): ?>
    <section id="<?= $service['slug'] ?>" class="service-detail" data-service-id="<?= $service['id'] ?>">
        <div class="container">
            <div class="service-detail-header">
                <div class="service-detail-icon">
                    <i class="fas <?= $service['icon'] ?>" aria-hidden="true"></i>
                </div>
                <div class="service-detail-title">
                    <h2><?= htmlspecialchars($service['name']) ?></h2>
                    <p class="service-detail-price"><?= htmlspecialchars($service['price']) ?></p>
                </div>
            </div>

            <div class="service-detail-content">
                <!-- Description -->
                <div class="service-detail-description">
                    <h3>Описание технологии</h3>
                    <p><?= nl2br(htmlspecialchars($service['description'])) ?></p>
                </div>

                <!-- Benefits -->
                <?php if (!empty($service['benefits'])): ?>
                <div class="service-detail-benefits">
                    <h3>Преимущества</h3>
                    <ul class="benefits-list">
                        <?php foreach ($service['benefits'] as $benefit): ?>
                        <li>
                            <i class="fas fa-check-circle icon-success" aria-hidden="true"></i>
                            <span><?= htmlspecialchars($benefit) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Materials/Specs Table -->
                <?php if (!empty($service['materials']) && is_array($service['materials']) && isset($service['materials'][0]['name'])): ?>
                <div class="service-detail-materials">
                    <h3>Материалы</h3>
                    <div class="materials-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Материал</th>
                                    <th>Свойства</th>
                                    <th>Температура</th>
                                    <th>Применение</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($service['materials'] as $material): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($material['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($material['properties']) ?></td>
                                    <td><?= htmlspecialchars($material['temp']) ?></td>
                                    <td><?= htmlspecialchars($material['applications']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Technical Specs -->
                <?php if (!empty($service['specs'])): ?>
                <div class="service-detail-specs">
                    <h3>Технические характеристики</h3>
                    <div class="specs-grid">
                        <?php foreach ($service['specs'] as $specName => $specValue): ?>
                        <div class="spec-item">
                            <span class="spec-label"><?= htmlspecialchars($specName) ?></span>
                            <span class="spec-value"><?= htmlspecialchars($specValue) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Turnaround Time -->
                <?php if (!empty($service['turnaround'])): ?>
                <div class="service-detail-turnaround">
                    <h3>Сроки изготовления</h3>
                    <p><?= nl2br(htmlspecialchars($service['turnaround'])) ?></p>
                </div>
                <?php endif; ?>

                <!-- Pricing Notes -->
                <?php if (!empty($service['pricing_notes'])): ?>
                <div class="service-detail-pricing">
                    <h3>Стоимость и формирование цены</h3>
                    <p><?= nl2br(htmlspecialchars($service['pricing_notes'])) ?></p>
                </div>
                <?php endif; ?>

                <!-- Sample Projects -->
                <?php if (!empty($service['sample_projects'])): ?>
                <div class="service-detail-examples">
                    <h3>Примеры работ</h3>
                    <div class="examples-grid">
                        <?php 
                        foreach ($service['sample_projects'] as $projectId):
                            $project = array_values(array_filter($portfolio, fn($p) => $p['id'] === $projectId))[0] ?? null;
                            if ($project):
                        ?>
                        <div class="example-card">
                            <img src="<?= htmlspecialchars($project['image']) ?>" alt="<?= htmlspecialchars($project['title']) ?>" loading="lazy">
                            <div class="example-info">
                                <h4><?= htmlspecialchars($project['title']) ?></h4>
                                <p class="example-tech"><?= htmlspecialchars($project['technology']) ?></p>
                                <p class="example-category"><?= htmlspecialchars($project['category']) ?></p>
                            </div>
                        </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Service-specific FAQ -->
                <?php if (!empty($service['service_faq'])): ?>
                <div class="service-detail-faq">
                    <h3>Часто задаваемые вопросы</h3>
                    <div class="faq-accordion">
                        <?php foreach ($service['service_faq'] as $index => $faqItem): ?>
                        <div class="faq-item">
                            <button class="faq-question" aria-expanded="false">
                                <span><?= htmlspecialchars($faqItem['q']) ?></span>
                                <i class="fas fa-chevron-down" aria-hidden="true"></i>
                            </button>
                            <div class="faq-answer">
                                <p><?= nl2br(htmlspecialchars($faqItem['a'])) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- CTA Buttons -->
                <div class="service-detail-cta">
                    <a href="index.php#order-form-section" class="btn-cta-primary btn-lg">
                        <i class="fas fa-cube"></i>
                        Заказать <?= htmlspecialchars($service['name']) ?>
                    </a>
                    <a href="contact.php" class="btn-cta-secondary btn-lg">
                        <i class="fas fa-comments"></i>
                        Получить консультацию
                    </a>
                </div>
            </div>
        </div>
    </section>
    <?php endforeach; ?>

    <!-- Final CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Готовы начать свой проект?</h2>
                <p>Свяжитесь с нами для бесплатной консультации и расчета стоимости</p>
                <div class="cta-buttons">
                    <a href="index.php#order-form-section" class="btn-cta-primary btn-lg">
                        <i class="fas fa-cube"></i>
                        Заказать 3D печать
                    </a>
                    <a href="contact.php" class="btn-cta-secondary btn-lg">
                        <i class="fas fa-phone"></i>
                        Связаться с нами
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
