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
            <h1>Контакты <?= $site['name'] ?> — FDM, SLA, SLS печать в <?= $site['city'] ?></h1>
            <p>Послойная 3D печать, 3D моделирование и сканирование. Звоните, пишите или приезжайте к нам в офис</p>
        </div>
    </section>

    <!-- Two-Column Contact Layout -->
    <section class="contact-main-section">
        <div class="container">
            <div class="contact-layout">
                <!-- Left Column: Order Form -->
                <div class="contact-form-column">
                    <?php
                    $form_heading = 'Отправьте сообщение';
                    $form_description = 'Заполните форму, и мы свяжемся с вами в течение 15 минут';
                    $form_label = 'Напишите нам';
                    $section_id = 'contact-form-section';
                    $form_id = 'contactForm';
                    $preselect_service = 'Консультация';
                    $cta_source = 'contact';
                    $show_info = false;
                    include __DIR__ . '/includes/order-form.php';
                    ?>
                </div>

                <!-- Right Column: Contact Info -->
                <div class="contact-info-column">
                    <div class="contact-panel">
                        <!-- Contact Details Cards -->
                        <div class="contact-details">
                            <h2 class="contact-panel-title">Контактная информация</h2>
                            
                            <!-- Address -->
                            <div class="contact-info-item">
                                <div class="contact-info-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="contact-info-content">
                                    <h3>Адрес</h3>
                                    <p><?= $site['address'] ?></p>
                                    <span class="text-muted"><?= $site['city'] ?>, <?= $site['postal_code'] ?></span>
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="contact-info-item">
                                <div class="contact-info-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="contact-info-content">
                                    <h3>Телефон</h3>
                                    <p><a href="tel:<?= str_replace([' ', '(', ')', '-'], '', $site['phone']) ?>"><?= $site['phone'] ?></a></p>
                                    <span class="text-muted">Звоните с 9:00 до 18:00</span>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="contact-info-item">
                                <div class="contact-info-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-info-content">
                                    <h3>Email</h3>
                                    <p><a href="mailto:<?= $site['email'] ?>"><?= $site['email'] ?></a></p>
                                    <span class="text-muted">Ответим в течение 2 часов</span>
                                </div>
                            </div>

                            <!-- Telegram -->
                            <div class="contact-info-item">
                                <div class="contact-info-icon">
                                    <i class="fab fa-telegram"></i>
                                </div>
                                <div class="contact-info-content">
                                    <h3>Telegram</h3>
                                    <p><a href="<?= $site['telegram'] ?>" target="_blank" rel="noopener">@PrintPro_Omsk</a></p>
                                    <span class="text-muted">Быстрая связь 24/7</span>
                                </div>
                            </div>

                            <!-- Working Hours -->
                            <div class="contact-info-item">
                                <div class="contact-info-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="contact-info-content">
                                    <h3>Режим работы</h3>
                                    <p><?= $site['working_hours']['weekdays'] ?></p>
                                    <p><?= $site['working_hours']['weekend'] ?></p>
                                    <span class="text-muted">Заказы онлайн круглосуточно</span>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="contact-actions">
                            <h3 class="contact-actions-title">Быстрые действия</h3>
                            <div class="contact-actions-buttons">
                                <a href="tel:<?= str_replace([' ', '(', ')', '-'], '', $site['phone']) ?>" 
                                   class="btn-cta-secondary btn-sm contact-action-btn"
                                   aria-label="Позвонить нам">
                                    <i class="fas fa-phone"></i>
                                    <span>Позвонить</span>
                                </a>
                                <a href="mailto:<?= $site['email'] ?>" 
                                   class="btn-cta-secondary btn-sm contact-action-btn"
                                   aria-label="Написать на email">
                                    <i class="fas fa-envelope"></i>
                                    <span>Email</span>
                                </a>
                                <a href="<?= $site['telegram'] ?>" 
                                   class="btn-cta-secondary btn-sm contact-action-btn"
                                   target="_blank" 
                                   rel="noopener"
                                   aria-label="Написать в Telegram">
                                    <i class="fab fa-telegram"></i>
                                    <span>Telegram</span>
                                </a>
                                <a href="<?= $site['whatsapp'] ?>" 
                                   class="btn-cta-secondary btn-sm contact-action-btn"
                                   target="_blank" 
                                   rel="noopener"
                                   aria-label="Написать в WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                    <span>WhatsApp</span>
                                </a>
                            </div>
                        </div>

                        <!-- Social Links -->
                        <div class="contact-social">
                            <h3 class="contact-social-title">Мы в соцсетях</h3>
                            <div class="contact-social-list">
                                <?php foreach ($site['social_links'] as $social): ?>
                                <a href="<?= $social['url'] ?>" 
                                   class="contact-social-link"
                                   target="_blank" 
                                   rel="noopener"
                                   aria-label="<?= $social['name'] ?>">
                                    <i class="<?= $social['icon'] ?>"></i>
                                    <span><?= $social['name'] ?></span>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
            <h2>Как нас найти</h2>
            <div class="map-embed-container">
                <?php if (!empty($site['map_url'])): ?>
                <iframe 
                    class="map-embed"
                    src="<?= $site['map_url'] ?>"
                    loading="lazy"
                    title="Карта с расположением <?= $site['name'] ?>"
                    allowfullscreen
                    aria-label="Интерактивная карта">
                </iframe>
                <?php else: ?>
                <div class="map-fallback">
                    <i class="fas fa-map-marked-alt"></i>
                    <p>Карта недоступна</p>
                    <p class="coord-text">
                        Координаты: <?= $site['geo']['latitude'] ?>, <?= $site['geo']['longitude'] ?>
                    </p>
                </div>
                <?php endif; ?>
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
