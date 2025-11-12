<?php
// ========================================
// Orders Management Page
// ========================================

define('ADMIN_INIT', true);

require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

Auth::require('/admin/login.php');

$pageTitle = 'Заказы';
$pageScripts = ['/admin/js/modules/orders.js'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Управление заказами</h2>
        <button class="btn btn-primary" id="exportOrdersBtn">
            <i class="fas fa-download"></i>
            Экспорт в CSV
        </button>
    </div>
    
    <div class="filters-bar">
        <div class="filter-group">
            <select class="filter-select" id="statusFilter">
                <option value="all">Все статусы</option>
                <option value="new">Новые</option>
                <option value="processing">В работе</option>
                <option value="completed">Выполнены</option>
                <option value="cancelled">Отменены</option>
            </select>
            
            <select class="filter-select" id="typeFilter">
                <option value="all">Все типы</option>
                <option value="order">Заказы</option>
                <option value="contact">Обращения</option>
            </select>
            
            <input type="text" class="filter-input" id="searchFilter" placeholder="Поиск по имени, email, телефону...">
        </div>
    </div>
    
    <div id="ordersTable">
        <div class="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Загрузка заказов...</p>
        </div>
    </div>
    
    <div id="ordersPagination"></div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
