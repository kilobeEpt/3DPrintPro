<?php
/**
 * Portfolio Page
 */

// Load content data
$content = require __DIR__ . '/data/content.php';

// Page metadata
$page_title = 'Портфолио работ 3D печати в Омске | ' . $content['site']['name'];
$page_description = 'Портфолио выполненных проектов 3D печати в Омске: архитектурные макеты, прототипы, ювелирные изделия, промышленные детали. Примеры наших работ с описанием технологий.';
$page_keywords = 'портфолио 3D печать Омск, работы 3D печати, примеры 3D печати, проекты 3D печать, архитектурные макеты Омск, прототипы 3D печать';
$canonical_url = $content['site']['url'] . '/portfolio.php';
$active_page = 'portfolio';
$body_data_page = 'portfolio';

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Главная', 'url' => $content['site']['url'] . '/'],
    ['name' => 'Портфолио работ', 'url' => $canonical_url],
];

// Include head
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

    <!-- Page Hero -->
    <section class="page-hero">
        <div class="container">
            <h1 class="page-title">Портфолио работ</h1>
            <p class="page-description">Примеры реализованных проектов 3D печати в различных технологиях</p>
        </div>
    </section>

    <!-- Portfolio Filter -->
    <section class="portfolio-section">
        <div class="container">
            <div class="portfolio-filters">
                <button class="filter-btn active" data-filter="all">Все проекты</button>
                <button class="filter-btn" data-filter="architecture">Архитектура</button>
                <button class="filter-btn" data-filter="medical">Медицина</button>
                <button class="filter-btn" data-filter="jewelry">Ювелирка</button>
                <button class="filter-btn" data-filter="electronics">Электроника</button>
                <button class="filter-btn" data-filter="figurines">Миниатюры</button>
                <button class="filter-btn" data-filter="industrial">Промышленность</button>
            </div>

            <div class="portfolio-grid-full">
                <?php foreach ($content['portfolio'] as $item): ?>
                <div class="portfolio-card-full" data-category="<?= htmlspecialchars($item['category']) ?>">
                    <div class="portfolio-image">
                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22600%22 height=%22400%22%3E%3Crect fill=%22%23e0e0e0%22 width=%22600%22 height=%22400%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2218%22 text-anchor=%22middle%22 fill=%22%23999%22%3E<?= htmlspecialchars($item['title']) ?>%3C/text%3E%3C/svg%3E'">
                        <div class="portfolio-overlay">
                            <button class="btn btn-primary" onclick="showPortfolioDetails(<?= $item['id'] ?>)">
                                <span>Подробнее</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="portfolio-info">
                        <h3><?= htmlspecialchars($item['title']) ?></h3>
                        <p class="portfolio-meta">
                            <span><i class="fas fa-print"></i> <?= htmlspecialchars($item['technology']) ?></span>
                            <span><i class="fas fa-clock"></i> <?= htmlspecialchars($item['duration']) ?></span>
                        </p>
                        <p class="portfolio-excerpt"><?= htmlspecialchars(mb_substr($item['description'], 0, 120)) ?>...</p>
                        <div class="portfolio-tags">
                            <?php foreach ($item['tags'] as $tag): ?>
                            <span class="tag"><?= htmlspecialchars($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
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
                <h2>Хотите заказать похожий проект?</h2>
                <p>Свяжитесь с нами для обсуждения деталей и расчета стоимости</p>
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

    <!-- Embed portfolio data for JS -->
    <script>
    window.PORTFOLIO_DATA = <?= json_encode($content['portfolio'], JSON_UNESCAPED_UNICODE) ?>;
    </script>

<?php require __DIR__ . '/includes/footer.php'; ?>
