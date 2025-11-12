<?php
define('ADMIN_INIT', true);
require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
Auth::require('/admin/login.php');

$pageTitle = 'Портфолио';
$pageScripts = ['/admin/js/modules/portfolio.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Управление портфолио</h2>
        <button class="btn btn-primary" id="addPortfolioBtn">
            <i class="fas fa-plus"></i>
            Добавить работу
        </button>
    </div>
    
    <div id="portfolioContainer">
        <div class="loading-state" style="grid-column: 1/-1;">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Загрузка портфолио...</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
