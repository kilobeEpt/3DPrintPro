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
    <section class="page-hero">
        <div class="container">
            <div class="breadcrumbs">
                <a href="index.php">Главная</a>
                <span>/</span>
                <span>Районы обслуживания</span>
            </div>
            <h1>FDM, SLA, SLS печать по районам Омска — доставка изделий</h1>
            <p>Послойная 3D печать, 3D моделирование и сканирование с доставкой по всем округам Омска</p>
        </div>
    </section>

    <!-- Intro -->
    <section class="content-section">
        <div class="container">
            <div class="content-wrapper">
                <h2>Доставка 3D печати по всему Омску</h2>
                <p>
                    Наша мастерская послойной 3D печати находится в Центральном округе Омска, но мы работаем с клиентами из всех 
                    районов города и Омской области. Предлагаем FDM печать, SLA печать, SLS печать, 3D моделирование и 3D сканирование. 
                    Доставим готовые изделия курьером, почтой или транспортной компанией — выбирайте удобный вариант.
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
                <h2 class="section-title">Обслуживаемые округа — услуги 3D печати</h2>
            </div>

            <?php foreach ($site['service_areas'] as $area): ?>
            <div class="district-card" itemscope itemtype="https://schema.org/ServiceArea">
                <h3><i class="fas fa-map-marker-alt"></i><span itemprop="name"><?= $area['name'] ?></span></h3>
                <p itemprop="description">
                    <?= $area['description'] ?>
                </p>
                <ul>
                    <?php if ($area['name'] === 'Центральный округ'): ?>
                    <li><i class="fas fa-check"></i> Самовывоз: бесплатно, в рабочее время</li>
                    <?php endif; ?>
                    <li><i class="fas fa-check"></i> Курьер: <?= $area['delivery_cost'] ?>, доставка <?= $area['delivery_time'] ?></li>
                    <li><i class="fas fa-check"></i> Бесплатная доставка при заказе от <?= number_format($area['free_delivery_threshold'], 0, ',', ' ') ?>₽</li>
                    <li><i class="fas fa-check"></i> Все технологии: FDM, SLA, SLS печать в Омске</li>
                </ul>
                <meta itemprop="deliveryLeadTime" content="<?= $area['delivery_time'] ?>">
            </div>
            <?php endforeach; ?>
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

    <!-- Delivery FAQ Section -->
    <section class="faq" id="delivery-faq" style="background: var(--bg-secondary);">
        <div class="container">
            <div class="section-header">
                <span class="section-label">FAQ о доставке</span>
                <h2 class="section-title">Вопросы о доставке 3D печати в Омске</h2>
                <p class="section-description">
                    Ответы на частые вопросы о сроках и стоимости доставки по Омску
                </p>
            </div>
            <div class="faq-container">
                <div class="faq-item active">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <h3>Какие сроки доставки 3D печати по районам Омска?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer" style="display: block;">
                        <p>Сроки зависят от района: Центральный округ — 30-60 минут, Советский округ — 1-2 часа, Кировский округ — 1.5-2 часа, Ленинский округ — 1.5-2.5 часа, Октябрьский округ — 2-3 часа. Доставка осуществляется после готовности заказа. Вы получите SMS-уведомление, когда изделие будет готово к отправке.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <h3>Сколько стоит доставка 3D печати по Омску?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Стоимость курьерской доставки по Омску: Центральный округ — от 150₽, остальные округа — от 200-250₽. Бесплатная доставка при заказе от 3000₽ во все районы Омска. Самовывоз из нашей мастерской на ул. Ленина, 15 всегда бесплатный.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <h3>Доставляете ли вы в выходные дни?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Доставка в выходные дни возможна по предварительной договорённости. Свяжитесь с нами по телефону +7 (999) 123-45-67 или через Telegram @PrintPro_Omsk для согласования времени. Самовывоз в выходные дни не осуществляется (мастерская работает Пн-Пт).</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <h3>Как отправить 3D печать в другой город Омской области?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Для отправки в города Омской области (Исилькуль, Калачинск, Тара и др.) используем Почту России (5-14 дней, от 300₽) или транспортные компании СДЭК/ПЭК (2-5 дней, от 400₽). Изделия надёжно упаковываются в крафт-бокс с защитным материалом. Предоставляем трек-номер для отслеживания.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <h3>Какие технологии 3D печати доступны во всех районах Омска?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Все технологии послойной 3D печати доступны для клиентов из любых районов Омска: FDM печать термопластиками (PLA, ABS, PETG, TPU, Nylon), SLA фотополимерная печать для высокой детализации, SLS лазерное спекание нейлона. Также предлагаем 3D моделирование, 3D сканирование и постобработку изделий с доставкой курьером.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="content-section">
        <div class="container">
            <div class="content-wrapper" style="text-align: center;">
                <h2>Закажите 3D печать с доставкой в Омске</h2>
                <p>Рассчитайте стоимость онлайн или свяжитесь с нами для консультации</p>
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

    <!-- Districts Page Specific JSON-LD Schema -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "Какие сроки доставки 3D печати по районам Омска?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Сроки зависят от района: Центральный округ — 30-60 минут, Советский округ — 1-2 часа, Кировский округ — 1.5-2 часа, Ленинский округ — 1.5-2.5 часа, Октябрьский округ — 2-3 часа. Доставка осуществляется после готовности заказа."
          }
        },
        {
          "@type": "Question",
          "name": "Сколько стоит доставка 3D печати по Омску?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Стоимость курьерской доставки по Омску: Центральный округ — от 150₽, остальные округа — от 200-250₽. Бесплатная доставка при заказе от 3000₽ во все районы Омска."
          }
        },
        {
          "@type": "Question",
          "name": "Доставляете ли вы в выходные дни?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Доставка в выходные дни возможна по предварительной договорённости. Свяжитесь с нами по телефону +7 (999) 123-45-67 или через Telegram."
          }
        },
        {
          "@type": "Question",
          "name": "Как отправить 3D печать в другой город Омской области?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Для отправки в города Омской области используем Почту России (5-14 дней, от 300₽) или транспортные компании СДЭК/ПЭК (2-5 дней, от 400₽)."
          }
        },
        {
          "@type": "Question",
          "name": "Какие технологии 3D печати доступны во всех районах Омска?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Все технологии послойной 3D печати доступны для клиентов из любых районов Омска: FDM печать термопластиками, SLA фотополимерная печать, SLS лазерное спекание нейлона, 3D моделирование, 3D сканирование и постобработку изделий."
          }
        }
      ]
    }
    </script>

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Service",
      "serviceType": "3D печать с доставкой",
      "name": "Доставка 3D печати по Омску",
      "description": "Курьерская доставка услуг послойной 3D печати FDM, SLA, SLS по всем округам Омска: Центральный, Советский, Кировский, Ленинский, Октябрьский. Бесплатная доставка от 3000₽.",
      "provider": {
        "@id": "<?= $site['url'] ?>/#organization"
      },
      "areaServed": [
        <?php foreach ($site['service_areas'] as $index => $area): ?>
        {
          "@type": "City",
          "name": "<?= htmlspecialchars($area['name'], ENT_QUOTES) ?>, Омск"
        }<?= $index < count($site['service_areas']) - 1 ? ',' : '' ?>
        
        <?php endforeach; ?>
      ],
      "offers": {
        "@type": "Offer",
        "priceCurrency": "RUB",
        "price": "150",
        "priceSpecification": [
          <?php foreach ($site['service_areas'] as $index => $area): ?>
          {
            "@type": "DeliveryChargeSpecification",
            "appliesToDeliveryMethod": "http://purl.org/goodrelations/v1#DeliveryModeCourier",
            "eligibleRegion": {
              "@type": "City",
              "name": "<?= htmlspecialchars($area['name'], ENT_QUOTES) ?>, Омск"
            },
            "price": "<?= preg_replace('/[^0-9]/', '', explode('-', $area['delivery_cost'])[0]) ?>",
            "priceCurrency": "RUB",
            "deliveryLeadTime": {
              "@type": "QuantitativeValue",
              "minValue": "<?= preg_replace('/[^0-9]/', '', explode('-', $area['delivery_time'])[0]) ?>",
              "maxValue": "<?= preg_replace('/[^0-9]/', '', explode('-', $area['delivery_time'])[count(explode('-', $area['delivery_time'])) - 1]) ?>",
              "unitCode": "MIN"
            }
          }<?= $index < count($site['service_areas']) - 1 ? ',' : '' ?>
          
          <?php endforeach; ?>
        ]
      }
    }
    </script>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
