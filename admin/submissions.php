<?php
// ========================================
// Form Submissions Management Page (v1.0)
// ========================================

define('ADMIN_INIT', true);

require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

Auth::require('/admin/login.php');

$pageTitle = 'Заявки';
$pageScripts = ['/admin/js/modules/submissions.js'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="submissions-workspace">
    <!-- Workspace Header -->
    <div class="workspace-header">
        <div class="workspace-title">
            <h1>Управление заявками</h1>
            <p class="workspace-subtitle">Просмотр и обработка отправленных форм</p>
        </div>
        <div class="workspace-actions">
            <button class="btn btn-outline" id="bulkActionsBtn" disabled>
                <i class="fas fa-tasks"></i>
                Групповые действия
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
            <button class="tab-btn active" data-tab="pending">
                <i class="fas fa-clock"></i>
                Ожидают
                <span class="tab-badge" id="pendingCount">0</span>
            </button>
            <button class="tab-btn" data-tab="processed">
                <i class="fas fa-check"></i>
                Обработаны
                <span class="tab-badge" id="processedCount">0</span>
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

    <!-- Filters -->
    <div class="card filters-card">
        <div class="filters-header">
            <div class="filters-left">
                <button class="btn btn-sm btn-outline" id="toggleFiltersBtn">
                    <i class="fas fa-filter"></i>
                    Фильтры
                </button>
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
                    <label>Форма</label>
                    <select class="filter-select" id="formFilter">
                        <option value="">Все формы</option>
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
                           placeholder="Поиск по данным заявки...">
                </div>
            </div>
        </div>
    </div>

    <!-- Submissions Table Card -->
    <div class="card submissions-table-card">
        <div class="table-header">
            <div class="table-info">
                <span id="submissionsInfo">Загрузка...</span>
            </div>
        </div>

        <div id="submissionsTable">
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Загрузка заявок...</p>
            </div>
        </div>

        <!-- Pagination -->
        <div class="pagination-container">
            <div class="pagination-info">
                <span id="paginationInfo"></span>
            </div>
            <div id="submissionsPagination"></div>
            <div class="pagination-size">
                <label>
                    Показывать:
                    <select id="perPageSelect" class="filter-select">
                        <option value="20">20</option>
                        <option value="50" selected>50</option>
                        <option value="100">100</option>
                    </select>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- Submission Detail Modal -->
<div class="modal" id="submissionDetailModal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="submissionDetailTitle">Детали заявки</h2>
            <button class="btn-close" onclick="submissionsModule.closeDetailModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="submissionDetailBody">
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Загрузка...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="submissionsModule.closeDetailModal()">Закрыть</button>
            <button class="btn btn-primary" id="changeStatusBtn">
                <i class="fas fa-edit"></i>
                Изменить статус
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
            <button class="btn-close" onclick="submissionsModule.closeBulkActionsModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>Выбрано заявок: <strong id="bulkSelectedCount">0</strong></p>
            
            <div class="bulk-actions-list">
                <button class="bulk-action-btn" onclick="submissionsModule.bulkProcess()">
                    <i class="fas fa-check"></i>
                    <div>
                        <strong>Обработать</strong>
                        <small>Пометить как обработанные</small>
                    </div>
                </button>
                
                <button class="bulk-action-btn" onclick="submissionsModule.bulkArchive()">
                    <i class="fas fa-archive"></i>
                    <div>
                        <strong>Архивировать</strong>
                        <small>Переместить в архив</small>
                    </div>
                </button>
                
                <button class="bulk-action-btn" onclick="submissionsModule.bulkDelete()">
                    <i class="fas fa-trash"></i>
                    <div>
                        <strong>Удалить</strong>
                        <small>Безвозвратно удалить заявки</small>
                    </div>
                </button>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="submissionsModule.closeBulkActionsModal()">Закрыть</button>
        </div>
    </div>
</div>

<!-- Change Status Modal -->
<div class="modal" id="changeStatusModal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2>Изменить статус</h2>
            <button class="btn-close" onclick="submissionsModule.closeChangeStatusModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="statusSubmissionId">
            <div class="form-group">
                <label>Новый статус</label>
                <select class="form-control" id="newStatus">
                    <option value="pending">Ожидает</option>
                    <option value="processed">Обработана</option>
                    <option value="archived">Архив</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="submissionsModule.closeChangeStatusModal()">Отмена</button>
            <button class="btn btn-primary" onclick="submissionsModule.saveStatus()">
                <i class="fas fa-save"></i>
                Сохранить
            </button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
