// ========================================
// Orders Module (v3.0) - Comprehensive Orders Workspace
// Server-side pagination, advanced filtering, presets, bulk actions, exports
// ========================================

class OrdersModule {
    constructor() {
        this.orders = [];
        this.currentPage = 1;
        this.perPage = 20;
        this.total = 0;
        this.activeTab = 'active';
        this.filters = {
            status: '',
            type: '',
            dateFrom: '',
            dateTo: '',
            search: '',
            archived: false
        };
        this.sorting = {
            sortBy: 'created_at',
            sortOrder: 'desc'
        };
        this.selectedOrders = new Set();
        this.filterPresets = this.loadPresets();
        this.refreshInterval = null;
        this.lastUpdated = null;
    }
    
    async init() {
        console.log('📦 Loading orders workspace...');
        
        if (!window.adminApi) {
            console.warn('⚠️ adminApi not ready yet, retrying...');
            setTimeout(() => this.init(), 100);
            return;
        }
        
        this.initEventListeners();
        this.loadPresetsUI();
        await this.loadOrders();
        this.startAutoRefresh();
    }
    
    initEventListeners() {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.switchTab(e.target.closest('.tab-btn').dataset.tab);
            });
        });
        
        document.getElementById('statusFilter')?.addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.applyFilters();
        });
        
        document.getElementById('typeFilter')?.addEventListener('change', (e) => {
            this.filters.type = e.target.value;
            this.applyFilters();
        });
        
        document.getElementById('dateFromFilter')?.addEventListener('change', (e) => {
            this.filters.dateFrom = e.target.value;
            this.applyFilters();
        });
        
        document.getElementById('dateToFilter')?.addEventListener('change', (e) => {
            this.filters.dateTo = e.target.value;
            this.applyFilters();
        });
        
        const searchInput = document.getElementById('searchFilter');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.filters.search = e.target.value;
                    this.applyFilters();
                }, 300);
            });
        }
        
        document.getElementById('sortBySelect')?.addEventListener('change', (e) => {
            this.sorting.sortBy = e.target.value;
            this.applyFilters();
        });
        
        document.getElementById('sortOrderSelect')?.addEventListener('change', (e) => {
            this.sorting.sortOrder = e.target.value;
            this.applyFilters();
        });
        
        document.getElementById('perPageSelect')?.addEventListener('change', (e) => {
            this.perPage = parseInt(e.target.value);
            this.currentPage = 1;
            this.loadOrders();
        });
        
        document.getElementById('refreshBtn')?.addEventListener('click', () => {
            this.loadOrders(true);
        });
        
        document.getElementById('exportBtn')?.addEventListener('click', () => {
            this.openExportModal();
        });
        
        document.getElementById('bulkActionsBtn')?.addEventListener('click', () => {
            this.openBulkActionsModal();
        });
        
        document.getElementById('toggleFiltersBtn')?.addEventListener('click', () => {
            this.toggleFilters();
        });
        
        document.getElementById('resetFiltersBtn')?.addEventListener('click', () => {
            this.resetFilters();
        });
        
        document.getElementById('savePresetBtn')?.addEventListener('click', () => {
            this.openSavePresetModal();
        });
        
        document.getElementById('presetSelect')?.addEventListener('change', (e) => {
            if (e.target.value) {
                this.loadPreset(e.target.value);
            }
        });
        
        document.getElementById('deletePresetBtn')?.addEventListener('click', () => {
            const select = document.getElementById('presetSelect');
            if (select && select.value) {
                this.deletePreset(select.value);
            }
        });
    }
    
    switchTab(tab) {
        this.activeTab = tab;
        
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tab);
        });
        
        if (tab === 'active') {
            this.filters.archived = false;
        } else if (tab === 'archived') {
            this.filters.archived = true;
        } else {
            this.filters.archived = null;
        }
        
        this.currentPage = 1;
        this.loadOrders();
    }
    
    async loadOrders(showRefreshing = false) {
        const container = document.getElementById('ordersTable');
        if (!container) return;
        
        try {
            if (showRefreshing) {
                const refreshBtn = document.getElementById('refreshBtn');
                if (refreshBtn) {
                    refreshBtn.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Обновление...';
                    refreshBtn.disabled = true;
                }
            } else {
                AdminMain.prototype.showLoading(container);
            }
            
            const params = this.buildApiParams();
            const response = await window.adminApi.request(`orders.php?${new URLSearchParams(params).toString()}`, 'GET');
            
            this.orders = response.orders || [];
            this.total = response.meta?.total || 0;
            this.lastUpdated = new Date();
            
            this.updateTabCounts(response.meta);
            this.renderOrders();
            this.renderPagination(response.meta);
            this.updateOrdersInfo(response.meta);
            this.renderActiveFilters();
            
            console.log(`✅ Loaded ${this.orders.length} orders (${this.total} total)`);
            
            if (showRefreshing) {
                const refreshBtn = document.getElementById('refreshBtn');
                if (refreshBtn) {
                    refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Обновить';
                    refreshBtn.disabled = false;
                }
                AdminMain.prototype.showToast('Данные обновлены', 'success');
            }
        } catch (error) {
            console.error('❌ Failed to load orders:', error);
            AdminMain.prototype.showError(container, 'Ошибка загрузки заказов');
            AdminMain.prototype.showToast('Ошибка загрузки заказов', 'error');
        }
    }
    
    buildApiParams() {
        const params = {
            limit: this.perPage,
            offset: (this.currentPage - 1) * this.perPage,
            sort_by: this.sorting.sortBy,
            sort_order: this.sorting.sortOrder,
            with_relations: 'false'
        };
        
        if (this.filters.status) params.status = this.filters.status;
        if (this.filters.type) params.type = this.filters.type;
        if (this.filters.dateFrom) params.date_from = this.filters.dateFrom;
        if (this.filters.dateTo) params.date_to = this.filters.dateTo;
        if (this.filters.search) params.search = this.filters.search;
        
        if (this.filters.archived === true) {
            params.archived = 'true';
        } else if (this.filters.archived === false) {
            params.archived = 'false';
        }
        
        return params;
    }
    
    updateTabCounts(meta) {
        if (!meta) return;
        
        const activeCount = document.getElementById('activeCount');
        const archivedCount = document.getElementById('archivedCount');
        const allCount = document.getElementById('allCount');
        
        if (this.activeTab === 'active' && activeCount) {
            activeCount.textContent = this.total;
        } else if (this.activeTab === 'archived' && archivedCount) {
            archivedCount.textContent = this.total;
        } else if (this.activeTab === 'all' && allCount) {
            allCount.textContent = this.total;
        }
    }
    
    renderOrders() {
        const container = document.getElementById('ordersTable');
        if (!container) return;
        
        if (this.orders.length === 0) {
            AdminMain.prototype.showEmpty(container, 'Заказы не найдены');
            return;
        }
        
        container.innerHTML = `
            <table class="data-table orders-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="selectAll" onchange="ordersModule.toggleSelectAll(this.checked)">
                        </th>
                        <th style="width: 120px;">Номер</th>
                        <th style="width: 100px;">Тип</th>
                        <th>Клиент</th>
                        <th>Контакты</th>
                        <th>Услуга/Тема</th>
                        <th style="width: 100px;">Сумма</th>
                        <th style="width: 130px;">Статус</th>
                        <th style="width: 160px;">Дата</th>
                        <th style="width: 100px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    ${this.orders.map(order => this.renderOrderRow(order)).join('')}
                </tbody>
            </table>
        `;
        
        this.updateBulkActionsButton();
    }
    
    renderOrderRow(order) {
        const typeBadge = order.type === 'contact' 
            ? '<span class="badge badge-info">Обращение</span>'
            : '<span class="badge badge-primary">Заказ</span>';
        
        const statusBadges = {
            'new': '<span class="badge badge-new">Новый</span>',
            'processing': '<span class="badge badge-processing">В работе</span>',
            'completed': '<span class="badge badge-completed">Выполнен</span>',
            'cancelled': '<span class="badge badge-cancelled">Отменён</span>'
        };
        
        const isArchived = order.archived_at !== null;
        const rowClass = isArchived ? 'archived-row' : '';
        const isSelected = this.selectedOrders.has(order.id);
        
        return `
            <tr data-order-id="${order.id}" class="${rowClass}">
                <td>
                    <input type="checkbox" class="order-checkbox" value="${order.id}" 
                           ${isSelected ? 'checked' : ''} 
                           onchange="ordersModule.toggleOrderSelection('${order.id}', this.checked)">
                </td>
                <td>
                    <div class="order-number-cell">
                        <strong>#${this.escapeHtml(order.order_number || order.id.substring(0, 8))}</strong>
                        ${isArchived ? '<i class="fas fa-archive" title="В архиве"></i>' : ''}
                    </div>
                </td>
                <td>${typeBadge}</td>
                <td class="customer-cell">
                    <strong>${this.escapeHtml(order.name)}</strong>
                </td>
                <td>
                    <div class="contact-info">
                        ${order.email ? `<div><i class="fas fa-envelope"></i> ${this.escapeHtml(order.email)}</div>` : ''}
                        ${order.phone ? `<div><i class="fas fa-phone"></i> ${this.escapeHtml(order.phone)}</div>` : ''}
                    </div>
                </td>
                <td>${this.escapeHtml(order.service || order.subject || '-')}</td>
                <td><strong>${AdminMain.prototype.formatMoney(order.amount)}</strong></td>
                <td>${statusBadges[order.status] || order.status}</td>
                <td>${AdminMain.prototype.formatDate(order.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-icon" onclick="orderDetailModule.openDrawer('${order.id}')" title="Подробнее">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-icon" onclick="ordersModule.quickStatusChange('${order.id}')" title="Изменить статус">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }
    
    renderPagination(meta) {
        const container = document.getElementById('ordersPagination');
        if (!container) return;
        
        const totalPages = Math.ceil(this.total / this.perPage);
        
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let html = '<div class="pagination">';
        
        html += `<button class="pagination-btn" ${this.currentPage === 1 ? 'disabled' : ''} 
                    onclick="ordersModule.goToPage(${this.currentPage - 1})">
                    <i class="fas fa-chevron-left"></i>
                </button>`;
        
        const maxButtons = 7;
        let startPage = Math.max(1, this.currentPage - Math.floor(maxButtons / 2));
        let endPage = Math.min(totalPages, startPage + maxButtons - 1);
        
        if (endPage - startPage < maxButtons - 1) {
            startPage = Math.max(1, endPage - maxButtons + 1);
        }
        
        if (startPage > 1) {
            html += `<button class="pagination-btn" onclick="ordersModule.goToPage(1)">1</button>`;
            if (startPage > 2) {
                html += '<span class="pagination-dots">...</span>';
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<button class="pagination-btn ${i === this.currentPage ? 'active' : ''}" 
                        onclick="ordersModule.goToPage(${i})">${i}</button>`;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += '<span class="pagination-dots">...</span>';
            }
            html += `<button class="pagination-btn" onclick="ordersModule.goToPage(${totalPages})">${totalPages}</button>`;
        }
        
        html += `<button class="pagination-btn" ${this.currentPage === totalPages ? 'disabled' : ''} 
                    onclick="ordersModule.goToPage(${this.currentPage + 1})">
                    <i class="fas fa-chevron-right"></i>
                </button>`;
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    updateOrdersInfo(meta) {
        const info = document.getElementById('ordersInfo');
        if (!info) return;
        
        const start = (this.currentPage - 1) * this.perPage + 1;
        const end = Math.min(this.currentPage * this.perPage, this.total);
        
        info.textContent = `Показаны ${start}-${end} из ${this.total}`;
        
        const paginationInfo = document.getElementById('paginationInfo');
        if (paginationInfo) {
            paginationInfo.textContent = `Страница ${this.currentPage} из ${Math.ceil(this.total / this.perPage) || 1}`;
        }
    }
    
    renderActiveFilters() {
        const container = document.getElementById('activeFilters');
        if (!container) return;
        
        const activeFilters = [];
        
        if (this.filters.status) {
            const statusLabels = {
                'new': 'Новые',
                'processing': 'В работе',
                'completed': 'Выполнены',
                'cancelled': 'Отменены'
            };
            activeFilters.push({
                label: `Статус: ${statusLabels[this.filters.status]}`,
                key: 'status'
            });
        }
        
        if (this.filters.type) {
            const typeLabels = {
                'order': 'Заказы',
                'contact': 'Обращения'
            };
            activeFilters.push({
                label: `Тип: ${typeLabels[this.filters.type]}`,
                key: 'type'
            });
        }
        
        if (this.filters.dateFrom || this.filters.dateTo) {
            const from = this.filters.dateFrom ? new Date(this.filters.dateFrom).toLocaleDateString('ru-RU') : '...';
            const to = this.filters.dateTo ? new Date(this.filters.dateTo).toLocaleDateString('ru-RU') : '...';
            activeFilters.push({
                label: `Период: ${from} — ${to}`,
                key: 'dates'
            });
        }
        
        if (this.filters.search) {
            activeFilters.push({
                label: `Поиск: "${this.filters.search}"`,
                key: 'search'
            });
        }
        
        if (activeFilters.length === 0) {
            container.innerHTML = '<small>Фильтры не применены</small>';
            return;
        }
        
        container.innerHTML = activeFilters.map(filter => `
            <span class="filter-badge">
                ${filter.label}
                <button onclick="ordersModule.removeFilter('${filter.key}')">
                    <i class="fas fa-times"></i>
                </button>
            </span>
        `).join('');
    }
    
    removeFilter(key) {
        switch (key) {
            case 'status':
                this.filters.status = '';
                document.getElementById('statusFilter').value = '';
                break;
            case 'type':
                this.filters.type = '';
                document.getElementById('typeFilter').value = '';
                break;
            case 'dates':
                this.filters.dateFrom = '';
                this.filters.dateTo = '';
                document.getElementById('dateFromFilter').value = '';
                document.getElementById('dateToFilter').value = '';
                break;
            case 'search':
                this.filters.search = '';
                document.getElementById('searchFilter').value = '';
                break;
        }
        this.applyFilters();
    }
    
    applyFilters() {
        this.currentPage = 1;
        this.loadOrders();
    }
    
    resetFilters() {
        this.filters = {
            status: '',
            type: '',
            dateFrom: '',
            dateTo: '',
            search: '',
            archived: this.filters.archived
        };
        
        document.getElementById('statusFilter').value = '';
        document.getElementById('typeFilter').value = '';
        document.getElementById('dateFromFilter').value = '';
        document.getElementById('dateToFilter').value = '';
        document.getElementById('searchFilter').value = '';
        
        this.applyFilters();
    }
    
    toggleFilters() {
        const body = document.getElementById('filtersBody');
        if (body) {
            body.style.display = body.style.display === 'none' ? 'block' : 'none';
        }
    }
    
    goToPage(page) {
        const totalPages = Math.ceil(this.total / this.perPage);
        if (page < 1 || page > totalPages) return;
        
        this.currentPage = page;
        this.loadOrders();
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    toggleSelectAll(checked) {
        this.selectedOrders.clear();
        
        if (checked) {
            this.orders.forEach(order => {
                this.selectedOrders.add(order.id);
            });
        }
        
        document.querySelectorAll('.order-checkbox').forEach(checkbox => {
            checkbox.checked = checked;
        });
        
        this.updateBulkActionsButton();
    }
    
    toggleOrderSelection(orderId, checked) {
        if (checked) {
            this.selectedOrders.add(orderId);
        } else {
            this.selectedOrders.delete(orderId);
        }
        
        const allCheckboxes = document.querySelectorAll('.order-checkbox');
        const checkedCheckboxes = document.querySelectorAll('.order-checkbox:checked');
        const selectAllCheckbox = document.getElementById('selectAll');
        
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length && allCheckboxes.length > 0;
        }
        
        this.updateBulkActionsButton();
    }
    
    updateBulkActionsButton() {
        const btn = document.getElementById('bulkActionsBtn');
        if (btn) {
            btn.disabled = this.selectedOrders.size === 0;
            btn.innerHTML = `
                <i class="fas fa-tasks"></i>
                Групповые действия ${this.selectedOrders.size > 0 ? `(${this.selectedOrders.size})` : ''}
            `;
        }
    }
    
    async quickStatusChange(orderId) {
        const order = this.orders.find(o => o.id === orderId);
        if (!order) return;
        
        const statuses = [
            { value: 'new', label: 'Новый' },
            { value: 'processing', label: 'В работе' },
            { value: 'completed', label: 'Выполнен' },
            { value: 'cancelled', label: 'Отменён' }
        ];
        
        const options = statuses.map(s => 
            `<option value="${s.value}" ${order.status === s.value ? 'selected' : ''}>${s.label}</option>`
        ).join('');
        
        const result = prompt(`Изменить статус заказа #${order.order_number || order.id.substring(0, 8)}:\n\nВыберите новый статус:\n${statuses.map((s, i) => `${i + 1}. ${s.label}`).join('\n')}\n\nВведите номер (1-4):`);
        
        if (!result) return;
        
        const index = parseInt(result) - 1;
        if (index < 0 || index >= statuses.length) {
            AdminMain.prototype.showToast('Неверный выбор', 'error');
            return;
        }
        
        const newStatus = statuses[index].value;
        if (newStatus === order.status) {
            AdminMain.prototype.showToast('Статус не изменился', 'info');
            return;
        }
        
        const comment = prompt('Комментарий (необязательно):');
        
        try {
            await window.adminApi.request(`/api/orders.php?action=status&id=${orderId}`, 'PATCH', {
                status: newStatus,
                comment: comment || null
            });
            
            AdminMain.prototype.showToast('Статус обновлён', 'success');
            await this.loadOrders();
        } catch (error) {
            console.error('❌ Failed to change status:', error);
            AdminMain.prototype.showToast('Ошибка изменения статуса', 'error');
        }
    }
    
    openExportModal() {
        const modal = document.getElementById('exportModal');
        if (modal) {
            document.getElementById('exportDateFrom').value = this.filters.dateFrom || '';
            document.getElementById('exportDateTo').value = this.filters.dateTo || '';
            modal.classList.add('show');
        }
    }
    
    closeExportModal() {
        const modal = document.getElementById('exportModal');
        if (modal) {
            modal.classList.remove('show');
        }
    }
    
    async performExport() {
        const format = document.querySelector('input[name="exportFormat"]:checked').value;
        const dateFrom = document.getElementById('exportDateFrom').value;
        const dateTo = document.getElementById('exportDateTo').value;
        const applyFilters = document.getElementById('applyCurrentFilters').checked;
        
        const selectedColumns = Array.from(document.querySelectorAll('input[name="exportColumn"]:checked'))
            .map(cb => cb.value);
        
        if (selectedColumns.length === 0) {
            AdminMain.prototype.showToast('Выберите хотя бы одну колонку', 'warning');
            return;
        }
        
        const params = {
            format: format,
            fields: selectedColumns.join(',')
        };
        
        if (applyFilters) {
            if (this.filters.status) params.status = this.filters.status;
            if (this.filters.type) params.type = this.filters.type;
            if (this.filters.search) params.search = this.filters.search;
            if (this.filters.archived !== null) params.archived = this.filters.archived ? 'true' : 'false';
        }
        
        if (dateFrom) params.date_from = dateFrom;
        if (dateTo) params.date_to = dateTo;
        
        try {
            const response = await window.adminApi.request(`/api/orders/export.php?${new URLSearchParams(params).toString()}`, 'GET');
            
            if (response.url) {
                window.location.href = response.url;
                AdminMain.prototype.showToast('Экспорт начат', 'success');
                this.closeExportModal();
            } else {
                throw new Error('No export URL returned');
            }
        } catch (error) {
            console.error('❌ Export failed:', error);
            AdminMain.prototype.showToast('Ошибка экспорта', 'error');
        }
    }
    
    openBulkActionsModal() {
        const modal = document.getElementById('bulkActionsModal');
        if (modal) {
            document.getElementById('bulkSelectedCount').textContent = this.selectedOrders.size;
            modal.classList.add('show');
        }
    }
    
    closeBulkActionsModal() {
        const modal = document.getElementById('bulkActionsModal');
        if (modal) {
            modal.classList.remove('show');
        }
    }
    
    async bulkArchive() {
        if (this.selectedOrders.size === 0) return;
        
        if (!confirm(`Архивировать ${this.selectedOrders.size} заказ(ов)?`)) {
            return;
        }
        
        let success = 0;
        let failed = 0;
        
        for (const orderId of this.selectedOrders) {
            try {
                await window.adminApi.request(`/api/orders.php?action=archive&id=${orderId}`, 'PATCH', {});
                success++;
            } catch (error) {
                console.error(`Failed to archive order ${orderId}:`, error);
                failed++;
            }
        }
        
        AdminMain.prototype.showToast(`Архивировано: ${success}, ошибок: ${failed}`, success > 0 ? 'success' : 'error');
        
        this.selectedOrders.clear();
        this.closeBulkActionsModal();
        await this.loadOrders();
    }
    
    async bulkUnarchive() {
        if (this.selectedOrders.size === 0) return;
        
        if (!confirm(`Разархивировать ${this.selectedOrders.size} заказ(ов)?`)) {
            return;
        }
        
        let success = 0;
        let failed = 0;
        
        for (const orderId of this.selectedOrders) {
            try {
                await window.adminApi.request(`/api/orders.php?action=unarchive&id=${orderId}`, 'PATCH', {});
                success++;
            } catch (error) {
                console.error(`Failed to unarchive order ${orderId}:`, error);
                failed++;
            }
        }
        
        AdminMain.prototype.showToast(`Разархивировано: ${success}, ошибок: ${failed}`, success > 0 ? 'success' : 'error');
        
        this.selectedOrders.clear();
        this.closeBulkActionsModal();
        await this.loadOrders();
    }
    
    async bulkChangeStatus() {
        if (this.selectedOrders.size === 0) return;
        
        const result = prompt(`Изменить статус для ${this.selectedOrders.size} заказ(ов):\n\n1. Новый\n2. В работе\n3. Выполнен\n4. Отменён\n\nВведите номер (1-4):`);
        
        if (!result) return;
        
        const statuses = ['new', 'processing', 'completed', 'cancelled'];
        const index = parseInt(result) - 1;
        
        if (index < 0 || index >= statuses.length) {
            AdminMain.prototype.showToast('Неверный выбор', 'error');
            return;
        }
        
        const newStatus = statuses[index];
        const comment = prompt('Комментарий (необязательно):');
        
        let success = 0;
        let failed = 0;
        
        for (const orderId of this.selectedOrders) {
            try {
                await window.adminApi.request(`/api/orders.php?action=status&id=${orderId}`, 'PATCH', {
                    status: newStatus,
                    comment: comment || null
                });
                success++;
            } catch (error) {
                console.error(`Failed to update status for order ${orderId}:`, error);
                failed++;
            }
        }
        
        AdminMain.prototype.showToast(`Обновлено: ${success}, ошибок: ${failed}`, success > 0 ? 'success' : 'error');
        
        this.selectedOrders.clear();
        this.closeBulkActionsModal();
        await this.loadOrders();
    }
    
    loadPresets() {
        const stored = localStorage.getItem('ordersFilterPresets');
        return stored ? JSON.parse(stored) : {};
    }
    
    savePresetsToStorage() {
        localStorage.setItem('ordersFilterPresets', JSON.stringify(this.filterPresets));
    }
    
    loadPresetsUI() {
        const select = document.getElementById('presetSelect');
        if (!select) return;
        
        select.innerHTML = '<option value="">Загрузить пресет...</option>';
        
        Object.keys(this.filterPresets).forEach(name => {
            const option = document.createElement('option');
            option.value = name;
            option.textContent = name;
            select.appendChild(option);
        });
        
        const deleteBtn = document.getElementById('deletePresetBtn');
        if (deleteBtn) {
            deleteBtn.disabled = Object.keys(this.filterPresets).length === 0;
        }
    }
    
    openSavePresetModal() {
        const modal = document.getElementById('savePresetModal');
        if (modal) {
            document.getElementById('presetName').value = '';
            modal.classList.add('show');
        }
    }
    
    closeSavePresetModal() {
        const modal = document.getElementById('savePresetModal');
        if (modal) {
            modal.classList.remove('show');
        }
    }
    
    savePreset() {
        const name = document.getElementById('presetName').value.trim();
        if (!name) {
            AdminMain.prototype.showToast('Введите название пресета', 'warning');
            return;
        }
        
        this.filterPresets[name] = {
            filters: { ...this.filters },
            sorting: { ...this.sorting }
        };
        
        this.savePresetsToStorage();
        this.loadPresetsUI();
        this.closeSavePresetModal();
        
        AdminMain.prototype.showToast('Пресет сохранён', 'success');
    }
    
    loadPreset(name) {
        const preset = this.filterPresets[name];
        if (!preset) return;
        
        this.filters = { ...preset.filters };
        this.sorting = { ...preset.sorting };
        
        document.getElementById('statusFilter').value = this.filters.status || '';
        document.getElementById('typeFilter').value = this.filters.type || '';
        document.getElementById('dateFromFilter').value = this.filters.dateFrom || '';
        document.getElementById('dateToFilter').value = this.filters.dateTo || '';
        document.getElementById('searchFilter').value = this.filters.search || '';
        document.getElementById('sortBySelect').value = this.sorting.sortBy;
        document.getElementById('sortOrderSelect').value = this.sorting.sortOrder;
        
        this.applyFilters();
        
        AdminMain.prototype.showToast(`Пресет "${name}" загружен`, 'success');
        
        const deleteBtn = document.getElementById('deletePresetBtn');
        if (deleteBtn) {
            deleteBtn.disabled = false;
        }
    }
    
    deletePreset(name) {
        if (!confirm(`Удалить пресет "${name}"?`)) {
            return;
        }
        
        delete this.filterPresets[name];
        this.savePresetsToStorage();
        this.loadPresetsUI();
        
        const select = document.getElementById('presetSelect');
        if (select) {
            select.value = '';
        }
        
        AdminMain.prototype.showToast('Пресет удалён', 'success');
    }
    
    startAutoRefresh() {
        this.refreshInterval = setInterval(() => {
            this.loadOrders(false);
        }, 60000);
    }
    
    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }
    
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.ordersModule = new OrdersModule();
        window.ordersModule.init();
    });
} else {
    window.ordersModule = new OrdersModule();
    window.ordersModule.init();
}
