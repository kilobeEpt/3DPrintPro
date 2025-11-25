<?php
/**
 * Homepage
 */

// Load content data
$content = require __DIR__ . '/data/content.php';

// Page metadata
$page_title = '3D печать в Омске — услуги 3D печати и моделирования | ' . $content['site']['name'];
$page_description = $content['site']['description'];
$page_keywords = '3D печать Омск, услуги 3D печати Омск, 3D моделирование Омск, FDM печать, SLA печать, прототипирование Омск, 3D принтер услуги, печать на 3D принтере Омск';
$canonical_url = $content['site']['url'] . '/';
$active_page = 'home';
$body_data_page = 'home';

// Breadcrumbs for structured data
$breadcrumbs = [
    ['name' => 'Главная', 'url' => $content['site']['url'] . '/'],
    ['name' => 'Услуги 3D печати в Омске', 'url' => $content['site']['url'] . '/services.php'],
    ['name' => 'Портфолио работ', 'url' => $content['site']['url'] . '/portfolio.php'],
    ['name' => 'О компании', 'url' => $content['site']['url'] . '/about.html'],
    ['name' => 'Контакты в Омске', 'url' => $content['site']['url'] . '/contact.php'],
];

// Additional structured data for services
$structured_data = [
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    'serviceType' => '3D печать и 3D моделирование',
    'provider' => [
        '@id' => $content['site']['url'] . '/#organization',
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
        }, array_slice($content['services'], 0, 5)),
    ],
];

// Include head
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

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
                    <span class="title-line gradient-text" id="heroTitle">3D печать в Омске</span>
                </h1>
                <p class="hero-description" id="heroDescription">
                    FDM, SLA, SLS технологии. Печать от 1 часа. 15+ материалов. <?= date('Y') - $content['site']['founded_year'] ?> лет опыта.
                </p>
                <div class="hero-buttons">
                    <a href="#calculator" class="btn btn-primary">
                        <span>Рассчитать стоимость</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="/portfolio.php" class="btn btn-outline">
                        <span>Наши работы</span>
                    </a>
                </div>
                <div class="hero-features" id="heroFeatures">
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Самовывоз в центре Омска</span>
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
            <span>Листайте вниз</span>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid" id="statsGrid">
                <?php foreach ($content['stats'] as $stat): ?>
                <div class="stat-card">
                    <i class="<?= htmlspecialchars($stat['icon']) ?>"></i>
                    <div class="stat-number" data-target="<?= $stat['number'] ?>">0</div>
                    <p><?= htmlspecialchars($stat['label']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services omsk-services" id="services">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Наши услуги</span>
                <h2 class="section-title">Услуги 3D печати в Омске</h2>
                <p class="section-description">
                    FDM, SLA, SLS технологии. От прототипов до функциональных изделий.
                </p>
            </div>
            <div class="services-grid" id="servicesGrid">
                <?php foreach (array_slice($content['services'], 0, 4) as $service): ?>
                <div class="service-card" data-service-id="<?= htmlspecialchars($service['id']) ?>">
                    <div class="service-icon">
                        <i class="<?= htmlspecialchars($service['icon']) ?>"></i>
                    </div>
                    <h3><?= htmlspecialchars($service['name']) ?></h3>
                    <p><?= htmlspecialchars($service['short_description']) ?></p>
                    <ul class="service-features">
                        <?php foreach (array_slice($service['features'], 0, 3) as $feature): ?>
                        <li><i class="fas fa-check"></i> <?= htmlspecialchars($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="service-price">
                        <span class="price">от <?= $service['price_from'] ?>₽</span>
                        <span class="price-label"><?= htmlspecialchars($service['price_unit']) ?></span>
                    </div>
                    <a href="/services.php#<?= htmlspecialchars($service['slug']) ?>" class="btn btn-outline btn-sm">Подробнее</a>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 30px;">
                <a href="/services.php" class="btn btn-outline">
                    <span>Все услуги</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Calculator Section -->
    <section class="calculator-section" id="calculator">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Калькулятор</span>
                <h2 class="section-title">Рассчитайте стоимость</h2>
                <p class="section-description">
                    Моментальный расчет стоимости вашего заказа
                </p>
            </div>
            <div class="calculator-wrapper">
                <div class="calculator-form">
                    <div class="form-group">
                        <label for="printTechnology">
                            <i class="fas fa-print"></i>
                            Технология печати
                        </label>
                        <select id="printTechnology" class="form-control">
                            <?php foreach ($content['calculator']['technologies'] as $key => $name): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="material">
                            <i class="fas fa-layer-group"></i>
                            Материал
                        </label>
                        <select id="material" class="form-control">
                            <!-- Options will be loaded dynamically by JS -->
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="weight">
                                <i class="fas fa-weight"></i>
                                Вес модели (г)
                            </label>
                            <input type="number" id="weight" class="form-control" value="100" min="1" max="10000">
                        </div>

                        <div class="form-group">
                            <label for="quantity">
                                <i class="fas fa-copy"></i>
                                Количество (шт)
                            </label>
                            <input type="number" id="quantity" class="form-control" value="1" min="1" max="1000">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="infill">
                            <i class="fas fa-percentage"></i>
                            Заполнение: <span id="infillValue">20</span>%
                        </label>
                        <input type="range" id="infill" class="range-slider" min="0" max="100" value="20">
                        <div class="range-labels">
                            <span>0%</span>
                            <span>50%</span>
                            <span>100%</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="quality">
                            <i class="fas fa-sliders-h"></i>
                            Качество печати
                        </label>
                        <select id="quality" class="form-control">
                            <?php foreach ($content['calculator']['quality'] as $key => $qual): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($qual['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-plus-circle"></i>
                            Дополнительные услуги
                        </label>
                        <div class="checkbox-group">
                            <?php foreach ($content['calculator']['services'] as $key => $service): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" id="<?= htmlspecialchars($key) ?>">
                                <span><?= htmlspecialchars($service['name']) ?> (<span class="service-price" data-service="<?= htmlspecialchars($key) ?>"><?= $service['price'] ?></span>₽ <?= htmlspecialchars($service['unit']) ?>)</span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button class="btn btn-primary btn-block" onclick="calculatePrice()">
                        <i class="fas fa-calculator"></i>
                        Рассчитать стоимость
                    </button>
                </div>

                <div class="calculator-result">
                    <div class="result-card">
                        <h3>Расчет стоимости</h3>
                        <div class="price-breakdown" id="priceBreakdown">
                            <div class="price-item">
                                <span>Материалы:</span>
                                <span id="materialCost">0₽</span>
                            </div>
                            <div class="price-item">
                                <span>Работа:</span>
                                <span id="laborCost">0₽</span>
                            </div>
                            <div class="price-item">
                                <span>Доп. услуги:</span>
                                <span id="additionalCost">0₽</span>
                            </div>
                            <div class="price-item discount" id="discountItem" style="display: none;">
                                <span>Скидка:</span>
                                <span id="discountAmount">-0₽</span>
                            </div>
                        </div>
                        <div class="total-price">
                            <span>Итого:</span>
                            <span class="price" id="totalPrice">0₽</span>
                        </div>
                        <div class="estimate-time">
                            <i class="fas fa-clock"></i>
                            <span>Срок изготовления: <strong id="estimateTime">-</strong></span>
                        </div>
                        <button class="btn btn-success btn-block" onclick="scrollToContactForm()">
                            <i class="fas fa-paper-plane"></i>
                            Отправить заявку
                        </button>
                        <a href="<?= htmlspecialchars($content['contact']['telegram']) ?>" target="_blank" class="btn btn-outline btn-block" style="margin-top: 10px; text-decoration: none;">
                            <i class="fab fa-telegram"></i>
                            Написать в Telegram
                        </a>
                        <div class="result-info">
                            <i class="fas fa-info-circle"></i>
                            <p>Расчет является предварительным. Точная стоимость определяется после анализа модели.</p>
                        </div>
                    </div>
                </div>
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
                <?php foreach (array_slice($content['portfolio'], 0, 3) as $item): ?>
                <div class="portfolio-card" data-id="<?= $item['id'] ?>">
                    <div class="portfolio-image">
                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22%3E%3Crect fill=%22%23e0e0e0%22 width=%22400%22 height=%22300%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2218%22 text-anchor=%22middle%22 fill=%22%23999%22%3E<?= htmlspecialchars($item['title']) ?>%3C/text%3E%3C/svg%3E'">
                        <div class="portfolio-overlay">
                            <button class="btn btn-outline btn-sm" onclick="showPortfolioDetails(<?= $item['id'] ?>)">
                                <span>Подробнее</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="portfolio-info">
                        <h3><?= htmlspecialchars($item['title']) ?></h3>
                        <p><?= htmlspecialchars($item['technology']) ?> · <?= htmlspecialchars($item['duration']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="/portfolio.php" class="btn btn-outline">
                    <span>Смотреть все работы</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="omsk-section omsk-why-us" id="why-us">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Наши преимущества</span>
                <h2 class="section-title">Почему выбирают нас</h2>
                <p class="section-description">
                    <?= date('Y') - $content['site']['founded_year'] ?> лет опыта в Омске. Современное оборудование. Команда профессионалов.
                </p>
            </div>
            <div class="why-us-grid">
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>Срочные заказы</h3>
                    <p>Печать от 1 часа. Срочное изготовление для всех технологий печати.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Гарантия качества</h3>
                    <p>Контроль качества каждого изделия. В случае брака — бесплатная перепечать.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3>Полный цикл работ</h3>
                    <p>Моделирование, печать, постобработка, покраска — всё под ключ.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <h3>Доступные цены</h3>
                    <p>Конкурентные цены. Скидки для постоянных клиентов и больших объёмов.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Высокие рейтинги</h3>
                    <p>Средняя оценка 4.9/5.0 от более чем <?= $content['stats'][1]['number'] ?> клиентов.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Отзывы</span>
                <h2 class="section-title">Что говорят клиенты</h2>
            </div>
            <div class="testimonials-slider" id="testimonialsSlider">
                <?php foreach ($content['testimonials'] as $index => $testimonial): ?>
                <div class="testimonial-card<?= $index === 0 ? ' active' : '' ?>" data-index="<?= $index ?>">
                    <div class="testimonial-content">
                        <div class="testimonial-rating">
                            <?php for ($i = 0; $i < $testimonial['rating']; $i++): ?>
                            <i class="fas fa-star"></i>
                            <?php endfor; ?>
                            <?php for ($i = $testimonial['rating']; $i < 5; $i++): ?>
                            <i class="far fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="testimonial-text">"<?= htmlspecialchars($testimonial['text']) ?>"</p>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <img src="<?= htmlspecialchars($testimonial['avatar']) ?>" alt="<?= htmlspecialchars($testimonial['name']) ?>" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2260%22 height=%2260%22%3E%3Ccircle fill=%22%23e0e0e0%22 cx=%2230%22 cy=%2230%22 r=%2230%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2224%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22%3E<?= mb_substr($testimonial['name'], 0, 1) ?>%3C/text%3E%3C/svg%3E'">
                            </div>
                            <div class="author-info">
                                <strong><?= htmlspecialchars($testimonial['name']) ?></strong>
                                <span><?= htmlspecialchars($testimonial['position']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="slider-controls">
                <button class="slider-btn" id="prevTestimonial" aria-label="Предыдущий отзыв">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="slider-btn" id="nextTestimonial" aria-label="Следующий отзыв">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq">
        <div class="container">
            <div class="section-header">
                <span class="section-label">FAQ</span>
                <h2 class="section-title">Часто задаваемые вопросы</h2>
            </div>
            <div class="faq-list" id="faqList">
                <?php foreach (array_slice($content['faq'], 0, 5) as $item): ?>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        <span><?= htmlspecialchars($item['question']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p><?= htmlspecialchars($item['answer']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact" id="contact">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Контакты</span>
                <h2 class="section-title">Свяжитесь с нами</h2>
                <p class="section-description">
                    Ответим на все вопросы и поможем с вашим проектом
                </p>
            </div>
            <div class="contact-wrapper">
                <div class="contact-info">
                    <div class="contact-card">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3>Адрес</h3>
                        <p id="contactAddress"><?= htmlspecialchars($content['contact']['address']['city']) ?>, <?= htmlspecialchars($content['contact']['address']['street']) ?></p>
                    </div>
                    <div class="contact-card">
                        <i class="fas fa-phone"></i>
                        <h3>Телефон</h3>
                        <p><a href="tel:<?= str_replace([' ', '(', ')', '-'], '', $content['contact']['phone']) ?>" id="contactPhone" style="color: var(--text); text-decoration: none;"><?= htmlspecialchars($content['contact']['phone']) ?></a></p>
                    </div>
                    <div class="contact-card">
                        <i class="fas fa-envelope"></i>
                        <h3>Email</h3>
                        <p><a href="mailto:<?= htmlspecialchars($content['contact']['email']) ?>" id="contactEmail" style="color: var(--text); text-decoration: none;"><?= htmlspecialchars($content['contact']['email']) ?></a></p>
                    </div>
                    <div class="contact-card">
                        <i class="fas fa-clock"></i>
                        <h3>Режим работы</h3>
                        <p id="contactHours"><?= htmlspecialchars($content['contact']['working_hours']['weekdays']) ?><br><?= htmlspecialchars($content['contact']['working_hours']['weekend']) ?></p>
                    </div>
                </div>
                <form class="contact-form" id="contactForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Имя</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Телефон</label>
                            <input type="tel" id="phone" name="phone" class="form-control" placeholder="+7 (___) ___-__-__" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Сообщение</label>
                        <textarea id="message" name="message" class="form-control" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="privacy" required>
                            <span>Согласен на обработку персональных данных</span>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-paper-plane"></i>
                        Отправить сообщение
                    </button>
                    <a href="<?= htmlspecialchars($content['contact']['telegram']) ?>" target="_blank" class="btn btn-outline btn-block" style="margin-top: 15px; text-decoration: none;">
                        <i class="fab fa-telegram"></i>
                        Написать в Telegram
                    </a>
                </form>
            </div>
        </div>
    </section>

    <!-- Embed calculator config for JS -->
    <script>
    window.CALCULATOR_CONFIG = <?= json_encode($content['calculator'], JSON_UNESCAPED_UNICODE) ?>;
    window.PORTFOLIO_DATA = <?= json_encode($content['portfolio'], JSON_UNESCAPED_UNICODE) ?>;
    </script>

<?php require __DIR__ . '/includes/footer.php'; ?>
