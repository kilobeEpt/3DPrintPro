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
        <h3>Telegram интеграция</h3>
        
        <div class="form-group">
            <label for="telegram_bot_token">
                Telegram Bot Token
                <small class="text-muted">Токен бота для отправки уведомлений</small>
            </label>
            <div class="input-group">
                <input type="password" 
                       name="telegram_bot_token" 
                       id="telegram_bot_token" 
                       class="form-control" 
                       placeholder="123456:ABC-DEF..."
                       autocomplete="off">
                <button type="button" class="btn btn-secondary" id="toggleTokenBtn" title="Показать/скрыть токен">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
        
        <div class="form-group">
            <label for="telegram_chat_id">
                Telegram Chat ID
                <small class="text-muted">ID чата для получения уведомлений</small>
            </label>
            <input type="text" 
                   name="telegram_chat_id" 
                   id="telegram_chat_id" 
                   class="form-control" 
                   placeholder="-1001234567890">
            <small class="form-text text-muted">
                Чтобы узнать Chat ID: отправьте /start боту, затем откройте 
                <a href="https://api.telegram.org/bot{TOKEN}/getUpdates" target="_blank" rel="noopener">
                    https://api.telegram.org/bot{TOKEN}/getUpdates
                </a>
            </small>
        </div>
        
        <div class="form-group">
            <label for="telegram_contact_url">
                Telegram Contact URL
                <small class="text-muted">Публичная ссылка на Telegram (отображается на сайте)</small>
            </label>
            <input type="text" 
                   name="telegram_contact_url" 
                   id="telegram_contact_url" 
                   class="form-control" 
                   placeholder="https://t.me/YourBot">
        </div>
        
        <div class="form-group">
            <button type="button" class="btn btn-info" id="testTelegramBtn">
                <i class="fas fa-paper-plane"></i>
                Отправить тестовое сообщение
            </button>
            <span id="telegramTestResult" class="ml-3"></span>
        </div>
        
        <h4>Настройки уведомлений</h4>
        
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="telegram_notify_new_order" id="telegram_notify_new_order" value="1">
                <span>Уведомлять о новых заказах</span>
            </label>
        </div>
        
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="telegram_notify_status_change" id="telegram_notify_status_change" value="1">
                <span>Уведомлять об изменении статуса заказа</span>
            </label>
        </div>
        
        <hr>
        
        <h3>Email уведомления</h3>
        
        <div class="form-group">
            <label for="admin_email">
                Email для уведомлений
                <small class="text-muted">Адрес для получения уведомлений по email</small>
            </label>
            <input type="email" 
                   name="admin_email" 
                   id="admin_email" 
                   class="form-control" 
                   placeholder="admin@example.com">
        </div>
        
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="email_notifications_enabled" id="email_notifications_enabled" value="1">
                <span>Включить email уведомления</span>
            </label>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
