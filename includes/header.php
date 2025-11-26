<?php
// $site and $CONTENT are already loaded from head.php
?>
<!-- Preloader -->
<div class="preloader" id="preloader">
    <div class="loader">
        <div class="cube">
            <div class="face front"></div>
            <div class="face back"></div>
            <div class="face right"></div>
            <div class="face left"></div>
            <div class="face top"></div>
            <div class="face bottom"></div>
        </div>
    </div>
</div>

<!-- Header -->
<header class="header" id="header">
    <nav class="navbar">
        <a href="index.php" class="logo" style="text-decoration: none; color: inherit;">
            <i class="fas fa-cube"></i>
            <span id="siteName"><?= $site['name'] ?></span>
        </a>
        <ul class="nav-menu" id="navMenu">
            <li><a href="index.php" class="nav-link <?= $active_page === 'home' ? 'active' : '' ?>" data-page="home">Главная</a></li>
            <li><a href="services.php" class="nav-link <?= $active_page === 'services' ? 'active' : '' ?>" data-page="services">Услуги</a></li>
            <li><a href="index.php#order-form-section" class="nav-link">Заказать</a></li>
            <li><a href="portfolio.php" class="nav-link <?= $active_page === 'portfolio' ? 'active' : '' ?>" data-page="portfolio">Портфолио</a></li>
            <li><a href="about.html" class="nav-link <?= $active_page === 'about' ? 'active' : '' ?>" data-page="about">О нас</a></li>
            <li><a href="contact.php" class="nav-link <?= $active_page === 'contact' ? 'active' : '' ?>" data-page="contact">Контакты</a></li>
            <li><a href="blog.html" class="nav-link <?= $active_page === 'blog' ? 'active' : '' ?>" data-page="blog">Блог</a></li>
        </ul>
        <div class="nav-actions">
            <button class="theme-toggle" id="themeToggle" title="Переключить тему">
                <i class="fas fa-moon"></i>
            </button>
            <button class="hamburger" id="hamburger" aria-label="Меню">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>
</header>
