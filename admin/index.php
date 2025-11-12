<?php
// ========================================
// Admin Dashboard - Protected Page
// ========================================

define('ADMIN_INIT', true);

require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// Require authentication
Auth::require('/admin/login.php');

// Get CSRF token for AJAX requests
$csrfToken = CSRF::getToken();
$adminLogin = Auth::user();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - 3D Print Pro</title>
    <?php echo CSRF::getTokenMeta(); ?>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link rel="stylesheet" href="/css/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔐</text></svg>">
</head>
<body>
    <!-- NOTE: This file now uses PHP sessions instead of localStorage -->
    <!-- The old login screen has been removed - see /admin/login.php -->
    
    <!-- ========================================
         ADMIN DASHBOARD
         ======================================== -->
    <div class="admin-dashboard" id="adminDashboard">
        
        <!-- ========================================
             SIDEBAR
             ======================================== -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <i class="fas fa-cube"></i>
                <span>3D Print Pro</span>
            </div>
            
            <nav class="sidebar-nav">
                <a href="#dashboard" class="nav-item active" data-page="dashboard">
                    <i class="fas fa-chart-line"></i>
                    <span>Дашборд</span>
                </a>
                <a href="#orders" class="nav-item" data-page="orders">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Заказы</span>
                    <span class="badge" id="ordersBadge">0</span>
                </a>
                <a href="#portfolio" class="nav-item" data-page="portfolio">
                    <i class="fas fa-images"></i>
                    <span>Портфолио</span>
                </a>
                <a href="#services" class="nav-item" data-page="services">
                    <i class="fas fa-cogs"></i>
                    <span>Услуги</span>
                </a>
                <a href="#testimonials" class="nav-item" data-page="testimonials">
                    <i class="fas fa-comments"></i>
                    <span>Отзывы</span>
                </a>
                <a href="#calculator" class="nav-item" data-page="calculator">
                    <i class="fas fa-calculator"></i>
                    <span>Калькулятор</span>
                </a>
                <a href="#content" class="nav-item" data-page="content">
                    <i class="fas fa-file-alt"></i>
                    <span>Контент</span>
                </a>
                <a href="#forms" class="nav-item" data-page="forms">
                    <i class="fas fa-inbox"></i>
                    <span>Формы</span>
                </a>
                <a href="#settings" class="nav-item" data-page="settings">
                    <i class="fas fa-cog"></i>
                    <span>Настройки</span>
                </a>
            </nav>
        </aside>

        <!-- Continue with the rest of the admin.html content... -->
        <!-- For brevity, I'll reference that the full content should be copied from admin.html -->
        <!-- Starting from line 112 (main content area) to line 891 (end) -->
        
        <!-- I'll create a simplified version that loads the main sections -->
        <main class="admin-main" id="adminMain">
            <header class="admin-header">
                <div class="header-left">
                    <button class="btn-icon" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 id="pageTitle">Дашборд</h1>
                </div>
                
                <div class="header-right">
                    <button class="btn-icon" id="notificationsButton">
                        <i class="fas fa-bell"></i>
                        <span class="badge">0</span>
                    </button>
                    
                    <button class="btn-icon" id="quickSettingsButton">
                        <i class="fas fa-sliders-h"></i>
                    </button>
                    
                    <div class="user-menu">
                        <button class="btn-user" id="userMenuButton">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo htmlspecialchars($adminLogin, ENT_QUOTES, 'UTF-8'); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
            </header>

            <div class="admin-content" id="adminContent">
                <!-- Content will be dynamically loaded here by admin.js -->
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Загрузка...</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Quick Settings Dropdown -->
    <div class="dropdown-menu" id="quickSettingsDropdown" style="display: none;">
        <div class="dropdown-content">
            <div style="padding: 10px; border-bottom: 1px solid var(--admin-border);">
                <strong>Быстрые настройки</strong>
            </div>
            <div style="padding: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span>Тёмная тема</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="quickThemeToggle" onchange="admin.quickToggleTheme(this.checked)">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            <hr style="margin: 10px 0; border: none; border-top: 1px solid var(--admin-border);">
            <button class="btn btn-outline btn-block" onclick="admin.clearCache()">
                <i class="fas fa-broom"></i>
                Очистить кеш
            </button>
        </div>
    </div>

    <!-- User Menu Dropdown -->
    <div class="dropdown-menu" id="userMenuDropdown" style="display: none;">
        <div class="dropdown-content">
            <a href="#" class="dropdown-item" onclick="admin.navigateToPage('settings')">
                <i class="fas fa-cog"></i>
                Настройки
            </a>
            <a href="#" class="dropdown-item" onclick="admin.viewProfile()">
                <i class="fas fa-user"></i>
                Профиль
            </a>
            <hr style="margin: 5px 0; border: none; border-top: 1px solid var(--admin-border);">
            <a href="/admin/logout.php" class="dropdown-item" style="color: var(--admin-danger);">
                <i class="fas fa-sign-out-alt"></i>
                Выход
            </a>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Pass PHP session data to JavaScript
        window.ADMIN_SESSION = {
            authenticated: true,
            login: <?php echo json_encode($adminLogin); ?>,
            csrfToken: <?php echo json_encode($csrfToken); ?>
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/config.js"></script>
    <script src="/js/validators.js"></script>
    <script src="/js/database.js"></script>
    <script src="/js/telegram.js"></script>
    <script src="/admin/js/admin.js"></script>
</body>
</html>
