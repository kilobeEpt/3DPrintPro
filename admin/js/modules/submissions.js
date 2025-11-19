// ========================================
// Submissions Module - Form Submissions Management
// ========================================

class SubmissionsModule {
    constructor() {
        this.submissions = [];
        this.forms = [];
        this.currentTab = 'pending';
        this.selectedIds = new Set();
        this.filters = {
            status: null,
            form_id: null,
            date_from: null,
            date_to: null,
            search: null
        };
        this.pagination = {
            limit: 50,
            offset: 0,
            total: 0
        };
        this.adminMain = null;
    }
    
    async init() {
        console.log('📥 Loading submissions...');
        
        if (!window.adminApi || !window.AdminMain) {
            setTimeout(() => this.init(), 100);
            return;
        }
        
        this.adminMain = window.AdminMain;
        this.initButtons();
        this.initTabs();
        this.initFilters();
        await this.loadForms();
        await this.loadSubmissions();
    }
    
    initButtons() {
        document.getElementById('refreshBtn')?.addEventListener('click', () => this.loadSubmissions());
        document.getElementById('bulkActionsBtn')?.addEventListener('click', () => this.showBulkActionsModal());
        document.getElementById('toggleFiltersBtn')?.addEventListener('click', () => this.toggleFilters());
        document.getElementById('resetFiltersBtn')?.addEventListener('click', () => this.resetFilters());
        document.getElementById('changeStatusBtn')?.addEventListener('click', () => this.showChangeStatusModal());
        document.getElementById('perPageSelect')?.addEventListener('change', (e) => {
            this.pagination.limit = parseInt(e.target.value);
            this.pagination.offset = 0;
            this.loadSubmissions();
        });
    }
    
    initTabs() {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.currentTab = e.target.closest('.tab-btn').dataset.tab;
                this.switchTab(this.currentTab);
            });
        });
    }
    
    initFilters() {
        document.getElementById('formFilter')?.addEventListener('change', (e) => {
            this.filters.form_id = e.target.value || null;
            this.loadSubmissions();
        });
        
        document.getElementById('dateFromFilter')?.addEventListener('change', (e) => {
            this.filters.date_from = e.target.value || null;
            this.loadSubmissions();
        });
        
        document.getElementById('dateToFilter')?.addEventListener('change', (e) => {
            this.filters.date_to = e.target.value || null;
            this.loadSubmissions();
        });
        
        document.getElementById('searchFilter')?.addEventListener('input', this.debounce(() => {
            this.filters.search = document.getElementById('searchFilter').value.trim() || null;
            this.loadSubmissions();
        }, 500));
    }
    
    async loadForms() {
        try {
            const response = await window.adminApi.getForms();
            this.forms = response.data.forms;
            this.renderFormFilter();
        } catch (error) {
            console.error('Failed to load forms:', error);
        }
    }
    
    renderFormFilter() {
        const select = document.getElementById('formFilter');
        if (!select) return;
        
        select.innerHTML = '<option value="">Все формы</option>' +
            this.forms.map(form => `<option value="${form.id}">${this.escapeHtml(form.name)}</option>`).join('');
    }
    
    async loadSubmissions() {
        const table = document.getElementById('submissionsTable');
        if (!table) return;
        
        try {
            this.adminMain.showLoading(table);
            
            const params = {
                limit: this.pagination.limit,
                offset: this.pagination.offset,
                ...this.filters
            };
            
            const response = await window.adminApi.getSubmissions(params);
            this.submissions = response.data.submissions;
            this.pagination.total = response.meta.total;
            
            this.updateCounts(response.meta.status_counts);
            this.renderSubmissionsTable();
            this.renderPagination();
            this.updateInfo();
            
            console.log(`✅ Loaded ${this.submissions.length} submissions`);
        } catch (error) {
            console.error('❌ Failed to load submissions:', error);
            this.adminMain.showError(table, 'Не удалось загрузить заявки');
        }
    }
    
    renderSubmissionsTable() {
        const table = document.getElementById('submissionsTable');
        if (!table) return;
        
        if (this.submissions.length === 0) {
            table.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Нет заявок</p>
                </div>
            `;
            return;
        }
        
        table.innerHTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onchange="submissionsModule.toggleSelectAll(this.checked)"></th>
                        <th>ID</th>
                        <th>Форма</th>
                        <th>Данные</th>
                        <th>Статус</th>
                        <th>Заказ</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    ${this.submissions.map(sub => this.renderSubmissionRow(sub)).join('')}
                </tbody>
            </table>
        `;
        
        this.updateBulkActionsButton();
    }
    
    renderSubmissionRow(sub) {
        const data = sub.submitted_data || {};
        const preview = Object.entries(data).slice(0, 2).map(([k, v]) => `${k}: ${v}`).join(', ');
        
        return `
            <tr>
                <td><input type="checkbox" class="submission-checkbox" value="${sub.id}" onchange="submissionsModule.toggleSelect(${sub.id}, this.checked)"></td>
                <td>${sub.id}</td>
                <td>${this.escapeHtml(sub.form_name || sub.form_slug)}</td>
                <td>
                    <small class="text-truncate" style="max-width: 300px; display: block;" title="${this.escapeHtml(JSON.stringify(data))}">
                        ${this.escapeHtml(preview)}...
                    </small>
                </td>
                <td>
                    <span class="badge ${this.getStatusBadgeClass(sub.status)}">
                        ${this.getStatusLabel(sub.status)}
                    </span>
                </td>
                <td>
                    ${sub.has_order ? `<a href="/admin/orders.php?id=${sub.order_id}" class="badge badge-info">Заказ #${sub.order_id}</a>` : '-'}
                </td>
                <td>${this.formatDate(sub.submitted_at)}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline" onclick="submissionsModule.viewSubmission(${sub.id})" title="Просмотр">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline" onclick="submissionsModule.deleteSubmission(${sub.id})" title="Удалить">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }
    
    async viewSubmission(id) {
        try {
            const response = await window.adminApi.getSubmission(id);
            const sub = response.data.submission;
            
            const body = document.getElementById('submissionDetailBody');
            body.innerHTML = `
                <div class="detail-section">
                    <h3>Информация о заявке</h3>
                    <dl class="detail-list">
                        <dt>ID:</dt>
                        <dd>${sub.id}</dd>
                        <dt>Форма:</dt>
                        <dd>${this.escapeHtml(sub.form_name || sub.form_slug)}</dd>
                        <dt>Статус:</dt>
                        <dd><span class="badge ${this.getStatusBadgeClass(sub.status)}">${this.getStatusLabel(sub.status)}</span></dd>
                        <dt>Дата отправки:</dt>
                        <dd>${this.formatDate(sub.submitted_at)}</dd>
                        <dt>IP адрес:</dt>
                        <dd>${sub.ip_address || '-'}</dd>
                    </dl>
                </div>
                
                ${sub.order ? `
                <div class="detail-section">
                    <h3>Связанный заказ</h3>
                    <dl class="detail-list">
                        <dt>Номер заказа:</dt>
                        <dd><a href="/admin/orders.php?id=${sub.order.id}">${this.escapeHtml(sub.order.order_number)}</a></dd>
                        <dt>Статус:</dt>
                        <dd>${this.escapeHtml(sub.order.status)}</dd>
                        <dt>Сумма:</dt>
                        <dd>${sub.order.amount} ₽</dd>
                    </dl>
                </div>
                ` : ''}
                
                <div class="detail-section">
                    <h3>Данные формы</h3>
                    <dl class="detail-list">
                        ${sub.values.map(val => `
                            <dt>${this.escapeHtml(val.field_label)}:</dt>
                            <dd>${this.escapeHtml(val.field_value || '-')}</dd>
                        `).join('')}
                    </dl>
                </div>
            `;
            
            document.getElementById('submissionDetailTitle').textContent = `Заявка #${sub.id}`;
            document.getElementById('statusSubmissionId').value = sub.id;
            this.showModal('submissionDetailModal');
        } catch (error) {
            console.error('Failed to load submission:', error);
            this.adminMain.showNotification('Не удалось загрузить заявку', 'error');
        }
    }
    
    async deleteSubmission(id) {
        if (!confirm('Удалить эту заявку? Это действие необратимо.')) return;
        
        try {
            await window.adminApi.deleteSubmission(id);
            this.adminMain.showNotification('Заявка удалена', 'success');
            await this.loadSubmissions();
        } catch (error) {
            console.error('Failed to delete submission:', error);
            this.adminMain.showNotification('Не удалось удалить заявку', 'error');
        }
    }
    
    toggleSelect(id, checked) {
        if (checked) {
            this.selectedIds.add(id);
        } else {
            this.selectedIds.delete(id);
        }
        this.updateBulkActionsButton();
    }
    
    toggleSelectAll(checked) {
        document.querySelectorAll('.submission-checkbox').forEach(cb => {
            cb.checked = checked;
            this.toggleSelect(parseInt(cb.value), checked);
        });
    }
    
    updateBulkActionsButton() {
        const btn = document.getElementById('bulkActionsBtn');
        if (btn) {
            btn.disabled = this.selectedIds.size === 0;
        }
        
        const count = document.getElementById('bulkSelectedCount');
        if (count) {
            count.textContent = this.selectedIds.size;
        }
    }
    
    showBulkActionsModal() {
        if (this.selectedIds.size === 0) return;
        this.showModal('bulkActionsModal');
    }
    
    showChangeStatusModal() {
        const submissionId = document.getElementById('statusSubmissionId').value;
        if (!submissionId) return;
        
        this.showModal('changeStatusModal');
    }
    
    async saveStatus() {
        const id = document.getElementById('statusSubmissionId').value;
        const newStatus = document.getElementById('newStatus').value;
        
        if (!id || !newStatus) return;
        
        try {
            await window.adminApi.updateSubmissionStatus(parseInt(id), newStatus);
            this.adminMain.showNotification('Статус обновлен', 'success');
            this.closeChangeStatusModal();
            this.closeDetailModal();
            await this.loadSubmissions();
        } catch (error) {
            console.error('Failed to update status:', error);
            this.adminMain.showNotification('Не удалось обновить статус', 'error');
        }
    }
    
    async bulkProcess() {
        await this.bulkAction('process', 'Заявки обработаны');
    }
    
    async bulkArchive() {
        await this.bulkAction('archive', 'Заявки архивированы');
    }
    
    async bulkDelete() {
        if (!confirm(`Удалить ${this.selectedIds.size} заявок? Это действие необратимо.`)) return;
        await this.bulkAction('delete', 'Заявки удалены');
    }
    
    async bulkAction(action, successMessage) {
        try {
            await window.adminApi.bulkSubmissionAction(action, Array.from(this.selectedIds));
            this.adminMain.showNotification(successMessage, 'success');
            this.selectedIds.clear();
            this.closeBulkActionsModal();
            await this.loadSubmissions();
        } catch (error) {
            console.error('Bulk action failed:', error);
            this.adminMain.showNotification('Не удалось выполнить действие', 'error');
        }
    }
    
    switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tab);
        });
        
        this.filters.status = tab === 'all' ? null : tab;
        this.pagination.offset = 0;
        this.loadSubmissions();
    }
    
    toggleFilters() {
        const body = document.getElementById('filtersBody');
        if (body) {
            body.style.display = body.style.display === 'none' ? 'block' : 'none';
        }
    }
    
    resetFilters() {
        this.filters = {
            status: this.currentTab === 'all' ? null : this.currentTab,
            form_id: null,
            date_from: null,
            date_to: null,
            search: null
        };
        
        document.getElementById('formFilter').value = '';
        document.getElementById('dateFromFilter').value = '';
        document.getElementById('dateToFilter').value = '';
        document.getElementById('searchFilter').value = '';
        
        this.loadSubmissions();
    }
    
    updateCounts(counts) {
        document.getElementById('pendingCount').textContent = counts.pending || 0;
        document.getElementById('processedCount').textContent = counts.processed || 0;
        document.getElementById('archivedCount').textContent = counts.archived || 0;
        document.getElementById('allCount').textContent = (counts.pending || 0) + (counts.processed || 0) + (counts.archived || 0);
        
        const badge = document.getElementById('submissionsBadge');
        if (badge) {
            const pending = counts.pending || 0;
            if (pending > 0) {
                badge.textContent = pending;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    renderPagination() {
        const container = document.getElementById('submissionsPagination');
        if (!container) return;
        
        const totalPages = Math.ceil(this.pagination.total / this.pagination.limit);
        const currentPage = Math.floor(this.pagination.offset / this.pagination.limit) + 1;
        
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let html = '';
        
        if (currentPage > 1) {
            html += `<button class="btn btn-sm btn-outline" onclick="submissionsModule.goToPage(${currentPage - 1})">←</button>`;
        }
        
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                html += `<button class="btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline'}" 
                        onclick="submissionsModule.goToPage(${i})">${i}</button>`;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                html += '<span>...</span>';
            }
        }
        
        if (currentPage < totalPages) {
            html += `<button class="btn btn-sm btn-outline" onclick="submissionsModule.goToPage(${currentPage + 1})">→</button>`;
        }
        
        container.innerHTML = html;
    }
    
    goToPage(page) {
        this.pagination.offset = (page - 1) * this.pagination.limit;
        this.loadSubmissions();
    }
    
    updateInfo() {
        const info = document.getElementById('submissionsInfo');
        const paginationInfo = document.getElementById('paginationInfo');
        
        if (info) {
            info.textContent = `Всего заявок: ${this.pagination.total}`;
        }
        
        if (paginationInfo) {
            const from = this.pagination.offset + 1;
            const to = Math.min(this.pagination.offset + this.pagination.limit, this.pagination.total);
            paginationInfo.textContent = `Показано ${from}-${to} из ${this.pagination.total}`;
        }
    }
    
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }
    
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
    
    closeDetailModal() {
        this.closeModal('submissionDetailModal');
    }
    
    closeBulkActionsModal() {
        this.closeModal('bulkActionsModal');
    }
    
    closeChangeStatusModal() {
        this.closeModal('changeStatusModal');
    }
    
    getStatusBadgeClass(status) {
        const classes = {
            pending: 'badge-warning',
            processed: 'badge-success',
            archived: 'badge-secondary'
        };
        return classes[status] || 'badge-secondary';
    }
    
    getStatusLabel(status) {
        const labels = {
            pending: 'Ожидает',
            processed: 'Обработана',
            archived: 'Архив'
        };
        return labels[status] || status;
    }
    
    formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

const submissionsModule = new SubmissionsModule();
document.addEventListener('DOMContentLoaded', () => submissionsModule.init());
