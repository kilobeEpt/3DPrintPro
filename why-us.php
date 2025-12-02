<?php
// Set page identifiers for includes
$page_meta_key = 'why-us';
$canonical_url = 'why-us.php';
$active_page = 'why-us';

// Load content data
$CONTENT = require __DIR__ . '/data/content.php';
$site = $CONTENT['site'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body data-page="why-us">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Page Hero -->
    <section class="page-hero">
        <div class="container">
            <div class="breadcrumbs">
                <a href="index.php">Главная</a>
                <span>/</span>
                <span>Почему мы</span>
            </div>
            <h1>Почему выбирают 3D Print Pro для FDM, SLA, SLS печати в Омске</h1>
            <p>12 лет опыта послойной 3D печати, 3D моделирование, 3D сканирование, постобработка. 1500+ успешных проектов</p>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="content-section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Наши преимущества</span>
                <h2 class="section-title">Что делает нас лучшими в Омске</h2>
            </div>
            <div class="why-us-grid">
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>Срочные заказы</h3>
                    <p>Печать от 1 часа. Срочное изготовление для всех технологий печати. Работаем круглосуточно при необходимости.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Гарантия качества</h3>
                    <p>Контроль качества каждого изделия. В случае брака — бесплатная перепечать. Используем только сертифицированные материалы.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3>Полный цикл работ</h3>
                    <p>Моделирование, печать, постобработка, покраска — всё под ключ. Не нужно искать разных исполнителей.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <h3>Доступные цены</h3>
                    <p>Конкурентные цены без переплат. Скидки для постоянных клиентов и больших объёмов. Прозрачный расчёт онлайн.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Высокие рейтинги</h3>
                    <p>Средняя оценка 4.9/5.0 от более чем 850 клиентов. Читайте отзывы на Яндекс.Картах и 2ГИС.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3>Современное оборудование</h3>
                    <p>15 принтеров: FDM, SLA, SLS. Регулярное обновление парка техники. Работаем на оборудовании мировых брендов.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <h3>Широкий выбор материалов</h3>
                    <p>15+ материалов для разных задач: PLA, ABS, PETG, TPU, нейлон, фотополимеры, порошки. Подберём оптимальный вариант.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Опыт и экспертиза</h3>
                    <p>12 лет на рынке 3D печати. Команда сертифицированных специалистов. Опыт работы с промышленными предприятиями.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h3>Удобная доставка</h3>
                    <p>Бесплатная доставка по Омску от 3000₽. Курьер, самовывоз, почта. Работаем со всеми районами города.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fas fa-cube"></i>
                    </div>
                    <h3>Онлайн-калькулятор</h3>
                    <p>Рассчитайте стоимость за 2 минуты. Прозрачная система ценообразования. Никаких скрытых платежей.</p>
                </div>
                <div class="why-us-card">
                    <div class="why-us-icon">
                        <i class="fab fa-telegram"></i>
                    </div>
                    <h3>Быстрая связь</h3>
                    <p>Telegram-бот для мгновенной связи. Отправляйте фото, модели, задавайте вопросы. Ответим в течение 15 минут.</p>
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
                    <i class="fas fa-award"></i>
                    <div class="stat-number">4.9/5</div>
                    <p>Средняя оценка</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Guarantees -->
    <section class="content-section">
        <div class="container">
            <div class="content-wrapper">
                <h2>Наши гарантии</h2>
                <p>
                    Мы уверены в качестве своей работы, поэтому предоставляем клиентам прозрачные гарантии:
                </p>

                <h3><i class="fas fa-check-circle" style="color: var(--success); margin-right: 10px;"></i>Гарантия качества печати</h3>
                <ul>
                    <li>Если изделие имеет производственный брак — бесплатно перепечатаем</li>
                    <li>Контроль каждой детали перед отправкой клиенту</li>
                    <li>Используем только сертифицированные материалы</li>
                    <li>Калибровка оборудования перед каждым запуском</li>
                </ul>

                <h3><i class="fas fa-clock" style="color: var(--success); margin-right: 10px;"></i>Соблюдение сроков</h3>
                <ul>
                    <li>Указываем точные сроки перед началом работы</li>
                    <li>Уведомляем о готовности по SMS и в Telegram</li>
                    <li>При задержке по нашей вине — скидка 10% на заказ</li>
                    <li>Возможность отслеживания статуса в личном кабинете</li>
                </ul>

                <h3><i class="fas fa-ruble-sign" style="color: var(--success); margin-right: 10px;"></i>Прозрачное ценообразование</h3>
                <ul>
                    <li>Расчёт стоимости онлайн — без скрытых платежей</li>
                    <li>Цена фиксируется после утверждения модели</li>
                    <li>Никаких доплат за «сложность» или «срочность» (если не обсуждалось)</li>
                    <li>Детальная смета с разбивкой по позициям</li>
                </ul>

                <h3><i class="fas fa-undo" style="color: var(--success); margin-right: 10px;"></i>Возврат и обмен</h3>
                <ul>
                    <li>При браке — полный возврат средств или бесплатная перепечать</li>
                    <li>Если изделие не подошло по размеру (наша ошибка) — бесплатная корректировка</li>
                    <li>Сохраняем модели клиентов 1 год для повторных заказов</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials" style="background: var(--bg-secondary); padding: 80px 0;">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Отзывы</span>
                <h2 class="section-title">Что говорят наши клиенты</h2>
            </div>
            <div class="testimonials-slider" id="testimonialsSlider">
                <!-- Testimonials will be loaded dynamically -->
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

    <!-- CTA Section -->
    <section class="content-section">
        <div class="container">
            <div class="content-wrapper" style="text-align: center;">
                <h2>Готовы убедиться в нашем качестве?</h2>
                <p>Закажите пробную печать со скидкой 15% для новых клиентов</p>
                <div class="cta-buttons">
                    <a href="index.php#order-form-section" class="btn-cta-primary">
                        <i class="fas fa-cube"></i>
                        Заказать 3D печать
                    </a>
                    <a href="contact.php" class="btn-cta-secondary">
                        <i class="fas fa-phone"></i>
                        Связаться с нами
                    </a>
                    <a href="<?= $site['telegram'] ?>" target="_blank" class="btn-cta-secondary">
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
