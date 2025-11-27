<?php
// Set page identifiers for includes
$page_meta_key = 'districts';
$canonical_url = 'districts.php';
$active_page = 'districts';

// Load content data
$CONTENT = require __DIR__ . '/data/content.php';
$site = $CONTENT['site'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body data-page="districts">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Page Hero -->
    <section class="page-hero text-center">
        <div class="container">
            <h1>3D печать по районам Омска</h1>
            <p>Работаем со всеми округами — доставка готовых изделий в удобное для вас время</p>
        </div>
    </section>

    <!-- Breadcrumbs -->
    <nav class="breadcrumbs">
        <div class="container">
            <ul>
                <li><a href="index.php">Главная</a></li>
                <li>Районы обслуживания</li>
            </ul>
        </div>
    </nav>

    <!-- Intro -->
    <section class="content-section">
        <div class="container">
            <div class="content-wrapper">
                <h2>Доставка 3D печати по всему Омску</h2>
                <p>
                    Наша мастерская находится в Центральном округе, но мы работаем с клиентами из всех 
                    районов Омска и Омской области. Доставим готовые изделия курьером, почтой или 
                    транспортной компанией — выбирайте удобный вариант.
                </p>
                <p>
                    <strong>Бесплатная курьерская доставка</strong> по Омску при заказе от 3000₽. 
                    Для заказов меньше этой суммы стоимость доставки — от 200₽ в зависимости от района.
                </p>
            </div>
        </div>
    </section>

    <!-- Districts -->
    <section class="content-section" style="background: var(--bg-secondary);">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Районы Омска</span>
                <h2 class="section-title">Обслуживаемые округа</h2>
            </div>

            <div class="district-card">
                <h3><i class="fas fa-map-marker-alt"></i>Центральный округ</h3>
                <p>
                    Наша мастерская расположена в самом сердце Омска — на ул. Ленина, д. 15. Если вы живёте 
                    или работаете в Центральном округе, можете забрать заказ самостоятельно или заказать 
                    курьерскую доставку (30-60 минут).
                </p>
                <ul>
                    <li><i class="fas fa-check"></i> Самовывоз: бесплатно, в рабочее время</li>
                    <li><i class="fas fa-check"></i> Курьер: от 150₽, доставка 30-60 минут</li>
                    <li><i class="fas fa-check"></i> Ключевые районы: исторический центр, Любинский проспект, пл. Ленина</li>
                </ul>
            </div>

            <div class="district-card">
                <h3><i class="fas fa-map-marker-alt"></i>Советский округ</h3>
                <p>
                    Один из крупнейших округов Омска. Доставляем в любую точку района — от микрорайонов 
                    на севере до южных кварталов. Среднее время доставки — 1-2 часа в зависимости от загруженности дорог.
                </p>
                <ul>
                    <li><i class="fas fa-check"></i> Курьер: от 200₽, доставка 1-2 часа</li>
                    <li><i class="fas fa-check"></i> Бесплатно при заказе от 3000₽</li>
                    <li><i class="fas fa-check"></i> Популярные микрорайоны: Северный, Первомайский, Амурский</li>
                </ul>
            </div>

            <div class="district-card">
                <h3><i class="fas fa-map-marker-alt"></i>Кировский округ</h3>
                <p>
                    Доставка в Кировский округ осуществляется ежедневно. Охватываем как старые жилые массивы, 
                    так и новые микрорайоны. Возможна доставка в выходные дни по предварительной договорённости.
                </p>
                <ul>
                    <li><i class="fas fa-check"></i> Курьер: от 250₽, доставка 1.5-2 часа</li>
                    <li><i class="fas fa-check"></i> Бесплатно при заказе от 3000₽</li>
                    <li><i class="fas fa-check"></i> Районы: Нефтяники, микрорайоны Кировского АО</li>
                </ul>
            </div>

            <div class="district-card">
                <h3><i class="fas fa-map-marker-alt"></i>Ленинский округ</h3>
                <p>
                    Работаем с клиентами из Ленинского округа, включая отдалённые микрорайоны. Доставка 
                    организуется курьером или можно забрать из нашей мастерской в центре (20-30 минут на авто).
                </p>
                <ul>
                    <li><i class="fas fa-check"></i> Курьер: от 250₽, доставка 1.5-2.5 часа</li>
                    <li><i class="fas fa-check"></i> Бесплатно при заказе от 3000₽</li>
                    <li><i class="fas fa-check"></i> Районы: Левобережье, Старый Кировск, микрорайоны ЛАО</li>
                </ul>
            </div>

            <div class="district-card">
                <h3><i class="fas fa-map-marker-alt"></i>Октябрьский округ</h3>
                <p>
                    Обслуживаем промышленные предприятия и частных клиентов Октябрьского округа. Доставка 
                    в рабочие дни, возможна срочная доставка в течение 2-3 часов при необходимости.
                </p>
                <ul>
                    <li><i class="fas fa-check"></i> Курьер: от 250₽, доставка 2-3 часа</li>
                    <li><i class="fas fa-check"></i> Бесплатно при заказе от 3000₽</li>
                    <li><i class="fas fa-check"></i> Районы: промзоны, жилые микрорайоны ОАО</li>
                </ul>
            </div>

            <div class="district-card">
                <h3><i class="fas fa-map-marker-alt"></i>Омская область</h3>
                <p>
                    Для клиентов из пригородов и других населённых пунктов Омской области предлагаем доставку 
                    Почтой России или транспортными компаниями (СДЭК, ПЭК, Деловые Линии). Упакуем изделие надёжно.
                </p>
                <ul>
                    <li><i class="fas fa-check"></i> Почта России: 5-14 дней, стоимость зависит от веса</li>
                    <li><i class="fas fa-check"></i> СДЭК/ПЭК: 2-5 дней, от 300₽</li>
                    <li><i class="fas fa-check"></i> Города: Исилькуль, Калачинск, Тара, Называевск и др.</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Delivery Options -->
    <section class="content-section">
        <div class="container">
            <div class="content-wrapper">
                <h2>Варианты доставки</h2>

                <h3><i class="fas fa-shipping-fast" style="color: var(--primary); margin-right: 10px;"></i>Курьерская доставка по Омску</h3>
                <p>
                    Самый быстрый способ получить ваше изделие. Курьер привезёт заказ по указанному адресу 
                    в удобное для вас время. Оплата наличными или картой при получении.
                </p>
                <ul>
                    <li>Доставка в течение 2-4 часов после готовности заказа</li>
                    <li>Бесплатно при заказе от 3000₽</li>
                    <li>Стоимость: от 150₽ (центр) до 300₽ (окраины)</li>
                    <li>Доставка по выходным — по договорённости</li>
                </ul>

                <h3><i class="fas fa-store" style="color: var(--primary); margin-right: 10px;"></i>Самовывоз</h3>
                <p>
                    Забрать заказ можно самостоятельно из нашей мастерской по адресу: г. Омск, ул. Ленина, д. 15. 
                    Работаем Пн-Пт: 9:00-18:00. Самовывоз всегда бесплатный.
                </p>
                <ul>
                    <li>Бесплатно для всех заказов</li>
                    <li>Удобная парковка рядом</li>
                    <li>Возможность осмотреть изделие перед оплатой</li>
                    <li>Получение в день готовности (по SMS-уведомлению)</li>
                </ul>

                <h3><i class="fas fa-box" style="color: var(--primary); margin-right: 10px;"></i>Почта и транспортные компании</h3>
                <p>
                    Для клиентов из Омской области и других регионов России предлагаем доставку через 
                    проверенные логистические сервисы.
                </p>
                <ul>
                    <li><strong>Почта России:</strong> 5-14 дней, недорого, с трек-номером</li>
                    <li><strong>СДЭК:</strong> 2-5 дней, пункты выдачи по всей стране, от 300₽</li>
                    <li><strong>ПЭК, Деловые Линии:</strong> для крупногабаритных заказов</li>
                    <li>Надёжная упаковка в крафт-бокс с защитным материалом</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="content-section" style="background: var(--bg-secondary);">
        <div class="container">
            <div class="content-wrapper" style="text-align: center;">
                <h2>Закажите 3D печать с доставкой</h2>
                <p>Рассчитайте стоимость онлайн или свяжитесь с нами для консультации</p>
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
