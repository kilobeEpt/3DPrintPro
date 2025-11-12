// ========================================
// Orders Module - Orders Management & CRUD
// ========================================

class OrdersModule {
    constructor() {
        this.orders = [];
        this.filteredOrders = [];
        this.currentPage = 1;
        this.perPage = 20;
        this.filters = {
            status: 'all',
            type: 'all',
            search: ''
        };
    }
    
    async init() {
        console.log('📦 Loading orders...');
        this.initFilters();
        await this.loadOrders();
    }
    
    initFilters() {
        // Status filter
        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.filters.status = e.target.value;
                this.applyFilters();
            });
        }
        
        // Type filter
        const typeFilter = document.getElementById('typeFilter');
        if (typeFilter) {
            typeFilter.addEventListener('change', (e) => {
                this.filters.type = e.target.value;
                this.applyFilters();
            });
        }
        
        // Search filter
        const searchFilter = document.getElementById('searchFilter');
        if (searchFilter) {
            searchFilter.addEventListener('input', (e) => {
                this.filters.search = e.target.value.toLowerCase();
                this.applyFilters();
            });
        }
        
        // Export button
        const exportBtn = document.getElementById('exportOrdersBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportOrders());
        }
    }
    
    async loadOrders() {
        const container = document.getElementById('ordersTable');
        if (!container) return;
        
        try {
            AdminMain.prototype.showLoading(container);
            
            this.orders = await adminApi.getOrders();
            this.orders.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            
            this.applyFilters();
            console.log(`✅ Loaded ${this.orders.length} orders`);
        } catch (error) {
            console.error('❌ Failed to load orders:', error);
            AdminMain.prototype.showError(container, 'Ошибка загрузки заказов');
            AdminMain.prototype.showToast('Ошибка загрузки заказов', 'error');
        }
    }
    
    applyFilters() {
        this.filteredOrders = this.orders.filter(order => {
            // Status filter
            if (this.filters.status !== 'all' && order.status !== this.filters.status) {
                return false;
            }
            
            // Type filter
            if (this.filters.type !== 'all' && order.type !== this.filters.type) {
                return false;
            }
            
            // Search filter
            if (this.filters.search) {
                const searchStr = this.filters.search;
                const searchFields = [
                    order.name,
                    order.email,
                    order.phone,
                    order.service,
                    order.subject,
                    order.order_number
                ].filter(Boolean).join(' ').toLowerCase();
                
                if (!searchFields.includes(searchStr)) {
                    return false;
                }
            }
            
            return true;
        });
        
        this.currentPage = 1;
        this.renderOrders();
        this.renderPagination();
    }
    
    renderOrders() {
        const container = document.getElementById('ordersTable');
        if (!container) return;
        
        if (this.filteredOrders.length === 0) {
            AdminMain.prototype.showEmpty(container, 'Заказы не найдены');
            return;
        }
        
        const start = (this.currentPage - 1) * this.perPage;
        const end = start + this.perPage;
        const pageOrders = this.filteredOrders.slice(start, end);
        
        container.innerHTML = `
            <table class="data-table">
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
                        <th style="width: 120px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    ${pageOrders.map(order => this.renderOrderRow(order)).join('')}
                </tbody>
            </table>
        `;
    }
    
    renderOrderRow(order) {
        const typeBadge = order.type === 'contact' 
            ? '<span class="badge badge-info">Обращение</span>'
            : '<span class="badge badge-primary">Заказ</span>';
        
        return `
            <tr data-order-id="${order.id}">
                <td>
                    <input type="checkbox" class="order-checkbox" value="${order.id}">
                </td>
                <td>
                    <strong>#${this.escapeHtml(order.order_number || order.id.substring(0, 8))}</strong>
                </td>
                <td>${typeBadge}</td>
                <td>${this.escapeHtml(order.name)}</td>
                <td>
                    <div class="contact-info">
                        ${order.email ? `<div><i class="fas fa-envelope"></i> ${this.escapeHtml(order.email)}</div>` : ''}
                        ${order.phone ? `<div><i class="fas fa-phone"></i> ${this.escapeHtml(order.phone)}</div>` : ''}
                    </div>
                </td>
                <td>${this.escapeHtml(order.service || order.subject || '-')}</td>
                <td><strong>${AdminMain.prototype.formatMoney(order.amount)}</strong></td>
                <td>
                    <select class="status-select" data-order-id="${order.id}" onchange="ordersModule.updateStatus('${order.id}', this.value)">
                        <option value="new" ${order.status === 'new' ? 'selected' : ''}>Новый</option>
                        <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>В работе</option>
                        <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>Выполнен</option>
                        <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Отменён</option>
                    </select>
                </td>
                <td>${AdminMain.prototype.formatDate(order.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-icon" onclick="ordersModule.viewOrder('${order.id}')" title="Просмотр">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-icon btn-danger" onclick="ordersModule.deleteOrder('${order.id}')" title="Удалить">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }
    
    renderPagination() {
        const container = document.getElementById('ordersPagination');
        if (!container) return;
        
        const totalPages = Math.ceil(this.filteredOrders.length / this.perPage);
        
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let html = '<div class="pagination">';
        
        // Previous button
        html += `<button class="pagination-btn" ${this.currentPage === 1 ? 'disabled' : ''} 
                    onclick="ordersModule.goToPage(${this.currentPage - 1})">
                    <i class="fas fa-chevron-left"></i>
                </button>`;
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= this.currentPage - 2 && i <= this.currentPage + 2)) {
                html += `<button class="pagination-btn ${i === this.currentPage ? 'active' : ''}" 
                            onclick="ordersModule.goToPage(${i})">${i}</button>`;
            } else if (i === this.currentPage - 3 || i === this.currentPage + 3) {
                html += '<span class="pagination-dots">...</span>';
            }
        }
        
        // Next button
        html += `<button class="pagination-btn" ${this.currentPage === totalPages ? 'disabled' : ''} 
                    onclick="ordersModule.goToPage(${this.currentPage + 1})">
                    <i class="fas fa-chevron-right"></i>
                </button>`;
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    goToPage(page) {
        const totalPages = Math.ceil(this.filteredOrders.length / this.perPage);
        if (page < 1 || page > totalPages) return;
        
        this.currentPage = page;
        this.renderOrders();
        this.renderPagination();
    }
    
    async updateStatus(orderId, newStatus) {
        try {
            await adminApi.updateOrder(orderId, { status: newStatus });
            
            // Update local data
            const order = this.orders.find(o => o.id === orderId);
            if (order) {
                order.status = newStatus;
            }
            
            AdminMain.prototype.showToast('Статус обновлён', 'success');
            AdminMain.prototype.checkOrdersBadge();
        } catch (error) {
            console.error('❌ Failed to update order status:', error);
            AdminMain.prototype.showToast('Ошибка обновления статуса', 'error');
            await this.loadOrders(); // Reload to reset
        }
    }
    
    viewOrder(orderId) {
        location.href = `/admin/orders.php?id=${orderId}`;
    }
    
    async deleteOrder(orderId) {
        if (!AdminMain.prototype.showConfirm('Вы уверены, что хотите удалить этот заказ?')) {
            return;
        }
        
        try {
            await adminApi.deleteOrder(orderId);
            this.orders = this.orders.filter(o => o.id !== orderId);
            this.applyFilters();
            AdminMain.prototype.showToast('Заказ удалён', 'success');
        } catch (error) {
            console.error('❌ Failed to delete order:', error);
            AdminMain.prototype.showToast('Ошибка удаления заказа', 'error');
        }
    }
    
    toggleSelectAll(checked) {
        document.querySelectorAll('.order-checkbox').forEach(checkbox => {
            checkbox.checked = checked;
        });
    }
    
    exportOrders() {
        const csv = this.ordersToCSV(this.filteredOrders);
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `orders_${new Date().toISOString().split('T')[0]}.csv`;
        link.click();
        
        AdminMain.prototype.showToast('Заказы экспортированы', 'success');
    }
    
    ordersToCSV(orders) {
        const headers = ['Номер', 'Тип', 'Клиент', 'Email', 'Телефон', 'Услуга', 'Сумма', 'Статус', 'Дата'];
        const rows = orders.map(o => [
            o.order_number || o.id.substring(0, 8),
            o.type === 'contact' ? 'Обращение' : 'Заказ',
            o.name,
            o.email || '',
            o.phone || '',
            o.service || o.subject || '',
            o.amount || 0,
            o.status,
            o.created_at
        ]);
        
        return [headers, ...rows].map(row => 
            row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')
        ).join('\n');
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.ordersModule = new OrdersModule();
        window.ordersModule.init();
    });
} else {
    window.ordersModule = new OrdersModule();
    window.ordersModule.init();
}
