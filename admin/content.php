<?php
define('ADMIN_INIT', true);
require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
Auth::require('/admin/login.php');

$pageTitle = 'Контент';
$pageScripts = ['/admin/js/modules/content.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Управление контентом</h2>
        <button class="btn btn-primary" id="addContentBtn">
            <i class="fas fa-plus"></i>
            Добавить блок
        </button>
    </div>
    
    <div id="contentContainer">
        <div class="loading-state" style="grid-column: 1/-1;">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Загрузка контента...</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
