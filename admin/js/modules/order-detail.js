// ========================================
// Order Detail Module - Detailed Order View with History & Notes
// ========================================

class OrderDetailModule {
    constructor() {
        this.currentOrder = null;
        this.editingNoteId = null;
    }
    
    init() {
        console.log('📋 Order Detail module initialized');
        
        const closeBtn = document.getElementById('closeDrawerBtn');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeDrawer());
        }
        
        const overlay = document.querySelector('#orderDrawer .drawer-overlay');
        if (overlay) {
            overlay.addEventListener('click', () => this.closeDrawer());
        }
    }
    
    async openDrawer(orderId) {
        const drawer = document.getElementById('orderDrawer');
        const drawerBody = document.getElementById('drawerBody');
        
        if (!drawer || !drawerBody) return;
        
        drawer.classList.add('show');
        drawerBody.innerHTML = '<div class="loading-state"><i class="fas fa-spinner fa-spin"></i><p>Загрузка...</p></div>';
        
        try {
            const response = await window.adminApi.get(`orders.php?id=${orderId}&with_relations=true`);
            this.currentOrder = response.order;
            
            document.getElementById('drawerTitle').textContent = `Заказ #${this.currentOrder.order_number || this.currentOrder.id.substring(0, 8)}`;
            
            this.renderOrderDetail();
        } catch (error) {
            console.error('❌ Failed to load order details:', error);
            drawerBody.innerHTML = '<div class="error-state"><i class="fas fa-exclamation-triangle"></i><p>Ошибка загрузки данных</p></div>';
            AdminMain.prototype.showToast('Ошибка загрузки заказа', 'error');
        }
    }
    
    renderOrderDetail() {
        const drawerBody = document.getElementById('drawerBody');
        if (!drawerBody || !this.currentOrder) return;
        
        const order = this.currentOrder;
        
        drawerBody.innerHTML = `
            <div class="order-detail-sections">
                ${this.renderCustomerSection(order)}
                ${this.renderStatusSection(order)}
                ${this.renderOrderInfoSection(order)}
                ${this.renderCalculatorSection(order)}
                ${this.renderTimelineSection(order)}
                ${this.renderNotesSection(order)}
            </div>
        `;
        
        this.attachEventListeners();
    }
    
    renderCustomerSection(order) {
        return `
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-user"></i> Информация о клиенте</h3>
                </div>
                <div class="section-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Имя</label>
                            <div class="info-value">${this.escapeHtml(order.name)}</div>
                        </div>
                        ${order.email ? `
                            <div class="info-item">
                                <label>Email</label>
                                <div class="info-value">
                                    <a href="mailto:${this.escapeHtml(order.email)}">${this.escapeHtml(order.email)}</a>
                                </div>
                            </div>
                        ` : ''}
                        ${order.phone ? `
                            <div class="info-item">
                                <label>Телефон</label>
                                <div class="info-value">
                                    <a href="tel:${this.escapeHtml(order.phone)}">${this.escapeHtml(order.phone)}</a>
                                </div>
                            </div>
                        ` : ''}
                        ${order.telegram ? `
                            <div class="info-item">
                                <label>Telegram</label>
                                <div class="info-value">${this.escapeHtml(order.telegram)}</div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }
    
    renderStatusSection(order) {
        const isArchived = order.archived_at !== null;
        
        return `
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-info-circle"></i> Статус и управление</h3>
                </div>
                <div class="section-body">
                    <div class="status-controls">
                        <div class="status-change-group">
                            <label>Изменить статус</label>
                            <div class="status-change-input">
                                <select class="form-control" id="newStatusSelect">
                                    <option value="new" ${order.status === 'new' ? 'selected' : ''}>Новый</option>
                                    <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>В работе</option>
                                    <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>Выполнен</option>
                                    <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Отменён</option>
                                </select>
                                <button class="btn btn-primary" onclick="orderDetailModule.changeStatus()">
                                    <i class="fas fa-check"></i>
                                    Применить
                                </button>
                            </div>
                        </div>
                        
                        <div class="archive-control">
                            ${isArchived ? `
                                <button class="btn btn-outline" onclick="orderDetailModule.unarchiveOrder()">
                                    <i class="fas fa-box-open"></i>
                                    Разархивировать
                                </button>
                            ` : `
                                <button class="btn btn-outline" onclick="orderDetailModule.archiveOrder()">
                                    <i class="fas fa-archive"></i>
                                    Архивировать
                                </button>
                            `}
                        </div>
                    </div>
                    
                    ${isArchived ? `
                        <div class="alert alert-info">
                            <i class="fas fa-archive"></i>
                            Заказ в архиве с ${this.formatDate(order.archived_at)}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    renderOrderInfoSection(order) {
        return `
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-clipboard-list"></i> Информация о заказе</h3>
                </div>
                <div class="section-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Номер заказа</label>
                            <div class="info-value"><strong>#${this.escapeHtml(order.order_number || order.id.substring(0, 8))}</strong></div>
                        </div>
                        <div class="info-item">
                            <label>Тип</label>
                            <div class="info-value">
                                ${order.type === 'contact' 
                                    ? '<span class="badge badge-info">Обращение</span>'
                                    : '<span class="badge badge-primary">Заказ</span>'}
                            </div>
                        </div>
                        <div class="info-item">
                            <label>Статус</label>
                            <div class="info-value">${this.renderStatusBadge(order.status)}</div>
                        </div>
                        <div class="info-item">
                            <label>Сумма</label>
                            <div class="info-value"><strong>${this.formatMoney(order.amount)}</strong></div>
                        </div>
                        ${order.service ? `
                            <div class="info-item">
                                <label>Услуга</label>
                                <div class="info-value">${this.escapeHtml(order.service)}</div>
                            </div>
                        ` : ''}
                        ${order.subject ? `
                            <div class="info-item">
                                <label>Тема</label>
                                <div class="info-value">${this.escapeHtml(order.subject)}</div>
                            </div>
                        ` : ''}
                        <div class="info-item">
                            <label>Дата создания</label>
                            <div class="info-value">${this.formatDate(order.created_at)}</div>
                        </div>
                        <div class="info-item">
                            <label>Последнее обновление</label>
                            <div class="info-value">${this.formatDate(order.updated_at)}</div>
                        </div>
                    </div>
                    
                    ${order.message ? `
                        <div class="info-item info-item-full">
                            <label>Сообщение</label>
                            <div class="info-value message-text">${this.escapeHtml(order.message).replace(/\n/g, '<br>')}</div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    renderCalculatorSection(order) {
        if (!order.calculator_data || !order.calculator_data.breakdown) {
            return '';
        }
        
        const calc = order.calculator_data;
        const breakdown = calc.breakdown;
        
        return `
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-calculator"></i> Расчёт стоимости</h3>
                </div>
                <div class="section-body">
                    <div class="calculator-breakdown">
                        ${breakdown.material ? `
                            <div class="breakdown-item">
                                <label>Материал</label>
                                <div class="breakdown-value">
                                    <span>${this.escapeHtml(breakdown.material.name || breakdown.material)}</span>
                                    <strong>${this.formatMoney(breakdown.materialCost || 0)}</strong>
                                </div>
                            </div>
                        ` : ''}
                        
                        ${breakdown.volume ? `
                            <div class="breakdown-item">
                                <label>Объём</label>
                                <div class="breakdown-value">
                                    <span>${breakdown.volume} см³</span>
                                </div>
                            </div>
                        ` : ''}
                        
                        ${breakdown.weight ? `
                            <div class="breakdown-item">
                                <label>Вес</label>
                                <div class="breakdown-value">
                                    <span>${breakdown.weight} г</span>
                                </div>
                            </div>
                        ` : ''}
                        
                        ${breakdown.printTime ? `
                            <div class="breakdown-item">
                                <label>Время печати</label>
                                <div class="breakdown-value">
                                    <span>${breakdown.printTime} ч</span>
                                    <strong>${this.formatMoney(breakdown.printCost || 0)}</strong>
                                </div>
                            </div>
                        ` : ''}
                        
                        ${breakdown.postProcessing ? `
                            <div class="breakdown-item">
                                <label>Постобработка</label>
                                <div class="breakdown-value">
                                    <span>${this.escapeHtml(breakdown.postProcessing)}</span>
                                    <strong>${this.formatMoney(breakdown.postProcessingCost || 0)}</strong>
                                </div>
                            </div>
                        ` : ''}
                        
                        <div class="breakdown-total">
                            <label>Итого</label>
                            <div class="breakdown-value">
                                <strong class="total-amount">${this.formatMoney(order.amount)}</strong>
                            </div>
                        </div>
                    </div>
                    
                    ${calc.files && calc.files.length > 0 ? `
                        <div class="calculator-files">
                            <h4>Прикреплённые файлы</h4>
                            <div class="files-list">
                                ${calc.files.map(file => `
                                    <div class="file-item">
                                        <i class="fas fa-file"></i>
                                        <span>${this.escapeHtml(file.name || file)}</span>
                                        ${file.size ? `<small>${this.formatBytes(file.size)}</small>` : ''}
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    renderTimelineSection(order) {
        const history = order.status_history || [];
        
        if (history.length === 0) {
            return '';
        }
        
        return `
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-history"></i> История изменений</h3>
                </div>
                <div class="section-body">
                    <div class="timeline">
                        ${history.map(item => this.renderTimelineItem(item)).join('')}
                    </div>
                </div>
            </div>
        `;
    }
    
    renderTimelineItem(item) {
        const changedBy = item.changed_by ? item.changed_by.name : 'Система';
        
        return `
            <div class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <div class="timeline-header">
                        <div class="timeline-status">
                            ${item.old_status ? `
                                ${this.renderStatusBadge(item.old_status)}
                                <i class="fas fa-arrow-right"></i>
                            ` : ''}
                            ${this.renderStatusBadge(item.new_status)}
                        </div>
                        <div class="timeline-time">${this.formatDate(item.created_at)}</div>
                    </div>
                    <div class="timeline-body">
                        <div class="timeline-author">
                            <i class="fas fa-user"></i>
                            ${this.escapeHtml(changedBy)}
                        </div>
                        ${item.comment ? `
                            <div class="timeline-comment">${this.escapeHtml(item.comment)}</div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }
    
    renderNotesSection(order) {
        const notes = order.notes || [];
        
        return `
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-sticky-note"></i> Внутренние заметки</h3>
                    <button class="btn btn-sm btn-primary" onclick="orderDetailModule.showAddNoteForm()">
                        <i class="fas fa-plus"></i>
                        Добавить
                    </button>
                </div>
                <div class="section-body">
                    <div id="addNoteForm" class="note-form" style="display: none;">
                        <textarea class="form-control" id="newNoteText" placeholder="Введите заметку..." rows="3"></textarea>
                        <div class="note-form-actions">
                            <button class="btn btn-secondary btn-sm" onclick="orderDetailModule.hideAddNoteForm()">Отмена</button>
                            <button class="btn btn-primary btn-sm" onclick="orderDetailModule.addNote()">
                                <i class="fas fa-save"></i>
                                Сохранить
                            </button>
                        </div>
                    </div>
                    
                    <div class="notes-list" id="notesList">
                        ${notes.length === 0 ? '<p class="empty-notes">Заметок пока нет</p>' : notes.map(note => this.renderNote(note)).join('')}
                    </div>
                </div>
            </div>
        `;
    }
    
    renderNote(note) {
        const createdBy = note.created_by ? note.created_by.name : 'Неизвестно';
        
        return `
            <div class="note-item" data-note-id="${note.id}">
                <div class="note-header">
                    <div class="note-author">
                        <i class="fas fa-user-circle"></i>
                        ${this.escapeHtml(createdBy)}
                    </div>
                    <div class="note-actions">
                        <small class="note-time">${this.formatDate(note.created_at)}</small>
                        <button class="btn-icon btn-sm" onclick="orderDetailModule.editNote('${note.id}')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon btn-sm" onclick="orderDetailModule.deleteNote('${note.id}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="note-body">
                    <div class="note-text">${this.escapeHtml(note.note).replace(/\n/g, '<br>')}</div>
                    <div class="note-edit-form" style="display: none;">
                        <textarea class="form-control" rows="3">${this.escapeHtml(note.note)}</textarea>
                        <div class="note-form-actions">
                            <button class="btn btn-secondary btn-sm" onclick="orderDetailModule.cancelEditNote('${note.id}')">Отмена</button>
                            <button class="btn btn-primary btn-sm" onclick="orderDetailModule.saveNote('${note.id}')">
                                <i class="fas fa-save"></i>
                                Сохранить
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    closeDrawer() {
        const drawer = document.getElementById('orderDrawer');
        if (drawer) {
            drawer.classList.remove('show');
        }
        this.currentOrder = null;
    }
    
    async changeStatus() {
        if (!this.currentOrder) return;
        
        const newStatus = document.getElementById('newStatusSelect').value;
        if (newStatus === this.currentOrder.status) {
            AdminMain.prototype.showToast('Статус не изменился', 'info');
            return;
        }
        
        const comment = prompt('Комментарий (необязательно):');
        
        try {
            await window.adminApi.request(`orders.php?action=status&id=${this.currentOrder.id}`, 'PATCH', {
                status: newStatus,
                comment: comment || null
            });
            
            AdminMain.prototype.showToast('Статус обновлён', 'success');
            
            if (window.ordersModule) {
                window.ordersModule.loadOrders();
            }
            
            await this.openDrawer(this.currentOrder.id);
        } catch (error) {
            console.error('❌ Failed to change status:', error);
            AdminMain.prototype.showToast('Ошибка изменения статуса', 'error');
        }
    }
    
    async archiveOrder() {
        if (!this.currentOrder) return;
        
        if (!confirm('Вы уверены, что хотите архивировать этот заказ?')) {
            return;
        }
        
        try {
            await window.adminApi.request(`orders.php?action=archive&id=${this.currentOrder.id}`, 'PATCH', {});
            
            AdminMain.prototype.showToast('Заказ архивирован', 'success');
            
            if (window.ordersModule) {
                window.ordersModule.loadOrders();
            }
            
            await this.openDrawer(this.currentOrder.id);
        } catch (error) {
            console.error('❌ Failed to archive order:', error);
            AdminMain.prototype.showToast('Ошибка архивирования', 'error');
        }
    }
    
    async unarchiveOrder() {
        if (!this.currentOrder) return;
        
        try {
            await window.adminApi.request(`orders.php?action=unarchive&id=${this.currentOrder.id}`, 'PATCH', {});
            
            AdminMain.prototype.showToast('Заказ разархивирован', 'success');
            
            if (window.ordersModule) {
                window.ordersModule.loadOrders();
            }
            
            await this.openDrawer(this.currentOrder.id);
        } catch (error) {
            console.error('❌ Failed to unarchive order:', error);
            AdminMain.prototype.showToast('Ошибка разархивирования', 'error');
        }
    }
    
    showAddNoteForm() {
        const form = document.getElementById('addNoteForm');
        if (form) {
            form.style.display = 'block';
            document.getElementById('newNoteText').focus();
        }
    }
    
    hideAddNoteForm() {
        const form = document.getElementById('addNoteForm');
        if (form) {
            form.style.display = 'none';
            document.getElementById('newNoteText').value = '';
        }
    }
    
    async addNote() {
        if (!this.currentOrder) return;
        
        const noteText = document.getElementById('newNoteText').value.trim();
        if (!noteText) {
            AdminMain.prototype.showToast('Введите текст заметки', 'warning');
            return;
        }
        
        try {
            await window.adminApi.request(`orders.php?action=add_note&id=${this.currentOrder.id}`, 'PATCH', {
                note: noteText
            });
            
            AdminMain.prototype.showToast('Заметка добавлена', 'success');
            
            this.hideAddNoteForm();
            await this.openDrawer(this.currentOrder.id);
        } catch (error) {
            console.error('❌ Failed to add note:', error);
            AdminMain.prototype.showToast('Ошибка добавления заметки', 'error');
        }
    }
    
    editNote(noteId) {
        const noteItem = document.querySelector(`[data-note-id="${noteId}"]`);
        if (noteItem) {
            noteItem.querySelector('.note-text').style.display = 'none';
            noteItem.querySelector('.note-edit-form').style.display = 'block';
        }
    }
    
    cancelEditNote(noteId) {
        const noteItem = document.querySelector(`[data-note-id="${noteId}"]`);
        if (noteItem) {
            noteItem.querySelector('.note-text').style.display = 'block';
            noteItem.querySelector('.note-edit-form').style.display = 'none';
        }
    }
    
    async saveNote(noteId) {
        if (!this.currentOrder) return;
        
        const noteItem = document.querySelector(`[data-note-id="${noteId}"]`);
        if (!noteItem) return;
        
        const noteText = noteItem.querySelector('textarea').value.trim();
        if (!noteText) {
            AdminMain.prototype.showToast('Введите текст заметки', 'warning');
            return;
        }
        
        try {
            await window.adminApi.request(`orders.php?action=update_note&id=${this.currentOrder.id}`, 'PATCH', {
                note_id: noteId,
                note: noteText
            });
            
            AdminMain.prototype.showToast('Заметка обновлена', 'success');
            
            await this.openDrawer(this.currentOrder.id);
        } catch (error) {
            console.error('❌ Failed to update note:', error);
            AdminMain.prototype.showToast('Ошибка обновления заметки', 'error');
        }
    }
    
    async deleteNote(noteId) {
        if (!this.currentOrder) return;
        
        if (!confirm('Удалить эту заметку?')) {
            return;
        }
        
        try {
            await window.adminApi.request(`orders.php?action=delete_note&id=${this.currentOrder.id}`, 'PATCH', {
                note_id: noteId
            });
            
            AdminMain.prototype.showToast('Заметка удалена', 'success');
            
            await this.openDrawer(this.currentOrder.id);
        } catch (error) {
            console.error('❌ Failed to delete note:', error);
            AdminMain.prototype.showToast('Ошибка удаления заметки', 'error');
        }
    }
    
    attachEventListeners() {
    }
    
    renderStatusBadge(status) {
        const badges = {
            'new': '<span class="badge badge-new">Новый</span>',
            'processing': '<span class="badge badge-processing">В работе</span>',
            'completed': '<span class="badge badge-completed">Выполнен</span>',
            'cancelled': '<span class="badge badge-cancelled">Отменён</span>'
        };
        return badges[status] || `<span class="badge">${status}</span>`;
    }
    
    formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('ru-RU', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    formatMoney(amount) {
        if (!amount) return '0 ₽';
        return new Intl.NumberFormat('ru-RU', { 
            style: 'currency', 
            currency: 'RUB',
            minimumFractionDigits: 0
        }).format(amount);
    }
    
    formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
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
        window.orderDetailModule = new OrderDetailModule();
        window.orderDetailModule.init();
    });
} else {
    window.orderDetailModule = new OrderDetailModule();
    window.orderDetailModule.init();
}
