<?php
// Set page identifiers for includes
$page_meta_key = 'services';
$canonical_url = 'services.php';
$active_page = 'services';

// Load content data
$CONTENT = require __DIR__ . '/data/content.php';
$site = $CONTENT['site'];
$services = $CONTENT['services'];
$technologies = $CONTENT['technologies'];
$materials = $CONTENT['materials'];
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
            <h1>Услуги 3D печати в <?= $site['city'] ?></h1>
            <p>Полный спектр услуг 3D печати: FDM, SLA, SLS технологии, 3D моделирование и постобработка</p>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services-section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Услуги</span>
                <h2 class="section-title">Наши услуги 3D печати</h2>
                <p class="section-description">
                    Полный спектр услуг от печати до постобработки
                </p>
            </div>
            <div class="services-grid">
                <?php foreach ($services as $service): ?>
                <article class="service-card" id="<?= $service['id'] ?>" data-icon="<?= $service['icon'] ?>">
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
                    
                    <p class="service-description"><?= htmlspecialchars($service['description']) ?></p>
                    
                    <?php if (!empty($service['features'])): ?>
                    <ul class="service-features">
                        <?php 
                        // Show first 4 features for compact layout
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
                    
                    <?php if (!empty($service['materials']) || !empty($service['software']) || !empty($service['formats'])): ?>
                    <div class="service-tags">
                        <?php 
                        // Combine and show first 5 tags
                        $tags = array_merge(
                            $service['materials'] ?? [],
                            $service['software'] ?? [],
                            $service['formats'] ?? []
                        );
                        $tagsSlice = array_slice($tags, 0, 5);
                        foreach ($tagsSlice as $tag): 
                        ?>
                        <span class="tag"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                        <?php if (count($tags) > 5): ?>
                        <span class="tag tag-more">+<?= count($tags) - 5 ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="service-cta-block">
                        <a href="index.php#order-form-section" class="btn-cta-primary">
                            <i class="fas fa-cube"></i>
                            Заказать 3D печать
                        </a>
                        <a href="<?= $site['telegram'] ?>" target="_blank" rel="noopener" class="btn-cta-secondary">
                            <i class="fab fa-telegram"></i>
                            Написать в Telegram
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Technologies Comparison Section -->
    <section class="technologies-comparison">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Сравнение</span>
                <h2 class="section-title">Сравнение технологий 3D печати</h2>
                <p class="section-description">
                    Выберите оптимальную технологию для вашего проекта
                </p>
            </div>
            <div class="comparison-grid">
                <?php foreach ($technologies as $key => $tech): ?>
                <div class="tech-card">
                    <h3><?= htmlspecialchars($tech['name']) ?></h3>
                    <p><?= htmlspecialchars($tech['description']) ?></p>
                    
                    <h4>Преимущества:</h4>
                    <ul>
                        <?php foreach ($tech['pros'] as $pro): ?>
                        <li><i class="fas fa-plus-circle icon-success"></i> <?= htmlspecialchars($pro) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <h4>Недостатки:</h4>
                    <ul>
                        <?php foreach ($tech['cons'] as $con): ?>
                        <li><i class="fas fa-minus-circle icon-danger"></i> <?= htmlspecialchars($con) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <h4>Применение:</h4>
                    <div class="tech-applications">
                        <?php foreach ($tech['applications'] as $app): ?>
                        <span class="tag"><?= htmlspecialchars($app) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Materials Section -->
    <section class="materials-section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Материалы</span>
                <h2 class="section-title">Материалы для 3D печати</h2>
                <p class="section-description">
                    Широкий выбор материалов для любых задач
                </p>
            </div>
            <div class="materials-grid">
                <?php foreach ($materials as $key => $material): ?>
                <div class="material-card">
                    <h3><?= htmlspecialchars($material['name']) ?></h3>
                    <div class="material-info">
                        <p><strong>Свойства:</strong> <?= htmlspecialchars($material['properties']) ?></p>
                        <p><strong>Температура печати:</strong> <?= htmlspecialchars($material['temperature']) ?></p>
                        <p><strong>Применение:</strong> <?= htmlspecialchars($material['applications']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
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
