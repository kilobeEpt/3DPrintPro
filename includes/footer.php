<?php
// $site and $CONTENT are already loaded from head.php
$services = $CONTENT['services'];
?>
<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-col">
                <div class="logo">
                    <i class="fas fa-cube"></i>
                    <span><?= $site['name'] ?></span>
                </div>
                <p>Профессиональная 3D печать в <?= $site['city'] ?> с <?= $site['year_founded'] ?> года</p>
                <div class="btn-cta-wrapper">
                    <a href="<?= $site['telegram'] ?>" target="_blank" rel="noopener" class="btn-cta-secondary btn-sm">
                        <i class="fab fa-telegram"></i>
                        <span>Наш Telegram</span>
                    </a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Услуги</h4>
                <ul id="footerServices">
                    <?php foreach (array_slice($services, 0, 4) as $service): ?>
                    <li><a href="services.php#<?= $service['slug'] ?>"><?= htmlspecialchars($service['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Компания</h4>
                <ul>
                    <li><a href="about.php">О нас</a></li>
                    <li><a href="portfolio.php">Портфолио</a></li>
                    <li><a href="why-us.php">Почему мы</a></li>
                    <li><a href="districts.php">Районы доставки</a></li>
                    <li><a href="contact.php">Контакты</a></li>
                    <li><a href="blog.php">Блог</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Подписка</h4>
                <p>Получайте новости и спецпредложения</p>
                <form class="subscribe-form" id="subscribeForm">
                    <input type="email" name="email" placeholder="Ваш email" required>
                    <button type="submit"><i class="fas fa-arrow-right"></i></button>
                </form>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= $site['name'] ?>. <?= $site['city'] ?>, <?= $site['country'] ?>. Все права защищены.</p>
        </div>
    </div>
</footer>

<!-- Modal Windows -->
<div class="modal" id="serviceModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('serviceModal')">&times;</button>
        <div id="serviceModalContent"></div>
    </div>
</div>

<div class="modal portfolio-modal" id="portfolioModal" role="dialog" aria-modal="true" aria-labelledby="portfolioModalTitle">
    <div class="modal-backdrop"></div>
    <div class="modal-content portfolio-modal-content">
        <button class="modal-close portfolio-modal-close" aria-label="Закрыть модальное окно">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
        
        <div class="portfolio-modal-body">
            <div class="portfolio-modal-image-wrapper">
                <img src="" alt="" id="portfolioModalImage" class="portfolio-modal-image">
                <button class="portfolio-nav-btn portfolio-nav-prev" aria-label="Предыдущий проект">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                </button>
                <button class="portfolio-nav-btn portfolio-nav-next" aria-label="Следующий проект">
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
            
            <div class="portfolio-modal-info">
                <div class="portfolio-modal-counter" id="portfolioModalCounter">1 / 6</div>
                <h2 id="portfolioModalTitle" class="portfolio-modal-title">Заголовок проекта</h2>
                <div class="portfolio-modal-meta">
                    <span class="portfolio-modal-tech">
                        <i class="fas fa-cog" aria-hidden="true"></i>
                        <span id="portfolioModalTech">Технология</span>
                    </span>
                    <span class="portfolio-modal-time">
                        <i class="fas fa-clock" aria-hidden="true"></i>
                        <span id="portfolioModalTime">Время выполнения</span>
                    </span>
                </div>
                <p id="portfolioModalDescription" class="portfolio-modal-description">Описание проекта</p>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="js/utils.js"></script>
<script src="js/validators.js"></script>
<script src="js/order-form.js"></script>
<script src="js/main.js"></script>
<script src="js/portfolio-gallery.js"></script>
