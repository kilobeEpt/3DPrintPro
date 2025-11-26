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
                    <a href="#calculator" class="btn btn-primary">
                        <span>Рассчитать стоимость</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="portfolio.php" class="btn btn-outline">
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
                <a href="index.php#calculator" class="service-card <?= $service['featured'] ? 'featured' : '' ?>" style="text-decoration: none; color: inherit; display: block;">
                    <?php if ($service['featured']): ?>
                    <div class="featured-badge">Популярное</div>
                    <?php endif; ?>
                    <div class="service-icon">
                        <i class="fas <?= $service['icon'] ?>"></i>
                    </div>
                    <h3><?= htmlspecialchars($service['name']) ?></h3>
                    <p><?= htmlspecialchars($service['description']) ?></p>
                    <ul class="service-features">
                        <?php foreach ($service['features'] as $feature): ?>
                        <li><i class="fas fa-check"></i> <?= htmlspecialchars($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </a>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 30px;">
                <a href="services.php" class="btn btn-outline">
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
                            <option value="fdm">FDM (послойное наплавление)</option>
                            <option value="sla">SLA (фотополимерная)</option>
                            <option value="sls">SLS (лазерное спекание)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="material">
                            <i class="fas fa-layer-group"></i>
                            Материал
                        </label>
                        <select id="material" class="form-control">
                            <!-- Options will be loaded dynamically -->
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
                            <option value="draft">Черновое (быстро)</option>
                            <option value="normal" selected>Нормальное</option>
                            <option value="high">Высокое</option>
                            <option value="ultra">Ультра (медленно)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-plus-circle"></i>
                            Дополнительные услуги
                        </label>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="modeling">
                                <span>3D моделирование (<span class="service-price" data-service="modeling">500</span>₽/час)</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" id="postProcessing">
                                <span>Постобработка (<span class="service-price" data-service="postProcessing">300</span>₽)</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" id="painting">
                                <span>Покраска (<span class="service-price" data-service="painting">500</span>₽)</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" id="express">
                                <span>Срочное изготовление (<span class="service-price" data-service="express">1000</span>₽)</span>
                            </label>
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
                        <button class="btn btn-success btn-block" onclick="document.getElementById('order').scrollIntoView({ behavior: 'smooth', block: 'start' })">
                            <i class="fas fa-paper-plane"></i>
                            Отправить заявку
                        </button>
                        <a href="<?= $site['telegram'] ?>" target="_blank" class="btn btn-outline btn-block" style="margin-top: 10px; text-decoration: none;">
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

    <!-- Order Form Section -->
    <section class="order-form-section" id="order">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Оформить заказ</span>
                <h2 class="section-title">Закажите 3D печать прямо сейчас</h2>
                <p class="section-description">
                    Заполните форму, и мы свяжемся с вами в течение 15 минут
                </p>
            </div>
            
            <div class="order-form-wrapper">
                <form class="order-form" id="order-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="orderFio">
                                <i class="fas fa-user"></i>
                                ФИО*
                            </label>
                            <input 
                                type="text" 
                                id="orderFio" 
                                name="fio" 
                                class="form-control" 
                                placeholder="Иванов Иван Иванович"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="orderEmail">
                                <i class="fas fa-envelope"></i>
                                Email*
                            </label>
                            <input 
                                type="email" 
                                id="orderEmail" 
                                name="email" 
                                class="form-control" 
                                placeholder="example@mail.com"
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="orderPhone">
                                <i class="fas fa-phone"></i>
                                Телефон*
                            </label>
                            <input 
                                type="tel" 
                                id="orderPhone" 
                                name="phone" 
                                class="form-control" 
                                placeholder="+7 (900) 123-45-67"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="orderTelegram">
                                <i class="fab fa-telegram"></i>
                                Telegram username*
                            </label>
                            <input 
                                type="text" 
                                id="orderTelegram" 
                                name="telegram" 
                                class="form-control" 
                                placeholder="username (без @)"
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="orderService">
                            <i class="fas fa-cogs"></i>
                            Услуга*
                        </label>
                        <select id="orderService" name="service" class="form-control" required>
                            <option value="">Выберите услугу</option>
                            <option value="FDM печать">FDM печать</option>
                            <option value="SLA печать">SLA печать</option>
                            <option value="SLS печать">SLS печать</option>
                            <option value="Цветная печать">Цветная печать</option>
                            <option value="Постобработка">Постобработка</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="orderDescription">
                            <i class="fas fa-comment-alt"></i>
                            Описание проекта*
                        </label>
                        <textarea 
                            id="orderDescription" 
                            name="description" 
                            class="form-control" 
                            rows="5" 
                            placeholder="Расскажите о вашем проекте (минимум 10 символов)"
                            required
                        ></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="orderFiles">
                            <i class="fas fa-file-upload"></i>
                            Загрузить файл (опционально)
                        </label>
                        <input 
                            type="file" 
                            id="orderFiles" 
                            name="files" 
                            class="form-control" 
                            accept=".stl,.obj,.gcode,.step,.stp,.3mf,.amf,.ply"
                        >
                        <small style="color: var(--text-secondary); display: block; margin-top: 5px;">
                            Допустимые форматы: STL, OBJ, GCODE, STEP, 3MF, AMF, PLY. Максимум 50 МБ
                        </small>
                        <div id="file-info" style="display: none; color: var(--accent); margin-top: 5px; font-size: 14px;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="privacy" required>
                            <span>Согласен на обработку персональных данных</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-paper-plane"></i>
                        Отправить заказ
                    </button>
                </form>
                
                <div id="form-message" class="form-message" style="display: none;"></div>
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
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="portfolio.php" class="btn btn-outline">
                    <span>Смотреть все работы</span>
                    <i class="fas fa-arrow-right"></i>
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

    <!-- Contact Form Section -->
    <section class="contact-form-section" id="contact-form">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Связаться</span>
                <h2 class="section-title">Остались вопросы?</h2>
                <p class="section-description">
                    Оставьте заявку, и мы свяжемся с вами в течение 15 минут
                </p>
            </div>
            <div class="contact-form-wrapper">
                <form class="contact-form" id="contactForm" onsubmit="handleFormSubmit(event)">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contactName">
                                <i class="fas fa-user"></i>
                                Ваше имя*
                            </label>
                            <input type="text" id="contactName" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="contactPhone">
                                <i class="fas fa-phone"></i>
                                Телефон*
                            </label>
                            <input type="tel" id="contactPhone" name="phone" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="contactEmail">
                            <i class="fas fa-envelope"></i>
                            Email
                        </label>
                        <input type="email" id="contactEmail" name="email" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="contactMessage">
                            <i class="fas fa-comment"></i>
                            Сообщение*
                        </label>
                        <textarea id="contactMessage" name="message" class="form-control" rows="5" required></textarea>
                    </div>
                    
                    <!-- Динамические поля формы -->
                    <div id="dynamicFormFields">
                        <div class="form-loading" style="text-align: center; padding: 20px; color: var(--text-secondary);">
                            <i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>
                            Загрузка формы...
                        </div>
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
                    <a href="<?= $site['telegram'] ?>" target="_blank" class="btn btn-outline btn-block" style="margin-top: 15px; text-decoration: none;">
                        <i class="fab fa-telegram"></i>
                        Написать в Telegram
                    </a>
                </form>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
