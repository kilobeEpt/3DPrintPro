// ========================================
// Audit Logs Module
// Manages admin action logs viewer
// ========================================

class AuditModule {
    constructor() {
        this.currentPage = 1;
        this.perPage = 50;
        this.filters = {};
        this.users = [];
    }
    
    init() {
        this.loadUsers();
        this.loadLogs();
        this.loadStats();
        this.attachEventListeners();
    }
    
    attachEventListeners() {
        // Filter controls
        document.getElementById('applyFiltersBtn').addEventListener('click', () => {
            this.applyFilters();
        });
        
        document.getElementById('resetFiltersBtn').addEventListener('click', () => {
            this.resetFilters();
        });
        
        // Export
        document.getElementById('exportLogsBtn').addEventListener('click', () => {
            this.exportLogs();
        });
        
        // Cleanup
        document.getElementById('cleanupLogsBtn').addEventListener('click', () => {
            this.showCleanupDialog();
        });
        
        // Modal close
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('logDetailsModal').classList.remove('active');
            });
        });
    }
    
    async loadUsers() {
        try {
            const response = await window.adminApi.get('/api/admin/users.php');
            if (response.data) {
                this.users = response.data;
                this.renderUserFilter();
            }
        } catch (error) {
            console.error('Failed to load users:', error);
        }
    }
    
    renderUserFilter() {
        const select = document.getElementById('filterUser');
        const currentValue = select.value;
        
        // Clear existing options except "All"
        select.innerHTML = '<option value="">Все пользователи</option>';
        
        this.users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = `${user.name} (${user.email})`;
            select.appendChild(option);
        });
        
        // System actions option
        const systemOption = document.createElement('option');
        systemOption.value = 'system';
        systemOption.textContent = 'Система (автоматические действия)';
        select.appendChild(systemOption);
        
        select.value = currentValue;
    }
    
    async loadLogs() {
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: this.perPage,
                ...this.filters
            });
            
            const response = await window.adminApi.get(`/api/admin/audit-logs.php?${params}`);
            
            if (response.data) {
                this.renderLogs(response.data);
                this.renderPagination(response.meta);
            }
        } catch (error) {
            console.error('Failed to load logs:', error);
            this.renderError('Не удалось загрузить журнал действий');
        }
    }
    
    async loadStats() {
        try {
            const response = await window.adminApi.get('/api/admin/audit-logs.php?stats=1');
            
            if (response.data) {
                this.renderStats(response.data);
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }
    
    renderStats(stats) {
        document.getElementById('statTotal').textContent = this.formatNumber(stats.total || 0);
        document.getElementById('statToday').textContent = this.formatNumber(stats.today || 0);
        document.getElementById('statViolations').textContent = this.formatNumber(stats.violations || 0);
        document.getElementById('statUniqueIps').textContent = this.formatNumber(stats.unique_ips || 0);
    }
    
    renderLogs(logs) {
        const tbody = document.getElementById('logsTableBody');
        
        if (logs.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center">
                        <i class="fas fa-inbox"></i>
                        <p>Записи не найдены</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = logs.map(log => this.renderLogRow(log)).join('');
        
        // Attach click handlers for details
        tbody.querySelectorAll('.view-details-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const logId = e.target.closest('button').dataset.logId;
                const log = logs.find(l => l.id == logId);
                if (log) {
                    this.showLogDetails(log);
                }
            });
        });
    }
    
    renderLogRow(log) {
        const userName = log.user ? `${log.user.name} (${log.user.email})` : '<em>Система</em>';
        const entityInfo = log.entity_type ? `${this.translateEntityType(log.entity_type)}${log.entity_id ? ' #' + log.entity_id : ''}` : '-';
        
        return `
            <tr>
                <td>
                    <div>${this.formatDateTime(log.created_at)}</div>
                    <small class="text-muted">${this.formatTimeAgo(log.created_at)}</small>
                </td>
                <td>${userName}</td>
                <td>
                    <span class="action-badge ${log.action}">${this.translateAction(log.action)}</span>
                </td>
                <td>
                    ${log.entity_id ? 
                        `<a href="#" class="entity-link" onclick="return false;">${entityInfo}</a>` : 
                        entityInfo
                    }
                </td>
                <td>
                    <code>${log.ip_address || '-'}</code>
                    ${log.user_agent ? `<br><small class="text-muted" title="${this.escapeHtml(log.user_agent)}">${this.truncate(log.user_agent, 30)}</small>` : ''}
                </td>
                <td>
                    <button class="btn btn-sm btn-secondary view-details-btn" data-log-id="${log.id}">
                        <i class="fas fa-eye"></i>
                        Детали
                    </button>
                </td>
            </tr>
        `;
    }
    
    showLogDetails(log) {
        const modal = document.getElementById('logDetailsModal');
        const body = document.getElementById('logDetailsBody');
        
        let payloadContent = 'Нет данных';
        if (log.payload) {
            try {
                const payload = typeof log.payload === 'string' ? JSON.parse(log.payload) : log.payload;
                payloadContent = JSON.stringify(payload, null, 2);
            } catch (e) {
                payloadContent = log.payload;
            }
        }
        
        body.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <p><strong>ID:</strong> ${log.id}</p>
                    <p><strong>Время:</strong> ${this.formatDateTime(log.created_at)}</p>
                    <p><strong>Пользователь:</strong> ${log.user ? `${log.user.name} (${log.user.email})` : 'Система'}</p>
                    <p><strong>Действие:</strong> <span class="action-badge ${log.action}">${this.translateAction(log.action)}</span></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Тип объекта:</strong> ${log.entity_type ? this.translateEntityType(log.entity_type) : '-'}</p>
                    <p><strong>ID объекта:</strong> ${log.entity_id || '-'}</p>
                    <p><strong>IP адрес:</strong> <code>${log.ip_address || '-'}</code></p>
                </div>
            </div>
            <div class="mt-3">
                <p><strong>User Agent:</strong></p>
                <code>${this.escapeHtml(log.user_agent || '-')}</code>
            </div>
            <div class="mt-3">
                <p><strong>Дополнительные данные:</strong></p>
                <div class="log-details">${this.escapeHtml(payloadContent)}</div>
            </div>
        `;
        
        modal.classList.add('active');
    }
    
    renderPagination(meta) {
        const container = document.getElementById('paginationContainer');
        
        if (!meta) {
            container.innerHTML = '';
            return;
        }
        
        const totalPages = Math.ceil(meta.total / meta.per_page);
        const currentPage = meta.page;
        
        container.innerHTML = `
            <div class="pagination-info">
                Записи ${meta.from || 0}-${meta.to || 0} из ${meta.total}
            </div>
            <div class="pagination-controls">
                <button class="btn btn-secondary" ${currentPage <= 1 ? 'disabled' : ''} onclick="auditModule.goToPage(1)">
                    <i class="fas fa-angle-double-left"></i>
                </button>
                <button class="btn btn-secondary" ${currentPage <= 1 ? 'disabled' : ''} onclick="auditModule.goToPage(${currentPage - 1})">
                    <i class="fas fa-angle-left"></i>
                </button>
                <span class="mx-3">Страница ${currentPage} из ${totalPages}</span>
                <button class="btn btn-secondary" ${currentPage >= totalPages ? 'disabled' : ''} onclick="auditModule.goToPage(${currentPage + 1})">
                    <i class="fas fa-angle-right"></i>
                </button>
                <button class="btn btn-secondary" ${currentPage >= totalPages ? 'disabled' : ''} onclick="auditModule.goToPage(${totalPages})">
                    <i class="fas fa-angle-double-right"></i>
                </button>
            </div>
        `;
    }
    
    goToPage(page) {
        this.currentPage = page;
        this.loadLogs();
    }
    
    applyFilters() {
        this.filters = {};
        
        const userId = document.getElementById('filterUser').value;
        if (userId) {
            if (userId === 'system') {
                this.filters.user_id = 'null';
            } else {
                this.filters.user_id = userId;
            }
        }
        
        const action = document.getElementById('filterAction').value;
        if (action) this.filters.action = action;
        
        const entityType = document.getElementById('filterEntityType').value;
        if (entityType) this.filters.entity_type = entityType;
        
        const dateFrom = document.getElementById('filterDateFrom').value;
        if (dateFrom) this.filters.date_from = dateFrom;
        
        const dateTo = document.getElementById('filterDateTo').value;
        if (dateTo) this.filters.date_to = dateTo;
        
        const search = document.getElementById('filterSearch').value;
        if (search) this.filters.search = search;
        
        this.currentPage = 1;
        this.loadLogs();
        this.loadStats();
    }
    
    resetFilters() {
        this.filters = {};
        this.currentPage = 1;
        
        document.getElementById('filterUser').value = '';
        document.getElementById('filterAction').value = '';
        document.getElementById('filterEntityType').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        document.getElementById('filterSearch').value = '';
        
        this.loadLogs();
        this.loadStats();
    }
    
    async exportLogs() {
        try {
            const params = new URLSearchParams(this.filters);
            const response = await window.adminApi.get(`/api/admin/audit-logs.php?export=csv&${params}`);
            
            if (response.data && response.data.url) {
                window.open(response.data.url, '_blank');
                window.showNotification('Экспорт подготовлен', 'success');
            }
        } catch (error) {
            console.error('Export failed:', error);
            window.showNotification('Не удалось экспортировать журнал', 'error');
        }
    }
    
    showCleanupDialog() {
        if (!confirm('Удалить записи старше 90 дней?\n\nЭто действие нельзя отменить.')) {
            return;
        }
        
        this.cleanupOldLogs(90);
    }
    
    async cleanupOldLogs(days) {
        try {
            const response = await window.adminApi.delete(`/api/admin/audit-logs.php?older_than=${days}`);
            
            if (response.success) {
                window.showNotification(`Удалено записей: ${response.data.deleted}`, 'success');
                this.loadLogs();
                this.loadStats();
            }
        } catch (error) {
            console.error('Cleanup failed:', error);
            window.showNotification('Не удалось очистить журнал', 'error');
        }
    }
    
    renderError(message) {
        const tbody = document.getElementById('logsTableBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>${message}</p>
                </td>
            </tr>
        `;
    }
    
    // Helper methods
    translateAction(action) {
        const translations = {
            'login': 'Вход',
            'logout': 'Выход',
            'login_failed': 'Неудачный вход',
            'create': 'Создание',
            'update': 'Обновление',
            'delete': 'Удаление',
            'view': 'Просмотр',
            'status_change': 'Изменение статуса',
            'archive': 'Архивирование',
            'unarchive': 'Разархивирование',
            'add_note': 'Добавление примечания',
            'generate_export_url': 'Генерация экспорта',
            'rate_limit_violation': 'Превышение лимита',
            'settings_change': 'Изменение настроек'
        };
        return translations[action] || action;
    }
    
    translateEntityType(type) {
        const translations = {
            'admin_user': 'Администратор',
            'service': 'Услуга',
            'portfolio': 'Портфолио',
            'testimonial': 'Отзыв',
            'faq': 'FAQ',
            'content_block': 'Контент-блок',
            'order': 'Заказ',
            'form': 'Форма',
            'setting': 'Настройка',
            'rate_limiter': 'Лимитер'
        };
        return translations[type] || type;
    }
    
    formatDateTime(dateTime) {
        const date = new Date(dateTime);
        return date.toLocaleString('ru-RU');
    }
    
    formatTimeAgo(dateTime) {
        const seconds = Math.floor((new Date() - new Date(dateTime)) / 1000);
        
        if (seconds < 60) return 'только что';
        if (seconds < 3600) return `${Math.floor(seconds / 60)} мин назад`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)} ч назад`;
        if (seconds < 604800) return `${Math.floor(seconds / 86400)} дн назад`;
        
        return new Date(dateTime).toLocaleDateString('ru-RU');
    }
    
    formatNumber(num) {
        return num.toLocaleString('ru-RU');
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    truncate(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substr(0, maxLength) + '...';
    }
}

// Initialize module
const auditModule = new AuditModule();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => auditModule.init());
} else {
    auditModule.init();
}
