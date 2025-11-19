<?php
if (!defined('ADMIN_INIT')) {
    die('Direct access not permitted');
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Check if user is super admin
$isSuperAdmin = false;
try {
    if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
        require_once __DIR__ . '/../../vendor/autoload.php';
        require_once __DIR__ . '/../../bootstrap/eloquent.php';
        
        $authService = new \App\Services\AdminAuthService();
        $sessionId = session_id();
        $validation = $authService->validateSession($sessionId);
        
        if ($validation['valid'] && $validation['user']->isSuperAdmin()) {
            $isSuperAdmin = true;
        }
    }
} catch (Exception $e) {
    // Silently fail - user just won't see super admin features
}
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <i class="fas fa-cube"></i>
        <span>3D Print Pro</span>
    </div>
    
    <nav class="sidebar-nav">
        <a href="/admin/index.php" class="nav-item <?php echo $currentPage === 'index' ? 'active' : ''; ?>" data-page="dashboard">
            <i class="fas fa-chart-line"></i>
            <span>Дашборд</span>
        </a>
        <a href="/admin/orders.php" class="nav-item <?php echo $currentPage === 'orders' ? 'active' : ''; ?>" data-page="orders">
            <i class="fas fa-shopping-cart"></i>
            <span>Заказы</span>
            <span class="badge" id="ordersBadge" style="display: none;">0</span>
        </a>
        <a href="/admin/services.php" class="nav-item <?php echo $currentPage === 'services' ? 'active' : ''; ?>" data-page="services">
            <i class="fas fa-cogs"></i>
            <span>Услуги</span>
        </a>
        <a href="/admin/portfolio.php" class="nav-item <?php echo $currentPage === 'portfolio' ? 'active' : ''; ?>" data-page="portfolio">
            <i class="fas fa-images"></i>
            <span>Портфолио</span>
        </a>
        <a href="/admin/testimonials.php" class="nav-item <?php echo $currentPage === 'testimonials' ? 'active' : ''; ?>" data-page="testimonials">
            <i class="fas fa-comments"></i>
            <span>Отзывы</span>
        </a>
        <a href="/admin/faq.php" class="nav-item <?php echo $currentPage === 'faq' ? 'active' : ''; ?>" data-page="faq">
            <i class="fas fa-question-circle"></i>
            <span>FAQ</span>
        </a>
        <a href="/admin/content.php" class="nav-item <?php echo $currentPage === 'content' ? 'active' : ''; ?>" data-page="content">
            <i class="fas fa-file-alt"></i>
            <span>Контент</span>
        </a>
        <a href="/admin/forms.php" class="nav-item <?php echo $currentPage === 'forms' ? 'active' : ''; ?>" data-page="forms">
            <i class="fas fa-wpforms"></i>
            <span>Формы</span>
        </a>
        <a href="/admin/submissions.php" class="nav-item <?php echo $currentPage === 'submissions' ? 'active' : ''; ?>" data-page="submissions">
            <i class="fas fa-inbox"></i>
            <span>Заявки</span>
            <span class="badge" id="submissionsBadge" style="display: none;">0</span>
        </a>
        <a href="/admin/settings.php" class="nav-item <?php echo $currentPage === 'settings' ? 'active' : ''; ?>" data-page="settings">
            <i class="fas fa-sliders-h"></i>
            <span>Настройки</span>
        </a>
        <?php if ($isSuperAdmin): ?>
        <a href="/admin/users.php" class="nav-item <?php echo $currentPage === 'users' ? 'active' : ''; ?>" data-page="users">
            <i class="fas fa-users"></i>
            <span>Пользователи</span>
        </a>
        <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <div>
                <strong><?php echo htmlspecialchars(Auth::user(), ENT_QUOTES, 'UTF-8'); ?></strong>
                <small>Администратор</small>
            </div>
        </div>
        <a href="/admin/logout.php" class="btn btn-outline btn-block btn-sm">
            <i class="fas fa-sign-out-alt"></i>
            Выход
        </a>
    </div>
</aside>
