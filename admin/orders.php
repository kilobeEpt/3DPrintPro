<?php
// ========================================
// Orders Management Page (v3.0 - Comprehensive Workspace)
// ========================================

define('ADMIN_INIT', true);

require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

Auth::require('/admin/login.php');

$pageTitle = 'Заказы';
$pageScripts = ['/admin/js/modules/orders.js', '/admin/js/modules/order-detail.js'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="orders-workspace">
    <!-- Workspace Header -->
    <div class="workspace-header">
        <div class="workspace-title">
            <h1>Управление заказами</h1>
            <p class="workspace-subtitle">Полная информация о заказах и обращениях клиентов</p>
        </div>
        <div class="workspace-actions">
            <button class="btn btn-outline" id="bulkActionsBtn" disabled>
                <i class="fas fa-tasks"></i>
                Групповые действия
            </button>
            <button class="btn btn-outline" id="exportBtn">
                <i class="fas fa-file-export"></i>
                Экспорт
            </button>
            <button class="btn btn-primary" id="refreshBtn">
                <i class="fas fa-sync-alt"></i>
                Обновить
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs-container">
        <div class="tabs">
            <button class="tab-btn active" data-tab="active">
                <i class="fas fa-clipboard-list"></i>
                Активные
                <span class="tab-badge" id="activeCount">0</span>
            </button>
            <button class="tab-btn" data-tab="archived">
                <i class="fas fa-archive"></i>
                Архив
                <span class="tab-badge" id="archivedCount">0</span>
            </button>
            <button class="tab-btn" data-tab="all">
                <i class="fas fa-list"></i>
                Все
                <span class="tab-badge" id="allCount">0</span>
            </button>
        </div>
    </div>

    <!-- Advanced Filters -->
    <div class="card filters-card">
        <div class="filters-header">
            <div class="filters-left">
                <button class="btn btn-sm btn-outline" id="toggleFiltersBtn">
                    <i class="fas fa-filter"></i>
                    Фильтры
                </button>
                <button class="btn btn-sm btn-outline" id="savePresetBtn">
                    <i class="fas fa-save"></i>
                    Сохранить пресет
                </button>
                <div class="preset-selector">
                    <select id="presetSelect" class="filter-select">
                        <option value="">Загрузить пресет...</option>
                    </select>
                    <button class="btn btn-sm btn-icon" id="deletePresetBtn" disabled>
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="filters-right">
                <button class="btn btn-sm btn-secondary" id="resetFiltersBtn">
                    <i class="fas fa-redo"></i>
                    Сбросить
                </button>
            </div>
        </div>

        <div class="filters-body" id="filtersBody">
            <div class="filters-grid">
                <div class="filter-item">
                    <label>Статус</label>
                    <select class="filter-select" id="statusFilter">
                        <option value="">Все статусы</option>
                        <option value="new">Новые</option>
                        <option value="processing">В работе</option>
                        <option value="completed">Выполнены</option>
                        <option value="cancelled">Отменены</option>
                    </select>
                </div>

                <div class="filter-item">
                    <label>Тип</label>
                    <select class="filter-select" id="typeFilter">
                        <option value="">Все типы</option>
                        <option value="order">Заказы</option>
                        <option value="contact">Обращения</option>
                    </select>
                </div>

                <div class="filter-item">
                    <label>Дата от</label>
                    <input type="date" class="filter-input" id="dateFromFilter">
                </div>

                <div class="filter-item">
                    <label>Дата до</label>
                    <input type="date" class="filter-input" id="dateToFilter">
                </div>

                <div class="filter-item filter-item-wide">
                    <label>Поиск</label>
                    <input type="text" class="filter-input" id="searchFilter" 
                           placeholder="Имя, email, телефон, номер заказа...">
                </div>
            </div>

            <div class="filters-footer">
                <div class="active-filters" id="activeFilters"></div>
            </div>
        </div>
    </div>

    <!-- Orders Table Card -->
    <div class="card orders-table-card">
        <div class="table-header">
            <div class="table-info">
                <span id="ordersInfo">Загрузка...</span>
            </div>
            <div class="table-controls">
                <label class="sort-label">
                    Сортировка:
                    <select id="sortBySelect" class="filter-select">
                        <option value="created_at">Дата создания</option>
                        <option value="updated_at">Дата обновления</option>
                        <option value="amount">Сумма</option>
                        <option value="status">Статус</option>
                        <option value="order_number">Номер</option>
                    </select>
                    <select id="sortOrderSelect" class="filter-select">
                        <option value="desc">↓ Убыв.</option>
                        <option value="asc">↑ Возр.</option>
                    </select>
                </label>
            </div>
        </div>

        <div id="ordersTable">
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Загрузка заказов...</p>
            </div>
        </div>

        <!-- Server-side Pagination -->
        <div class="pagination-container">
            <div class="pagination-info">
                <span id="paginationInfo"></span>
            </div>
            <div id="ordersPagination"></div>
            <div class="pagination-size">
                <label>
                    Показывать:
                    <select id="perPageSelect" class="filter-select">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- Order Detail Drawer -->
<div class="drawer" id="orderDrawer">
    <div class="drawer-overlay"></div>
    <div class="drawer-content">
        <div class="drawer-header">
            <h2 id="drawerTitle">Заказ</h2>
            <button class="btn-close" id="closeDrawerBtn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="drawer-body" id="drawerBody">
            <!-- Content loaded by order-detail.js -->
        </div>
    </div>
</div>

<!-- Export Dialog Modal -->
<div class="modal" id="exportModal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2>Экспорт заказов</h2>
            <button class="btn-close" onclick="ordersModule.closeExportModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Формат</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="exportFormat" value="csv" checked>
                        <span>CSV</span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="exportFormat" value="pdf">
                        <span>PDF</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>Период</label>
                <div class="date-range-group">
                    <input type="date" class="form-control" id="exportDateFrom" placeholder="От">
                    <span>—</span>
                    <input type="date" class="form-control" id="exportDateTo" placeholder="До">
                </div>
            </div>

            <div class="form-group">
                <label>Колонки для экспорта</label>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="exportColumn" value="order_number" checked>
                        <span>Номер</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="exportColumn" value="type" checked>
                        <span>Тип</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="exportColumn" value="name" checked>
                        <span>Клиент</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="exportColumn" value="email" checked>
                        <span>Email</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="exportColumn" value="phone" checked>
                        <span>Телефон</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="exportColumn" value="service" checked>
                        <span>Услуга</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="exportColumn" value="amount" checked>
                        <span>Сумма</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="exportColumn" value="status" checked>
                        <span>Статус</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="exportColumn" value="created_at" checked>
                        <span>Дата</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="applyCurrentFilters" checked>
                    <span>Применить текущие фильтры</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="ordersModule.closeExportModal()">Отмена</button>
            <button class="btn btn-primary" onclick="ordersModule.performExport()">
                <i class="fas fa-download"></i>
                Экспортировать
            </button>
        </div>
    </div>
</div>

<!-- Bulk Actions Modal -->
<div class="modal" id="bulkActionsModal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2>Групповые действия</h2>
            <button class="btn-close" onclick="ordersModule.closeBulkActionsModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>Выбрано заказов: <strong id="bulkSelectedCount">0</strong></p>
            
            <div class="bulk-actions-list">
                <button class="bulk-action-btn" onclick="ordersModule.bulkArchive()">
                    <i class="fas fa-archive"></i>
                    <div>
                        <strong>Архивировать</strong>
                        <small>Переместить в архив</small>
                    </div>
                </button>
                
                <button class="bulk-action-btn" onclick="ordersModule.bulkUnarchive()">
                    <i class="fas fa-box-open"></i>
                    <div>
                        <strong>Разархивировать</strong>
                        <small>Вернуть из архива</small>
                    </div>
                </button>
                
                <button class="bulk-action-btn" onclick="ordersModule.bulkChangeStatus()">
                    <i class="fas fa-edit"></i>
                    <div>
                        <strong>Изменить статус</strong>
                        <small>Установить новый статус</small>
                    </div>
                </button>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="ordersModule.closeBulkActionsModal()">Закрыть</button>
        </div>
    </div>
</div>

<!-- Save Preset Modal -->
<div class="modal" id="savePresetModal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2>Сохранить пресет фильтров</h2>
            <button class="btn-close" onclick="ordersModule.closeSavePresetModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Название пресета</label>
                <input type="text" class="form-control" id="presetName" 
                       placeholder="Например: Новые заказы за неделю">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="ordersModule.closeSavePresetModal()">Отмена</button>
            <button class="btn btn-primary" onclick="ordersModule.savePreset()">
                <i class="fas fa-save"></i>
                Сохранить
            </button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
