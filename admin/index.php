<?php
// ========================================
// Admin Dashboard - Main Page
// ========================================

define('ADMIN_INIT', true);

require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// Require authentication
Auth::require('/admin/login.php');

// Page configuration
$pageTitle = 'Дашборд';
$pageScripts = ['/admin/js/modules/dashboard.js'];

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<!-- Dashboard Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-info">
            <h3 id="statTotalOrders">0</h3>
            <p>Всего заказов</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
            <i class="fas fa-ruble-sign"></i>
        </div>
        <div class="stat-info">
            <h3 id="statMonthRevenue">₽0</h3>
            <p>Доход за месяц</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3 id="statTotalClients">0</h3>
            <p>Клиентов</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a, #fee140);">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <h3 id="statProcessing">0</h3>
            <p>В обработке</p>
        </div>
    </div>
</div>

<!-- Dashboard Content -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
    <div class="card">
        <div class="card-header">
            <h3>График заказов</h3>
        </div>
        <div class="chart-container">
            <canvas id="ordersChart"></canvas>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Последние заказы</h3>
            <a href="/admin/orders.php" style="color: var(--admin-primary); text-decoration: none; font-size: 14px;">
                Все заказы <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div id="recentOrdersList">
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Загрузка...</p>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once __DIR__ . '/includes/footer.php';
?>
