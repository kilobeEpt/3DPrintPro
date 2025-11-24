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
        <div>
            <button class="btn btn-primary" id="saveSettingsBtn">
                <i class="fas fa-save"></i>
                Сохранить изменения
            </button>
            <button class="btn btn-secondary ml-2" id="viewAuditBtn">
                <i class="fas fa-history"></i>
                История изменений
            </button>
            <span id="cacheStatus" class="ml-3"></span>
        </div>
    </div>
    
    <div class="card-body">
        <div id="validationErrors"></div>
        
        <!-- Tabs Navigation -->
        <div class="tabs-container">
            <div class="tabs-nav">
                <button class="tab-btn active" data-tab="contacts">
                    <i class="fas fa-address-card"></i>
                    Контакты
                </button>
                <button class="tab-btn" data-tab="social">
                    <i class="fas fa-share-alt"></i>
                    Соц. сети
                </button>
                <button class="tab-btn" data-tab="seo">
                    <i class="fas fa-search"></i>
                    SEO
                </button>
                <button class="tab-btn" data-tab="email">
                    <i class="fas fa-envelope"></i>
                    Email
                </button>
                <button class="tab-btn" data-tab="telegram">
                    <i class="fab fa-telegram"></i>
                    Telegram
                </button>
                <button class="tab-btn" data-tab="logging">
                    <i class="fas fa-chart-line"></i>
                    Логи/Аналитика
                </button>
                <button class="tab-btn" data-tab="cache">
                    <i class="fas fa-database"></i>
                    Кеширование
                </button>
            </div>
            
            <form id="settingsForm">
                <!-- Contacts Tab -->
                <div class="tab-content active" data-tab="contacts">
                    <h3>Контактная информация</h3>
                    <p class="text-muted">Эти данные отображаются на публичном сайте и используются для связи с клиентами.</p>
                    
                    <div class="form-group">
                        <label for="contact_phone">Телефон</label>
                        <input type="text" name="contact_phone" id="contact_phone" class="form-control" 
                               placeholder="+7 (999) 123-45-67">
                        <small class="form-text text-muted">Основной контактный телефон</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_email">Email</label>
                        <input type="email" name="contact_email" id="contact_email" class="form-control" 
                               placeholder="info@3dprint-omsk.ru">
                        <small class="form-text text-muted">Основной контактный email</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_address">Адрес</label>
                        <input type="text" name="contact_address" id="contact_address" class="form-control" 
                               placeholder="ул. Ленина, д. 15">
                        <small class="form-text text-muted">Улица и номер дома</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="contact_city">Город</label>
                            <input type="text" name="contact_city" id="contact_city" class="form-control" 
                                   placeholder="Омск">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="contact_region">Регион</label>
                            <input type="text" name="contact_region" id="contact_region" class="form-control" 
                                   placeholder="Омская область">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="contact_postal_code">Индекс</label>
                            <input type="text" name="contact_postal_code" id="contact_postal_code" class="form-control" 
                                   placeholder="644000">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_country">Код страны</label>
                        <input type="text" name="contact_country" id="contact_country" class="form-control" 
                               placeholder="RU">
                        <small class="form-text text-muted">ISO код страны (например: RU, US, UA)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_working_hours">Режим работы</label>
                        <input type="text" name="contact_working_hours" id="contact_working_hours" class="form-control" 
                               placeholder="Пн-Пт: 9:00-18:00">
                        <small class="form-text text-muted">Часы работы для отображения на сайте</small>
                    </div>
                    
                    <h4 class="mt-4">Геолокация</h4>
                    <p class="text-muted">Координаты для карт и геотаргетинга</p>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="contact_latitude">Широта</label>
                            <input type="number" step="0.0001" name="contact_latitude" id="contact_latitude" 
                                   class="form-control" placeholder="54.9885">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="contact_longitude">Долгота</label>
                            <input type="number" step="0.0001" name="contact_longitude" id="contact_longitude" 
                                   class="form-control" placeholder="73.3242">
                        </div>
                    </div>
                </div>
                
                <!-- Social Tab -->
                <div class="tab-content" data-tab="social">
                    <h3>Социальные сети</h3>
                    <p class="text-muted">Ссылки на профили в социальных сетях. Оставьте пустым, если не используете.</p>
                    
                    <div class="form-group">
                        <label for="social_telegram">Telegram</label>
                        <input type="url" name="social_telegram" id="social_telegram" class="form-control" 
                               placeholder="https://t.me/YourChannel">
                    </div>
                    
                    <div class="form-group">
                        <label for="social_vk">ВКонтакте</label>
                        <input type="url" name="social_vk" id="social_vk" class="form-control" 
                               placeholder="https://vk.com/your_group">
                    </div>
                    
                    <div class="form-group">
                        <label for="social_instagram">Instagram</label>
                        <input type="url" name="social_instagram" id="social_instagram" class="form-control" 
                               placeholder="https://instagram.com/your_profile">
                    </div>
                    
                    <div class="form-group">
                        <label for="social_facebook">Facebook</label>
                        <input type="url" name="social_facebook" id="social_facebook" class="form-control" 
                               placeholder="https://facebook.com/your_page">
                    </div>
                    
                    <div class="form-group">
                        <label for="social_youtube">YouTube</label>
                        <input type="url" name="social_youtube" id="social_youtube" class="form-control" 
                               placeholder="https://youtube.com/@your_channel">
                    </div>
                    
                    <div class="form-group">
                        <label for="social_twitter">Twitter/X</label>
                        <input type="url" name="social_twitter" id="social_twitter" class="form-control" 
                               placeholder="https://twitter.com/your_handle">
                    </div>
                    
                    <div class="form-group">
                        <label for="social_whatsapp">WhatsApp</label>
                        <input type="text" name="social_whatsapp" id="social_whatsapp" class="form-control" 
                               placeholder="+79991234567">
                        <small class="form-text text-muted">Номер телефона для WhatsApp (с кодом страны)</small>
                    </div>
                </div>
                
                <!-- SEO Tab -->
                <div class="tab-content" data-tab="seo">
                    <h3>SEO метаданные</h3>
                    <p class="text-muted">Настройки для поисковой оптимизации и социальных сетей.</p>
                    
                    <div class="form-group">
                        <label for="seo_title">Заголовок сайта</label>
                        <input type="text" name="seo_title" id="seo_title" class="form-control" 
                               placeholder="3D печать в Омске — услуги 3D печати и моделирования">
                        <small class="form-text text-muted">Основной заголовок (title) для главной страницы</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="seo_description">Описание сайта</label>
                        <textarea name="seo_description" id="seo_description" class="form-control" rows="3"
                                  placeholder="Профессиональная 3D печать в Омске: FDM, SLA, SLS технологии..."></textarea>
                        <small class="form-text text-muted">Meta description для главной страницы (до 160 символов)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="seo_keywords">Ключевые слова</label>
                        <textarea name="seo_keywords" id="seo_keywords" class="form-control" rows="2"
                                  placeholder="3D печать Омск, услуги 3D печати, моделирование"></textarea>
                        <small class="form-text text-muted">Ключевые слова через запятую</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="seo_site_name">Название бренда</label>
                        <input type="text" name="seo_site_name" id="seo_site_name" class="form-control" 
                               placeholder="3D Print Pro">
                        <small class="form-text text-muted">Название для Open Graph (og:site_name)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="seo_canonical_url">Канонический URL</label>
                        <input type="url" name="seo_canonical_url" id="seo_canonical_url" class="form-control" 
                               placeholder="https://3dprint-omsk.ru/">
                        <small class="form-text text-muted">Основной URL сайта</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="seo_og_image">Изображение для соц. сетей</label>
                        <input type="url" name="seo_og_image" id="seo_og_image" class="form-control" 
                               placeholder="https://3dprint-omsk.ru/images/og-image.jpg">
                        <small class="form-text text-muted">URL изображения для Open Graph (1200x630px)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="seo_og_type">Тип контента</label>
                        <select name="seo_og_type" id="seo_og_type" class="form-control">
                            <option value="website">Website</option>
                            <option value="article">Article</option>
                            <option value="product">Product</option>
                        </select>
                        <small class="form-text text-muted">Тип контента для Open Graph</small>
                    </div>
                </div>
                
                <!-- Email Tab -->
                <div class="tab-content" data-tab="email">
                    <h3>Настройки Email</h3>
                    <p class="text-muted">Конфигурация SMTP для отправки уведомлений по электронной почте.</p>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="email_notifications_enabled" id="email_notifications_enabled" value="1">
                            <span>Включить email уведомления</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_email">Email для уведомлений</label>
                        <input type="email" name="admin_email" id="admin_email" class="form-control" 
                               placeholder="admin@3dprint-omsk.ru">
                        <small class="form-text text-muted">Адрес для получения уведомлений</small>
                    </div>
                    
                    <hr class="my-4">
                    <h4>SMTP конфигурация</h4>
                    
                    <div class="form-group">
                        <label for="smtp_host">SMTP хост</label>
                        <input type="text" name="smtp_host" id="smtp_host" class="form-control" 
                               placeholder="smtp.gmail.com">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="smtp_port">SMTP порт</label>
                            <input type="number" name="smtp_port" id="smtp_port" class="form-control" 
                                   placeholder="587">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="smtp_encryption">Шифрование</label>
                            <select name="smtp_encryption" id="smtp_encryption" class="form-control">
                                <option value="">Без шифрования</option>
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_username">SMTP логин</label>
                        <input type="text" name="smtp_username" id="smtp_username" class="form-control" 
                               placeholder="user@example.com" autocomplete="off">
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_password">SMTP пароль</label>
                        <div class="input-group">
                            <input type="password" name="smtp_password" id="smtp_password" class="form-control" 
                                   placeholder="••••••••" autocomplete="off">
                            <button type="button" class="btn btn-secondary" id="toggleSmtpPasswordBtn" 
                                    title="Показать/скрыть пароль">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="smtp_from_email">Email отправителя</label>
                            <input type="email" name="smtp_from_email" id="smtp_from_email" class="form-control" 
                                   placeholder="noreply@3dprint-omsk.ru">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="smtp_from_name">Имя отправителя</label>
                            <input type="text" name="smtp_from_name" id="smtp_from_name" class="form-control" 
                                   placeholder="3D Print Pro">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" class="btn btn-info" id="testEmailBtn">
                            <i class="fas fa-paper-plane"></i>
                            Отправить тестовое письмо
                        </button>
                        <span id="emailTestResult" class="ml-3"></span>
                    </div>
                </div>
                
                <!-- Telegram Tab -->
                <div class="tab-content" data-tab="telegram">
                    <h3>Telegram интеграция</h3>
                    <p class="text-muted">Настройка бота для отправки уведомлений в Telegram.</p>
                    
                    <div class="form-group">
                        <label for="telegram_bot_token">Telegram Bot Token</label>
                        <div class="input-group">
                            <input type="password" name="telegram_bot_token" id="telegram_bot_token" 
                                   class="form-control" placeholder="123456:ABC-DEF..." autocomplete="off">
                            <button type="button" class="btn btn-secondary" id="toggleTokenBtn" 
                                    title="Показать/скрыть токен">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted">Токен бота для отправки уведомлений</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="telegram_chat_id">Telegram Chat ID</label>
                        <input type="text" name="telegram_chat_id" id="telegram_chat_id" class="form-control" 
                               placeholder="-1001234567890">
                        <small class="form-text text-muted">
                            Чтобы узнать Chat ID: отправьте /start боту, затем откройте 
                            <a href="https://api.telegram.org/bot{TOKEN}/getUpdates" target="_blank" rel="noopener">
                                https://api.telegram.org/bot{TOKEN}/getUpdates
                            </a>
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="telegram_contact_url">Telegram Contact URL</label>
                        <input type="text" name="telegram_contact_url" id="telegram_contact_url" class="form-control" 
                               placeholder="https://t.me/YourBot">
                        <small class="form-text text-muted">Публичная ссылка на Telegram (отображается на сайте)</small>
                    </div>
                    
                    <h4 class="mt-4">Настройки уведомлений</h4>
                    
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
                    
                    <div class="form-group">
                        <button type="button" class="btn btn-info" id="testTelegramBtn">
                            <i class="fas fa-paper-plane"></i>
                            Отправить тестовое сообщение
                        </button>
                        <span id="telegramTestResult" class="ml-3"></span>
                    </div>
                </div>
                
                <!-- Logging Tab -->
                <div class="tab-content" data-tab="logging">
                    <h3>Логирование и аналитика</h3>
                    <p class="text-muted">Настройки для отслеживания посетителей и ведения логов.</p>
                    
                    <h4>Аналитика</h4>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="analytics_enabled" id="analytics_enabled" value="1">
                            <span>Включить аналитику</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="analytics_google_id">Google Analytics ID</label>
                        <input type="text" name="analytics_google_id" id="analytics_google_id" class="form-control" 
                               placeholder="G-XXXXXXXXXX или UA-XXXXXXXXX-X">
                        <small class="form-text text-muted">Tracking ID для Google Analytics</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="analytics_yandex_id">Яндекс.Метрика ID</label>
                        <input type="text" name="analytics_yandex_id" id="analytics_yandex_id" class="form-control" 
                               placeholder="12345678">
                        <small class="form-text text-muted">Номер счетчика Яндекс.Метрики</small>
                    </div>
                    
                    <hr class="my-4">
                    <h4>Логирование</h4>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="logging_enabled" id="logging_enabled" value="1">
                            <span>Включить логирование</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="logging_level">Уровень логирования</label>
                        <select name="logging_level" id="logging_level" class="form-control">
                            <option value="debug">Debug (все события)</option>
                            <option value="info">Info (информационные сообщения)</option>
                            <option value="warning">Warning (предупреждения)</option>
                            <option value="error">Error (только ошибки)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="logging_max_files">Хранить логи (дней)</label>
                        <input type="number" name="logging_max_files" id="logging_max_files" class="form-control" 
                               placeholder="30" min="1" max="365">
                        <small class="form-text text-muted">Количество дней хранения файлов логов</small>
                    </div>
                </div>
                
                <!-- Cache Tab -->
                <div class="tab-content" data-tab="cache">
                    <h3>Кеширование</h3>
                    <p class="text-muted">Настройки кеширования для повышения производительности.</p>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="cache_enabled" id="cache_enabled" value="1">
                            <span>Включить кеширование</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="cache_ttl">Время жизни кеша (секунды)</label>
                        <input type="number" name="cache_ttl" id="cache_ttl" class="form-control" 
                               placeholder="300" min="0" max="86400">
                        <small class="form-text text-muted">
                            Время хранения данных в кеше. 300 = 5 минут, 3600 = 1 час, 86400 = 24 часа
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="cache_driver">Драйвер кеша</label>
                        <select name="cache_driver" id="cache_driver" class="form-control">
                            <option value="file">File (файловый)</option>
                            <option value="redis">Redis</option>
                            <option value="memcached">Memcached</option>
                        </select>
                        <small class="form-text text-muted">Система хранения кеша</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="cache_prefix">Префикс ключей кеша</label>
                        <input type="text" name="cache_prefix" id="cache_prefix" class="form-control" 
                               placeholder="3dprint_">
                        <small class="form-text text-muted">Префикс для всех ключей кеша (полезно при shared hosting)</small>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
