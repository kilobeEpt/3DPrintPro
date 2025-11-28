<?php
// Set page identifiers for includes
$page_meta_key = 'home';
$canonical_url = '';
$active_page = 'home';

// Load content data
$CONTENT = require __DIR__ . '/data/content.php';
$site = $CONTENT['site'];
$services = $CONTENT['services'];
$portfolio = $CONTENT['portfolio'];
$testimonials = $CONTENT['testimonials'];
$faq = $CONTENT['faq'];
$stats = $CONTENT['stats'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body data-page="home">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-bg">
            <div class="particle" style="--i:1"></div>
            <div class="particle" style="--i:2"></div>
            <div class="particle" style="--i:3"></div>
            <div class="particle" style="--i:4"></div>
            <div class="particle" style="--i:5"></div>
        </div>
        <div class="container hero-content">
            <div class="hero-text">
                <h1 class="hero-title">
                    <span class="title-line">Профессиональная</span>
                    <span class="title-line gradient-text" id="heroTitle">3D печать в <?= $site['city'] ?></span>
                </h1>
                <p class="hero-description" id="heroDescription">
                    FDM, SLA, SLS технологии. Печать от 1 часа. 15+ материалов. <?= $stats['years'] ?> лет опыта.
                </p>
                <div class="hero-buttons">
                    <a href="#order-form-section" class="btn-cta-primary btn-lg">
                        <i class="fas fa-cube"></i>
                        <span>Заказать 3D печать</span>
                    </a>
                    <a href="portfolio.php" class="btn-cta-secondary btn-lg">
                        <i class="fas fa-images"></i>
                        <span>Наши работы</span>
                    </a>
                </div>
                <div class="hero-features" id="heroFeatures">
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Самовывоз в центре <?= $site['city'] ?></span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Доставка по городу</span>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <div class="floating-card">
                    <div class="card-3d">
                        <i class="fas fa-print"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="scroll-indicator">
            <div class="mouse">
                <div class="wheel"></div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card">
                    <i class="fas fa-project-diagram"></i>
                    <div class="stat-number" data-target="<?= $stats['projects'] ?>">0</div>
                    <p>Проектов выполнено</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="stat-number" data-target="<?= $stats['clients'] ?>">0</div>
                    <p>Довольных клиентов</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <div class="stat-number" data-target="<?= $stats['years'] ?>">0</div>
                    <p>Лет опыта</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-award"></i>
                    <div class="stat-number" data-target="<?= $stats['awards'] ?>">0</div>
                    <p>Наград получено</p>
                </div>
            </div>
        </div>
    </section>


    <!-- Services Section -->
    <section class="services omsk-services" id="services">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Наши услуги</span>
                <h2 class="section-title">Услуги 3D печати в <?= $site['city'] ?></h2>
                <p class="section-description">
                    FDM, SLA, SLS технологии. От прототипов до функциональных изделий.
                </p>
            </div>
            <div class="services-grid" id="servicesGrid">
                <?php
                $displayServices = array_slice(array_filter($services, fn($s) => $s['featured'] ?? false), 0, 4);
                if (empty($displayServices)) {
                    $displayServices = array_slice($services, 0, 4);
                }
                foreach ($displayServices as $service):
                ?>
                <a href="index.php#order-form-section" class="service-card <?= $service['featured'] ? 'featured' : '' ?>">
                    <?php if ($service['featured']): ?>
                    <div class="featured-badge">Популярное</div>
                    <?php endif; ?>
                    <div class="service-icon">
                        <i class="fas <?= $service['icon'] ?>" aria-hidden="true"></i>
                        <span class="sr-only"><?= htmlspecialchars($service['name']) ?></span>
                    </div>
                    <h3><?= htmlspecialchars($service['name']) ?></h3>
                    <p><?= htmlspecialchars($service['description']) ?></p>
                    <ul class="service-features">
                        <?php foreach ($service['features'] as $feature): ?>
                        <li><i class="fas fa-check-circle icon-success" aria-hidden="true"></i> <?= htmlspecialchars($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="cta-buttons">
                <a href="services.php" class="btn-cta-secondary">
                    <i class="fas fa-th-large"></i>
                    <span>Все услуги</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Portfolio Section -->
    <section class="portfolio" id="portfolio">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Портфолио</span>
                <h2 class="section-title">Наши работы</h2>
                <p class="section-description">
                    Примеры реализованных проектов
                </p>
            </div>

            <div class="portfolio-grid" id="portfolioGrid">
                <?php foreach (array_slice($portfolio, 0, 6) as $item): ?>
                <div class="portfolio-item" data-category="<?= strtolower($item['category']) ?>">
                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="portfolio-image" loading="lazy">
                    <span class="portfolio-category"><?= htmlspecialchars($item['category']) ?></span>
                    <div class="portfolio-overlay">
                        <h3><?= htmlspecialchars($item['title']) ?></h3>
                        <p><?= htmlspecialchars($item['technology']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="cta-buttons">
                <a href="portfolio.php" class="btn-cta-secondary">
                    <i class="fas fa-images"></i>
                    <span>Смотреть все работы</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials" id="testimonials">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Отзывы</span>
                <h2 class="section-title">Что говорят клиенты</h2>
                <p class="section-description">
                    Реальные отзывы наших заказчиков
                </p>
            </div>
            <div class="testimonials-wrapper">
                <div class="testimonials-container" id="testimonialsContainer">
                    <?php foreach ($testimonials as $index => $testimonial): ?>
                    <div class="testimonial-card <?= $index === 0 ? 'active' : '' ?>">
                        <div class="testimonial-header">
                            <div class="testimonial-avatar">
                                <?php if ($testimonial['avatar']): ?>
                                <img src="<?= htmlspecialchars($testimonial['avatar']) ?>" alt="<?= htmlspecialchars($testimonial['name']) ?>">
                                <?php else: ?>
                                <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div class="testimonial-info">
                                <h4><?= htmlspecialchars($testimonial['name']) ?></h4>
                                <p><?= htmlspecialchars($testimonial['company']) ?></p>
                                <div class="testimonial-rating">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                    <i class="fas fa-star <?= $i < $testimonial['rating'] ? '' : 'empty' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        <p class="testimonial-text"><?= htmlspecialchars($testimonial['text']) ?></p>
                        <div class="testimonial-date"><?= date('d.m.Y', strtotime($testimonial['date'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($testimonials) > 1): ?>
                <div class="testimonials-nav">
                    <button class="testimonial-nav-btn prev" onclick="changeTestimonial(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="testimonial-nav-btn next" onclick="changeTestimonial(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <?php endif; ?>
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
                    Ответы на популярные вопросы о 3D печати
                </p>
            </div>
            <div class="faq-container" id="faqContainer">
                <?php foreach ($faq as $index => $item): ?>
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

    <?php
    // Order Form Section - using reusable include
    $cta_source = 'homepage';
    include __DIR__ . '/includes/order-form.php';
    ?>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
