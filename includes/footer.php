<?php
/**
 * Footer Include
 * 
 * Site footer with links, contact info, and scripts
 */

$content = require __DIR__ . '/../data/content.php';
$site = $content['site'];
$contact = $content['contact'];
$services = $content['services'];
?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <div class="logo">
                        <i class="fas fa-cube"></i>
                        <span><?= htmlspecialchars($site['name']) ?></span>
                    </div>
                    <p><?= htmlspecialchars($site['tagline']) ?> с <?= $site['founded_year'] ?> года</p>
                    <a href="<?= htmlspecialchars($contact['telegram']) ?>" target="_blank" class="btn btn-outline btn-sm" style="margin-top: 15px; text-decoration: none;">
                        <i class="fab fa-telegram"></i>
                        Наш Telegram
                    </a>
                </div>
                <div class="footer-col">
                    <h4>Услуги</h4>
                    <ul id="footerServices">
                        <?php foreach (array_slice($services, 0, 4) as $service): ?>
                        <li><a href="/services.php#<?= htmlspecialchars($service['slug']) ?>"><?= htmlspecialchars($service['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Компания</h4>
                    <ul>
                        <li><a href="/about.html">О нас</a></li>
                        <li><a href="/portfolio.php">Портфолио</a></li>
                        <li><a href="/why-us.html">Почему мы</a></li>
                        <li><a href="/districts.html">Районы доставки</a></li>
                        <li><a href="/contact.php">Контакты</a></li>
                        <li><a href="/blog.html">Блог</a></li>
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
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($site['name']) ?>. <?= htmlspecialchars($contact['address']['city']) ?>, Россия. Все права защищены.</p>
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
    <script src="/js/utils.js"></script>
    <script src="/js/validators.js"></script>
    <script src="/js/calculator.js"></script>
    <script src="/js/telegram.js"></script>
    <script src="/js/main.js"></script>
</body>
</html>
