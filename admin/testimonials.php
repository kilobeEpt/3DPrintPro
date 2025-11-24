<?php
define('ADMIN_INIT', true);
require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
Auth::require('/admin/login.php');

$pageTitle = 'Отзывы';
$pageScripts = ['/admin/js/modules/testimonials.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Управление отзывами</h2>
        <button class="btn btn-primary" id="addTestimonialBtn">
            <i class="fas fa-plus"></i>
            Добавить отзыв
        </button>
    </div>
    
    <div id="testimonialsContainer">
        <div class="loading-state" style="grid-column: 1/-1;">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Загрузка отзывов...</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
