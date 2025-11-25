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
    <section class="services-full">
        <div class="container">
            <div class="services-grid">
                <?php foreach ($services as $service): ?>
                <div class="service-card-full" id="<?= $service['id'] ?>">
                    <div class="service-header">
                        <div class="service-icon-large">
                            <i class="fas <?= $service['icon'] ?>"></i>
                        </div>
                        <div>
                            <h2><?= htmlspecialchars($service['name']) ?></h2>
                            <p class="service-price-tag"><?= htmlspecialchars($service['price']) ?></p>
                        </div>
                    </div>
                    <p class="service-description"><?= htmlspecialchars($service['description']) ?></p>
                    
                    <h3>Преимущества:</h3>
                    <ul class="service-features-list">
                        <?php foreach ($service['features'] as $feature): ?>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($feature) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if (isset($service['materials'])): ?>
                    <h3>Материалы:</h3>
                    <div class="service-tags">
                        <?php foreach ($service['materials'] as $material): ?>
                        <span class="tag"><?= htmlspecialchars($material) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($service['software'])): ?>
                    <h3>ПО для моделирования:</h3>
                    <div class="service-tags">
                        <?php foreach ($service['software'] as $soft): ?>
                        <span class="tag"><?= htmlspecialchars($soft) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($service['formats'])): ?>
                    <h3>Поддерживаемые форматы:</h3>
                    <div class="service-tags">
                        <?php foreach ($service['formats'] as $format): ?>
                        <span class="tag"><?= htmlspecialchars($format) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($service['techniques'])): ?>
                    <h3>Методы обработки:</h3>
                    <ul class="service-features-list">
                        <?php foreach ($service['techniques'] as $technique): ?>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($technique) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <?php if (isset($service['options'])): ?>
                    <h3>Доступные варианты:</h3>
                    <ul class="service-features-list">
                        <?php foreach ($service['options'] as $option): ?>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($option) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <?php if (isset($service['max_size']) || isset($service['layer_height'])): ?>
                    <h3>Технические характеристики:</h3>
                    <ul class="service-specs">
                        <?php if (isset($service['max_size'])): ?>
                        <li><strong>Макс. размер:</strong> <?= htmlspecialchars($service['max_size']) ?></li>
                        <?php endif; ?>
                        <?php if (isset($service['layer_height'])): ?>
                        <li><strong>Высота слоя:</strong> <?= htmlspecialchars($service['layer_height']) ?></li>
                        <?php endif; ?>
                    </ul>
                    <?php endif; ?>

                    <div class="service-actions">
                        <a href="index.php#calculator" class="btn btn-primary">
                            <i class="fas fa-calculator"></i>
                            Рассчитать стоимость
                        </a>
                        <a href="<?= $site['telegram'] ?>" target="_blank" class="btn btn-outline">
                            <i class="fab fa-telegram"></i>
                            Написать в Telegram
                        </a>
                    </div>
                </div>
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
                        <li><i class="fas fa-plus-circle" style="color: var(--success);"></i> <?= htmlspecialchars($pro) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <h4>Недостатки:</h4>
                    <ul>
                        <?php foreach ($tech['cons'] as $con): ?>
                        <li><i class="fas fa-minus-circle" style="color: var(--danger);"></i> <?= htmlspecialchars($con) ?></li>
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
                    <a href="index.php#calculator" class="btn btn-primary btn-lg">
                        <i class="fas fa-calculator"></i>
                        Рассчитать стоимость
                    </a>
                    <a href="contact.php" class="btn btn-outline btn-lg">
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
