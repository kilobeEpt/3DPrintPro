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
            <h1>Контакты <?= $site['name'] ?> в <?= $site['city'] ?></h1>
            <p>Свяжитесь с нами удобным способом — звоните, пишите или приезжайте к нам в офис</p>
        </div>
    </section>

    <!-- Contact Info Section -->
    <section class="contact-info">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h3>Телефон</h3>
                    <p><a href="tel:<?= str_replace([' ', '(', ')', '-'], '', $site['phone']) ?>"><?= $site['phone'] ?></a></p>
                    <span class="contact-hint">Звоните с 9:00 до 18:00</span>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Email</h3>
                    <p><a href="mailto:<?= $site['email'] ?>"><?= $site['email'] ?></a></p>
                    <span class="contact-hint">Ответим в течение 2 часов</span>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fab fa-telegram"></i>
                    </div>
                    <h3>Telegram</h3>
                    <p><a href="<?= $site['telegram'] ?>" target="_blank">@PrintPro_Omsk</a></p>
                    <span class="contact-hint">Быстрая связь 24/7</span>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>Адрес</h3>
                    <p><?= $site['address'] ?></p>
                    <span class="contact-hint"><?= $site['city'] ?>, <?= $site['postal_code'] ?></span>
                </div>
            </div>
        </div>
    </section>

    <!-- Working Hours Section -->
    <section class="working-hours">
        <div class="container">
            <div class="hours-card">
                <div class="hours-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="hours-content">
                    <h2>Режим работы</h2>
                    <div class="hours-grid">
                        <div class="hours-item">
                            <strong><?= $site['working_hours']['weekdays'] ?></strong>
                        </div>
                        <div class="hours-item">
                            <strong><?= $site['working_hours']['weekend'] ?></strong>
                        </div>
                    </div>
                    <p style="margin-top: 15px; color: var(--text-secondary);">
                        <i class="fas fa-info-circle"></i>
                        Принимаем заказы через сайт и Telegram круглосуточно
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
            <h2>Как нас найти</h2>
            <div class="map-container">
                <div class="map-placeholder">
                    <i class="fas fa-map-marked-alt"></i>
                    <p>Карта</p>
                    <p style="font-size: 14px; color: var(--text-secondary);">
                        Координаты: <?= $site['geo']['latitude'] ?>, <?= $site['geo']['longitude'] ?>
                    </p>
                </div>
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

    <?php
    // Contact Form Section - using reusable order form include with custom parameters
    $form_heading = 'Отправьте сообщение';
    $form_description = 'Заполните форму, и мы свяжемся с вами в течение 15 минут';
    $form_label = 'Напишите нам';
    $section_id = 'contact-form-section';
    $form_id = 'contactForm';
    $preselect_service = 'Консультация';
    include __DIR__ . '/includes/order-form.php';
    ?>

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

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
