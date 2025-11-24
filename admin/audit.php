<?php
define('ADMIN_INIT', true);
require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
Auth::require('/admin/login.php');

$pageTitle = 'Журнал действий';
$pageScripts = ['/admin/js/modules/audit.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Журнал действий администраторов</h2>
        <div>
            <button class="btn btn-secondary" id="exportLogsBtn">
                <i class="fas fa-download"></i>
                Экспорт
            </button>
            <button class="btn btn-secondary ml-2" id="cleanupLogsBtn">
                <i class="fas fa-trash-alt"></i>
                Очистка старых записей
            </button>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Filters -->
        <div class="filters-container mb-4">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="filterUser">Администратор</label>
                        <select id="filterUser" class="form-control">
                            <option value="">Все пользователи</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="filterAction">Действие</label>
                        <select id="filterAction" class="form-control">
                            <option value="">Все действия</option>
                            <optgroup label="Аутентификация">
                                <option value="login">Вход в систему</option>
                                <option value="logout">Выход из системы</option>
                                <option value="login_failed">Неудачный вход</option>
                            </optgroup>
                            <optgroup label="Управление контентом">
                                <option value="create">Создание</option>
                                <option value="update">Обновление</option>
                                <option value="delete">Удаление</option>
                                <option value="view">Просмотр</option>
                            </optgroup>
                            <optgroup label="Заказы">
                                <option value="status_change">Изменение статуса</option>
                                <option value="archive">Архивирование</option>
                                <option value="unarchive">Разархивирование</option>
                                <option value="add_note">Добавление примечания</option>
                                <option value="generate_export_url">Генерация экспорта</option>
                            </optgroup>
                            <optgroup label="Система">
                                <option value="rate_limit_violation">Превышение лимита</option>
                                <option value="settings_change">Изменение настроек</option>
                            </optgroup>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="filterEntityType">Тип объекта</label>
                        <select id="filterEntityType" class="form-control">
                            <option value="">Все типы</option>
                            <option value="admin_user">Администратор</option>
                            <option value="service">Услуга</option>
                            <option value="portfolio">Портфолио</option>
                            <option value="testimonial">Отзыв</option>
                            <option value="faq">FAQ</option>
                            <option value="content_block">Контент-блок</option>
                            <option value="order">Заказ</option>
                            <option value="form">Форма</option>
                            <option value="setting">Настройка</option>
                            <option value="rate_limiter">Ограничитель запросов</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="filterDateFrom">Дата от</label>
                        <input type="date" id="filterDateFrom" class="form-control">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="filterDateTo">Дата до</label>
                        <input type="date" id="filterDateTo" class="form-control">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="filterSearch">Поиск</label>
                        <input type="text" id="filterSearch" class="form-control" placeholder="IP, User Agent, ID...">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button class="btn btn-primary" id="applyFiltersBtn">
                                <i class="fas fa-filter"></i>
                                Применить фильтры
                            </button>
                            <button class="btn btn-secondary ml-2" id="resetFiltersBtn">
                                <i class="fas fa-undo"></i>
                                Сбросить
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="row mb-4" id="statsContainer">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Всего записей</div>
                    <div class="stat-value" id="statTotal">-</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">За сегодня</div>
                    <div class="stat-value" id="statToday">-</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Нарушения лимитов</div>
                    <div class="stat-value text-danger" id="statViolations">-</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Уникальных IP</div>
                    <div class="stat-value" id="statUniqueIps">-</div>
                </div>
            </div>
        </div>
        
        <!-- Logs Table -->
        <div class="table-responsive">
            <table class="table" id="logsTable">
                <thead>
                    <tr>
                        <th>Время</th>
                        <th>Пользователь</th>
                        <th>Действие</th>
                        <th>Объект</th>
                        <th>IP адрес</th>
                        <th>Детали</th>
                    </tr>
                </thead>
                <tbody id="logsTableBody">
                    <tr>
                        <td colspan="6" class="text-center">
                            <i class="fas fa-spinner fa-spin"></i> Загрузка...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="pagination-container" id="paginationContainer"></div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal" id="logDetailsModal" style="display: none;">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>Детали действия</h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body" id="logDetailsBody">
            <!-- Dynamic content -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary close-modal">Закрыть</button>
        </div>
    </div>
</div>

<style>
.filters-container {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    border-left: 4px solid #007bff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 600;
    color: #2c3e50;
}

.action-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
}

.action-badge.login { background: #d4edda; color: #155724; }
.action-badge.logout { background: #fff3cd; color: #856404; }
.action-badge.login_failed { background: #f8d7da; color: #721c24; }
.action-badge.create { background: #d1ecf1; color: #0c5460; }
.action-badge.update { background: #e2e3e5; color: #383d41; }
.action-badge.delete { background: #f8d7da; color: #721c24; }
.action-badge.rate_limit_violation { background: #f8d7da; color: #721c24; }

.entity-link {
    color: #007bff;
    text-decoration: none;
    font-family: monospace;
}

.entity-link:hover {
    text-decoration: underline;
}

.log-details {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.875rem;
    white-space: pre-wrap;
    max-height: 400px;
    overflow-y: auto;
}

.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #dee2e6;
}

.pagination-info {
    color: #6c757d;
    font-size: 0.875rem;
}

.pagination-controls button {
    margin-left: 0.5rem;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
