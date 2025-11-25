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

    <!-- Contact Form Section -->
    <section class="contact-form-section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Напишите нам</span>
                <h2 class="section-title">Отправьте сообщение</h2>
                <p class="section-description">
                    Заполните форму, и мы свяжемся с вами в течение 15 минут
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
                        <label for="contactSubject">
                            <i class="fas fa-tag"></i>
                            Тема обращения
                        </label>
                        <select id="contactSubject" name="subject" class="form-control">
                            <option value="order">Заказ 3D печати</option>
                            <option value="modeling">3D моделирование</option>
                            <option value="consultation">Консультация</option>
                            <option value="partnership">Сотрудничество</option>
                            <option value="other">Другое</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="contactMessage">
                            <i class="fas fa-comment"></i>
                            Сообщение*
                        </label>
                        <textarea id="contactMessage" name="message" class="form-control" rows="5" required></textarea>
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
