<?php
// Set page identifiers for includes
$page_meta_key = 'about';
$canonical_url = 'about.php';
$active_page = 'about';

// Load content data
$CONTENT = require __DIR__ . '/data/content.php';
$site = $CONTENT['site'];
$stats = $CONTENT['stats'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body data-page="about">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Page Hero -->
    <section class="page-hero text-center">
        <div class="container">
            <h1>О компании 3D Print Pro</h1>
            <p>Профессиональная 3D печать в Омске с 2011 года</p>
        </div>
    </section>

    <!-- Breadcrumbs -->
    <nav class="breadcrumbs">
        <div class="container">
            <ul>
                <li><a href="index.php">Главная</a></li>
                <li>О компании</li>
            </ul>
        </div>
    </nav>

    <!-- About Content -->
    <section class="content-section">
        <div class="container">
            <div class="content-wrapper">
                <h2>Наша история</h2>
                <p>
                    3D Print Pro — это команда профессионалов, увлечённых технологиями аддитивного производства. 
                    Мы начали свой путь в 2011 году, когда 3D-печать только набирала популярность в России, 
                    и с тех пор помогли реализовать более 1500 проектов для клиентов по всей Омской области.
                </p>
                <p>
                    Наша миссия — делать технологии 3D печати доступными для каждого. Мы работаем как с крупными 
                    компаниями, так и с частными лицами, предоставляя качественные услуги по разумным ценам.
                </p>

                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-year">2011</div>
                        <div class="timeline-content">
                            <h3>Основание компании</h3>
                            <p>
                                Открытие первой мастерской 3D печати в Омске. Закупка первого FDM принтера 
                                и начало работы с прототипированием для местных предприятий.
                            </p>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-year">2014</div>
                        <div class="timeline-content">
                            <h3>Расширение технологий</h3>
                            <p>
                                Приобретение оборудования для SLA-печати. Открытие отдела 3D моделирования. 
                                Первые крупные контракты с промышленными предприятиями.
                            </p>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-year">2017</div>
                        <div class="timeline-content">
                            <h3>Премиальный сегмент</h3>
                            <p>
                                Запуск SLS-печати для высокоточных проектов. Расширение парка оборудования до 
                                12 принтеров. Открытие цеха постобработки изделий.
                            </p>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-year">2020</div>
                        <div class="timeline-content">
                            <h3>Цифровизация</h3>
                            <p>
                                Запуск онлайн-калькулятора стоимости. Интеграция с Telegram для оперативной 
                                связи с клиентами. Внедрение автоматизированной системы управления заказами.
                            </p>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-year">2023</div>
                        <div class="timeline-content">
                            <h3>Лидер рынка</h3>
                            <p>
                                Более 1500 успешных проектов. Команда из 15 специалистов. Крупнейший парк 
                                3D-принтеров в Омске. Запуск образовательных программ по 3D технологиям.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values -->
    <section class="content-section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Наши ценности</span>
                <h2 class="section-title">Почему нам доверяют</h2>
            </div>
            <div class="why-us-grid">
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-award"></i>
                    </div>
                    <h3>Качество</h3>
                    <p>Контроль качества на каждом этапе производства. Используем только проверенные материалы и оборудование.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Честность</h3>
                    <p>Прозрачные цены без скрытых платежей. Предварительный расчет стоимости перед началом работы.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h3>Клиентоориентированность</h3>
                    <p>Индивидуальный подход к каждому проекту. Консультации на всех этапах работы.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3>Инновации</h3>
                    <p>Постоянное обновление оборудования. Внедрение новых технологий и материалов.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Скорость</h3>
                    <p>Соблюдаем все сроки. Возможность срочного изготовления от 1 часа.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Экспертиза</h3>
                    <p>12 лет опыта в индустрии. Команда сертифицированных специалистов.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-project-diagram"></i>
                    <div class="stat-number">1500+</div>
                    <p>Проектов выполнено</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="stat-number">850+</div>
                    <p>Довольных клиентов</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <div class="stat-number">12</div>
                    <p>Лет опыта</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-print"></i>
                    <div class="stat-number">15</div>
                    <p>3D-принтеров</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="content-section">
        <div class="container">
            <div class="content-wrapper" style="text-align: center;">
                <h2>Готовы начать свой проект?</h2>
                <p>Свяжитесь с нами для бесплатной консультации и расчета стоимости</p>
                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-top: 30px;">
                    <a href="index.php#order-form-section" class="btn btn-primary">
                        <i class="fas fa-cube"></i>
                        Заказать 3D печать
                    </a>
                    <a href="contact.php" class="btn btn-outline">
                        <i class="fas fa-phone"></i>
                        Связаться с нами
                    </a>
                    <a href="<?= $site['telegram'] ?>" target="_blank" class="btn btn-outline">
                        <i class="fab fa-telegram"></i>
                        Написать в Telegram
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
