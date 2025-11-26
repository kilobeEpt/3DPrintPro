<?php
// Set page identifiers for includes
$page_meta_key = 'portfolio';
$canonical_url = 'portfolio.php';
$active_page = 'portfolio';

// Load content data
$CONTENT = require __DIR__ . '/data/content.php';
$site = $CONTENT['site'];
$portfolio = $CONTENT['portfolio'];

// Get unique categories
$categories = array_unique(array_map(fn($item) => $item['category'], $portfolio));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body data-page="portfolio">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="page-hero">
        <div class="container">
            <div class="breadcrumbs">
                <a href="index.php">Главная</a>
                <span>/</span>
                <span>Портфолио</span>
            </div>
            <h1>Портфолио работ по 3D печати</h1>
            <p>Примеры наших проектов в различных отраслях. Более <?= $CONTENT['stats']['projects'] ?> выполненных заказов</p>
        </div>
    </section>

    <!-- Portfolio Section -->
    <section class="portfolio-full">
        <div class="container">
            <div class="portfolio-filters">
                <button class="filter-btn active" data-filter="all">Все работы</button>
                <?php foreach ($categories as $category): ?>
                <button class="filter-btn" data-filter="<?= strtolower($category) ?>"><?= htmlspecialchars($category) ?></button>
                <?php endforeach; ?>
            </div>

            <div class="portfolio-grid" id="portfolioGrid">
                <?php foreach ($portfolio as $item): ?>
                <div class="portfolio-item" data-category="<?= strtolower($item['category']) ?>">
                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="portfolio-image" loading="lazy">
                    <span class="portfolio-category"><?= htmlspecialchars($item['category']) ?></span>
                    <div class="portfolio-overlay">
                        <h3><?= htmlspecialchars($item['title']) ?></h3>
                        <p class="portfolio-tech">
                            <i class="fas fa-cog"></i>
                            <?= htmlspecialchars($item['technology']) ?>
                        </p>
                        <p class="portfolio-desc"><?= htmlspecialchars($item['description']) ?></p>
                        <div class="portfolio-meta">
                            <span>
                                <i class="fas fa-clock"></i>
                                <?= htmlspecialchars($item['completion_time']) ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-project-diagram"></i>
                    <div class="stat-number" data-target="<?= $CONTENT['stats']['projects'] ?>">0</div>
                    <p>Проектов выполнено</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="stat-number" data-target="<?= $CONTENT['stats']['clients'] ?>">0</div>
                    <p>Довольных клиентов</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <div class="stat-number" data-target="<?= $CONTENT['stats']['years'] ?>">0</div>
                    <p>Лет опыта</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-award"></i>
                    <div class="stat-number" data-target="<?= $CONTENT['stats']['awards'] ?>">0</div>
                    <p>Наград получено</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Хотите увидеть свой проект здесь?</h2>
                <p>Закажите 3D печать прямо сейчас и станьте частью нашего портфолио</p>
                <div class="cta-buttons">
                    <a href="index.php#order-form-section" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane"></i>
                        Заказать 3D печать
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
