
<?php
if (!defined('ADMIN_INIT')) {
    die('Direct access not permitted');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Админ-панель', ENT_QUOTES, 'UTF-8'); ?> - 3D Print Pro</title>
    <?php echo CSRF::getTokenMeta(); ?>
    <link rel="stylesheet" href="/admin/css/admin-style.css">
    <link rel="stylesheet" href="/css/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔐</text></svg>">
</head>
<body class="admin-body">
    <div class="admin-dashboard">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <button class="btn-icon" id="sidebarToggle" title="Переключить боковую панель">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title"><?php echo htmlspecialchars($pageTitle ?? 'Админ-панель', ENT_QUOTES, 'UTF-8'); ?></h1>
                </div>
                
                <div class="header-right">
                    <button class="btn-icon" id="notificationsBtn" title="Уведомления">
                        <i class="fas fa-bell"></i>
                        <span class="badge badge-notification" id="notificationBadge" style="display: none;">0</span>
                    </button>
                    
                    <button class="btn-icon" id="quickSettingsBtn" title="Быстрые настройки">
                        <i class="fas fa-sliders-h"></i>
                    </button>
                    
                    <div class="user-menu">
                        <button class="btn-user" id="userMenuBtn">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo htmlspecialchars(Auth::user(), ENT_QUOTES, 'UTF-8'); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        
                        <div class="dropdown-menu" id="userMenuDropdown" style="display: none;">
                            <a href="/admin/settings.php" class="dropdown-item">
                                <i class="fas fa-cog"></i>
                                Настройки
                            </a>
                            <hr class="dropdown-divider">
                            <a href="/admin/logout.php" class="dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt"></i>
                                Выход
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <div class="admin-content">
