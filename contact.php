<?php
/**
 * Contact Page
 */

// Load content data
$content = require __DIR__ . '/data/content.php';

// Page metadata
$page_title = 'Контакты 3D печати в Омске — адрес, телефон, email | ' . $content['site']['name'];
$page_description = 'Контакты 3D Print Pro в Омске: телефон ' . $content['contact']['phone'] . ', email ' . $content['contact']['email'] . ', адрес ' . $content['contact']['address']['street'] . '. Режим работы, схема проезда.';
$page_keywords = 'контакты 3D печать Омск, адрес 3D печать Омск, телефон 3D печать, где заказать 3D печать Омск';
$canonical_url = $content['site']['url'] . '/contact.php';
$active_page = 'contact';
$body_data_page = 'contact';

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Главная', 'url' => $content['site']['url'] . '/'],
    ['name' => 'Контакты', 'url' => $canonical_url],
];

// Include head
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

    <!-- Page Hero -->
    <section class="page-hero">
        <div class="container">
            <h1 class="page-title">Контакты</h1>
            <p class="page-description">Свяжитесь с нами удобным способом</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-page">
        <div class="container">
            <div class="contact-grid">
                <!-- Contact Info Cards -->
                <div class="contact-info-cards">
                    <div class="contact-card-large">
                        <div class="card-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h3>Адрес</h3>
                        <p><?= htmlspecialchars($content['contact']['address']['city']) ?>, <?= htmlspecialchars($content['contact']['address']['postal_code']) ?></p>
                        <p><?= htmlspecialchars($content['contact']['address']['street']) ?></p>
                        <a href="https://yandex.ru/maps/?text=<?= urlencode($content['contact']['address']['city'] . ', ' . $content['contact']['address']['street']) ?>" target="_blank" class="btn btn-outline btn-sm" style="margin-top: 10px;">
                            <i class="fas fa-map"></i>
                            Показать на карте
                        </a>
                    </div>

                    <div class="contact-card-large">
                        <div class="card-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h3>Телефон</h3>
                        <p class="contact-value">
                            <a href="tel:<?= str_replace([' ', '(', ')', '-'], '', $content['contact']['phone']) ?>" style="color: var(--primary); text-decoration: none; font-weight: bold;">
                                <?= htmlspecialchars($content['contact']['phone']) ?>
                            </a>
                        </p>
                        <p>Звоните в рабочее время</p>
                    </div>

                    <div class="contact-card-large">
                        <div class="card-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3>Email</h3>
                        <p class="contact-value">
                            <a href="mailto:<?= htmlspecialchars($content['contact']['email']) ?>" style="color: var(--primary); text-decoration: none; font-weight: bold;">
                                <?= htmlspecialchars($content['contact']['email']) ?>
                            </a>
                        </p>
                        <p>Ответим в течение 24 часов</p>
                    </div>

                    <div class="contact-card-large">
                        <div class="card-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3>Режим работы</h3>
                        <p><?= htmlspecialchars($content['contact']['working_hours']['weekdays']) ?></p>
                        <p><?= htmlspecialchars($content['contact']['working_hours']['weekend']) ?></p>
                    </div>

                    <div class="contact-card-large">
                        <div class="card-icon">
                            <i class="fab fa-telegram"></i>
                        </div>
                        <h3>Telegram</h3>
                        <p>Быстрая связь в мессенджере</p>
                        <a href="<?= htmlspecialchars($content['contact']['telegram']) ?>" target="_blank" class="btn btn-primary btn-sm" style="margin-top: 10px;">
                            <i class="fab fa-telegram"></i>
                            Написать в Telegram
                        </a>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="contact-form-wrapper">
                    <div class="contact-form-header">
                        <h2>Отправьте нам сообщение</h2>
                        <p>Заполните форму, и мы свяжемся с вами в ближайшее время</p>
                    </div>

                    <form class="contact-form" id="contactForm">
                        <div class="form-group">
                            <label for="name">
                                <i class="fas fa-user"></i>
                                Ваше имя
                            </label>
                            <input type="text" id="name" name="name" class="form-control" placeholder="Иван Иванов" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">
                                    <i class="fas fa-phone"></i>
                                    Телефон
                                </label>
                                <input type="tel" id="phone" name="phone" class="form-control" placeholder="+7 (___) ___-__-__" required>
                            </div>

                            <div class="form-group">
                                <label for="email">
                                    <i class="fas fa-envelope"></i>
                                    Email
                                </label>
                                <input type="email" id="email" name="email" class="form-control" placeholder="example@mail.ru" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="subject">
                                <i class="fas fa-tag"></i>
                                Тема
                            </label>
                            <select id="subject" name="subject" class="form-control">
                                <option value="general">Общий вопрос</option>
                                <option value="quote">Запрос стоимости</option>
                                <option value="technical">Технический вопрос</option>
                                <option value="order">Оформление заказа</option>
                                <option value="support">Поддержка</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="message">
                                <i class="fas fa-comment"></i>
                                Сообщение
                            </label>
                            <textarea id="message" name="message" class="form-control" rows="5" placeholder="Расскажите о вашем проекте или задайте вопрос" required></textarea>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="privacy" required>
                                <span>Согласен на обработку персональных данных</span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-paper-plane"></i>
                            Отправить сообщение
                        </button>

                        <div class="form-note">
                            <i class="fas fa-info-circle"></i>
                            <p>Также вы можете написать нам в <a href="<?= htmlspecialchars($content['contact']['telegram']) ?>" target="_blank">Telegram</a> для быстрой связи</p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
            <h2 class="section-title">Как нас найти</h2>
            <div class="map-wrapper">
                <div id="map" style="width: 100%; height: 400px; background: #e0e0e0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <div style="text-align: center; color: #666;">
                        <i class="fas fa-map-marked-alt" style="font-size: 48px; margin-bottom: 15px; color: #999;"></i>
                        <p style="margin: 0;">Карта загружается...</p>
                        <p style="margin: 5px 0 0 0; font-size: 14px;">
                            <a href="https://yandex.ru/maps/?text=<?= urlencode($content['contact']['address']['city'] . ', ' . $content['contact']['address']['street']) ?>" target="_blank" style="color: var(--primary);">
                                Открыть в Яндекс.Картах
                            </a>
                        </p>
                    </div>
                </div>
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
            <div class="faq-list">
                <?php foreach (array_slice($content['faq'], 0, 6) as $item): ?>
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

<?php require __DIR__ . '/includes/footer.php'; ?>
