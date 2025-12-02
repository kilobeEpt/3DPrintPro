<?php
// Set page identifiers for includes
$page_meta_key = 'blog';
$canonical_url = 'blog.php';
$active_page = 'blog';

// Load content data
$CONTENT = require __DIR__ . '/data/content.php';
$site = $CONTENT['site'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <?php include __DIR__ . '/includes/head.php'; ?>
</head>
<body data-page="blog">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Page Hero -->
    <section class="page-hero">
        <div class="container">
            <div class="breadcrumbs">
                <a href="index.php">Главная</a>
                <span>/</span>
                <span>Блог</span>
            </div>
            <h1>Блог о FDM, SLA, SLS печати в Омске — статьи и новости</h1>
            <p>Послойная 3D печать, 3D моделирование, 3D сканирование: обзоры, советы и кейсы от экспертов</p>
        </div>
    </section>

    <!-- Blog Intro -->
    <section class="content-section">
        <div class="container">
            <div class="content-wrapper">
                <h2>Добро пожаловать в наш блог</h2>
                <p>
                    Здесь мы делимся опытом послойной 3D печати в Омске: рассказываем о FDM печати, SLA печати, SLS печати, 
                    3D моделировании и 3D сканировании. Публикуем кейсы интересных проектов, обзоры материалов и даём 
                    практические советы по работе с технологиями аддитивного производства.
                </p>
                <p>
                    Подписывайтесь на наш <a href="<?= $site['telegram'] ?>" target="_blank" style="color: var(--primary); font-weight: 600;">Telegram-канал</a>, 
                    чтобы не пропустить новые публикации!
                </p>
            </div>
        </div>
    </section>

    <!-- Blog Grid (Placeholder) -->
    <section style="padding: 80px 0; background: var(--bg-secondary);">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Последние публикации</span>
                <h2 class="section-title">Статьи и новости</h2>
            </div>

            <div class="blog-grid">
                <!-- Placeholder Article 1 -->
                <a href="#" class="blog-card">
                    <img src="https://images.unsplash.com/photo-1581092160562-40aa08e78837?w=600&auto=format&fit=crop" 
                         alt="Сравнение технологий FDM, SLA и SLS 3D печати" 
                         class="blog-image" 
                         loading="lazy" 
                         decoding="async"
                         width="600" 
                         height="400">
                    <div class="blog-content">
                        <span class="blog-date">15 января 2025</span>
                        <h3>FDM vs SLA vs SLS: какую технологию выбрать?</h3>
                        <p class="blog-excerpt">
                            Разбираем плюсы и минусы каждой технологии 3D печати. Для каких задач подходит FDM, 
                            когда нужна SLA, и стоит ли платить за SLS.
                        </p>
                    </div>
                </a>

                <!-- Placeholder Article 2 -->
                <a href="#" class="blog-card">
                    <img src="https://images.unsplash.com/photo-1612837017391-4b6b7b0e3d0a?w=600&auto=format&fit=crop" 
                         alt="Руководство по выбору материалов для 3D печати - PLA, ABS, PETG, TPU" 
                         class="blog-image" 
                         loading="lazy" 
                         decoding="async"
                         width="600" 
                         height="400">
                    <div class="blog-content">
                        <span class="blog-date">10 января 2025</span>
                        <h3>Как выбрать материал для 3D печати</h3>
                        <p class="blog-excerpt">
                            Полный гид по материалам: PLA, ABS, PETG, TPU, нейлон, фотополимеры. Свойства, 
                            применение, преимущества и недостатки.
                        </p>
                    </div>
                </a>

                <!-- Placeholder Article 3 -->
                <a href="#" class="blog-card">
                    <img src="https://images.unsplash.com/photo-1587654780291-39c9404d746b?w=600&auto=format&fit=crop" 
                         alt="Методы постобработки 3D печатных деталей - шлифовка, покраска, химическая обработка" 
                         class="blog-image" 
                         loading="lazy" 
                         decoding="async"
                         width="600" 
                         height="400">
                    <div class="blog-content">
                        <span class="blog-date">5 января 2025</span>
                        <h3>Постобработка 3D печатных деталей</h3>
                        <p class="blog-excerpt">
                            Шлифовка, покраска, химическая обработка — как превратить сырой отпечаток 
                            в профессионально выглядящее изделие.
                        </p>
                    </div>
                </a>

                <!-- Placeholder Article 4 -->
                <a href="#" class="blog-card">
                    <img src="https://images.unsplash.com/photo-1593376893114-1aed528d80cf?w=600&auto=format&fit=crop" 
                         alt="Быстрое прототипирование для стартапов с помощью 3D печати" 
                         class="blog-image" 
                         loading="lazy" 
                         decoding="async"
                         width="600" 
                         height="400">
                    <div class="blog-content">
                        <span class="blog-date">28 декабря 2024</span>
                        <h3>Быстрое прототипирование для стартапов</h3>
                        <p class="blog-excerpt">
                            Как 3D печать помогает сократить время от идеи до готового прототипа. Кейс 
                            омского стартапа в сфере IoT-устройств.
                        </p>
                    </div>
                </a>

                <!-- Placeholder Article 5 -->
                <a href="#" class="blog-card">
                    <img src="https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=600&auto=format&fit=crop" 
                         alt="3D печать архитектурных макетов и прототипов зданий" 
                         class="blog-image" 
                         loading="lazy" 
                         decoding="async"
                         width="600" 
                         height="400">
                    <div class="blog-content">
                        <span class="blog-date">20 декабря 2024</span>
                        <h3>3D печать архитектурных макетов</h3>
                        <p class="blog-excerpt">
                            Преимущества 3D печати для архитекторов. Как мы изготовили макет 120-метрового 
                            жилого комплекса за 5 дней.
                        </p>
                    </div>
                </a>

                <!-- Placeholder Article 6 -->
                <a href="#" class="blog-card">
                    <img src="https://images.unsplash.com/photo-1614741118887-7a4ee193a5fa?w=600&auto=format&fit=crop" 
                         alt="Медицинские модели из 3D печати для хирургического планирования" 
                         class="blog-image" 
                         loading="lazy" 
                         decoding="async"
                         width="600" 
                         height="400">
                    <div class="blog-content">
                        <span class="blog-date">15 декабря 2024</span>
                        <h3>Медицинские модели: от МРТ до 3D печати</h3>
                        <p class="blog-excerpt">
                            Как 3D печать помогает хирургам планировать сложные операции. Пример работы 
                            с омской клиникой нейрохирургии.
                        </p>
                    </div>
                </a>
            </div>

            <div style="text-align: center; margin-top: 50px; padding: 40px; background: var(--bg); border-radius: var(--radius); border: 1px dashed var(--border);">
                <i class="fas fa-newspaper" style="font-size: 3rem; color: var(--primary); opacity: 0.3; margin-bottom: 20px;"></i>
                <p style="color: var(--text-secondary); font-size: 1.1rem; margin-bottom: 20px;">
                    Скоро здесь появятся полные статьи с иллюстрациями, пошаговыми инструкциями и видео
                </p>
                <p style="color: var(--text-secondary);">
                    Подписывайтесь на Telegram, чтобы не пропустить запуск блога
                </p>
                <div class="btn-cta-wrapper">
                    <a href="<?= $site['telegram'] ?>" target="_blank" class="btn-cta-primary">
                        <i class="fab fa-telegram"></i>
                        Подписаться на Telegram
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="content-section">
        <div class="container">
            <div class="content-wrapper" style="text-align: center; max-width: 600px;">
                <h2>Подпишитесь на рассылку</h2>
                <p>Получайте новые статьи, специальные предложения и новости о 3D технологиях</p>
                <form class="subscribe-form" id="subscribeForm" style="max-width: 500px; margin: 30px auto 0;">
                    <input type="email" name="email" placeholder="Ваш email" required style="flex: 1;">
                    <button type="submit"><i class="fas fa-arrow-right"></i></button>
                </form>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="content-section" style="background: var(--bg-secondary);">
        <div class="container">
            <div class="content-wrapper" style="text-align: center;">
                <h2>Есть вопрос о 3D печати?</h2>
                <p>Напишите нам — ответим в блоге или лично в Telegram</p>
                <div class="cta-buttons">
                    <a href="contact.php" class="btn-cta-primary">
                        <i class="fas fa-envelope"></i>
                        Задать вопрос
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
