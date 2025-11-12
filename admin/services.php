<?php
define('ADMIN_INIT', true);
require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
Auth::require('/admin/login.php');

$pageTitle = 'Услуги';
$pageScripts = ['/admin/js/modules/services.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Управление услугами</h2>
        <button class="btn btn-primary" id="addServiceBtn">
            <i class="fas fa-plus"></i>
            Добавить услугу
        </button>
    </div>
    
    <div id="servicesContainer">
        <div class="loading-state" style="grid-column: 1/-1;">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Загрузка услуг...</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
