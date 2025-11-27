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
                <a href="<?= $site['telegram'] ?>" target="_blank" class="btn btn-outline btn-sm" style="margin-top: 15px; text-decoration: none;">
                    <i class="fab fa-telegram"></i>
                    Наш Telegram
                </a>
            </div>
            <div class="footer-col">
                <h4>Услуги</h4>
                <ul id="footerServices">
                    <?php foreach (array_slice($services, 0, 4) as $service): ?>
                    <li><a href="services.php#<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?></a></li>
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

<div class="modal" id="portfolioModal">
    <div class="modal-content modal-large">
        <button class="modal-close" onclick="closeModal('portfolioModal')">&times;</button>
        <div id="portfolioModalContent"></div>
    </div>
</div>

<!-- Scripts -->
<script src="js/utils.js"></script>
<script src="js/validators.js"></script>
<script src="js/order-form.js"></script>
<script src="js/main.js"></script>
