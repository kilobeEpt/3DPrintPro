<?php
/**
 * Header Include
 * 
 * Site header with navigation
 * 
 * Variables expected:
 * - $active_page: Current page identifier for active nav link (optional)
 */

$content = require __DIR__ . '/../data/content.php';
$site = $content['site'];

$active_page = $active_page ?? '';
?>

    <!-- Header -->
    <header class="header" id="header">
        <nav class="navbar">
            <a href="/index.php" class="logo" style="text-decoration: none; color: inherit;">
                <i class="fas fa-cube"></i>
                <span id="siteName"><?= htmlspecialchars($site['name']) ?></span>
            </a>
            <ul class="nav-menu" id="navMenu">
                <li><a href="/index.php" class="nav-link<?= $active_page === 'home' ? ' active' : '' ?>" data-page="home">Главная</a></li>
                <li><a href="/services.php" class="nav-link<?= $active_page === 'services' ? ' active' : '' ?>" data-page="services">Услуги</a></li>
                <li><a href="/index.php#calculator" class="nav-link">Калькулятор</a></li>
                <li><a href="/portfolio.php" class="nav-link<?= $active_page === 'portfolio' ? ' active' : '' ?>" data-page="portfolio">Портфолио</a></li>
                <li><a href="/about.html" class="nav-link<?= $active_page === 'about' ? ' active' : '' ?>" data-page="about">О нас</a></li>
                <li><a href="/contact.php" class="nav-link<?= $active_page === 'contact' ? ' active' : '' ?>" data-page="contact">Контакты</a></li>
                <li><a href="/blog.html" class="nav-link<?= $active_page === 'blog' ? ' active' : '' ?>" data-page="blog">Блог</a></li>
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
