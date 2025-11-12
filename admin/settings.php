<?php
define('ADMIN_INIT', true);
require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
Auth::require('/admin/login.php');

$pageTitle = 'Настройки';
$pageScripts = ['/admin/js/modules/settings.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Настройки системы</h2>
        <button class="btn btn-primary" id="saveSettingsBtn">
            <i class="fas fa-save"></i>
            Сохранить изменения
        </button>
    </div>
    
    <form id="settingsForm">
        <div class="form-group">
            <label>Telegram Bot Token</label>
            <input type="text" name="telegram_bot_token" class="form-control" placeholder="123456:ABC-DEF...">
        </div>
        
        <div class="form-group">
            <label>Telegram Chat ID</label>
            <input type="text" name="telegram_chat_id" class="form-control" placeholder="-1001234567890">
        </div>
        
        <div class="form-group">
            <label>Email для уведомлений</label>
            <input type="email" name="admin_email" class="form-control" placeholder="admin@example.com">
        </div>
        
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="enable_notifications">
                <span>Включить уведомления о новых заказах</span>
            </label>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
