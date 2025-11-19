// ========================================
// Admin Main - Page Bootstrapper & Shared UI Components
// ========================================

class AdminMain {
    constructor() {
        this.sidebarCollapsed = false;
        this.notifications = [];
        this.toastTimeout = null;
    }
    
    init() {
        console.log('🔄 Initializing admin panel...');
        this.initSidebar();
        this.initHeader();
        this.initTheme();
        this.checkOrdersBadge();
        console.log('✅ Admin panel initialized');
    }
    
    // ========================================
    // Sidebar Management
    // ========================================
    
    initSidebar() {
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('adminSidebar');
        
        if (toggle && sidebar) {
            toggle.addEventListener('click', () => {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                sidebar.classList.toggle('collapsed', this.sidebarCollapsed);
                localStorage.setItem('adminSidebarCollapsed', this.sidebarCollapsed);
            });
            
            // Restore sidebar state
            const savedState = localStorage.getItem('adminSidebarCollapsed');
            if (savedState === 'true') {
                this.sidebarCollapsed = true;
                sidebar.classList.add('collapsed');
            }
        }
    }
    
    // ========================================
    // Header & Dropdowns
    // ========================================
    
    initHeader() {
        // User menu dropdown
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userMenuDropdown = document.getElementById('userMenuDropdown');
        
        if (userMenuBtn && userMenuDropdown) {
            userMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleDropdown(userMenuDropdown);
            });
        }
        
        // Quick settings dropdown
        const quickSettingsBtn = document.getElementById('quickSettingsBtn');
        const quickSettingsDropdown = document.getElementById('quickSettingsDropdown');
        
        if (quickSettingsBtn && quickSettingsDropdown) {
            quickSettingsBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleDropdown(quickSettingsDropdown);
            });
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-menu') && !e.target.closest('.btn-user') && !e.target.closest('.btn-icon')) {
                this.closeAllDropdowns();
            }
        });
        
        // Notifications button
        const notificationsBtn = document.getElementById('notificationsBtn');
        if (notificationsBtn) {
            notificationsBtn.addEventListener('click', () => {
                this.showNotificationsPanel();
            });
        }
    }
    
    toggleDropdown(dropdown) {
        const isVisible = dropdown.style.display === 'block';
        this.closeAllDropdowns();
        
        if (!isVisible) {
            dropdown.style.display = 'block';
        }
    }
    
    closeAllDropdowns() {
        document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
            dropdown.style.display = 'none';
        });
    }
    
    // ========================================
    // Theme Management
    // ========================================
    
    initTheme() {
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            const savedTheme = localStorage.getItem('adminTheme') || 'light';
            document.body.setAttribute('data-theme', savedTheme);
            themeToggle.checked = savedTheme === 'dark';
            
            themeToggle.addEventListener('change', (e) => {
                const theme = e.target.checked ? 'dark' : 'light';
                document.body.setAttribute('data-theme', theme);
                localStorage.setItem('adminTheme', theme);
                this.showToast('Тема изменена', 'success');
            });
        }
    }
    
    // ========================================
    // Notifications & Toasts
    // ========================================
    
    showToast(message, type = 'info', duration = 3000) {
        const container = document.getElementById('toastContainer') || this.createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icon = this.getToastIcon(type);
        toast.innerHTML = `
            <i class="fas ${icon}"></i>
            <span>${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        container.appendChild(toast);
        
        // Animate in
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Auto remove
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
    
    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
        return container;
    }
    
    getToastIcon(type) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || icons.info;
    }
    
    showNotificationsPanel() {
        this.showToast('Система уведомлений в разработке', 'info');
    }
    
    // ========================================
    // Orders Badge
    // ========================================
    
    async checkOrdersBadge() {
        try {
            if (!window.adminApi) {
                console.warn('⚠️ adminApi not ready yet');
                return;
            }
            
            const orders = await window.adminApi.getOrders();
            const newOrders = orders.filter(o => o.status === 'new').length;
            
            const badge = document.getElementById('ordersBadge');
            if (badge) {
                if (newOrders > 0) {
                    badge.textContent = newOrders;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('❌ Failed to check orders badge:', error);
        }
    }
    
    // ========================================
    // Utility Methods
    // ========================================
    
    static clearCache() {
        if (confirm('Очистить локальный кеш? Данные будут загружены заново с сервера.')) {
            localStorage.removeItem('db_cache');
            sessionStorage.clear();
            window.AdminMain.showToast('Кеш очищен', 'success');
            setTimeout(() => location.reload(), 1000);
        }
    }
    
    formatDate(dateString) {
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
        return new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: 'RUB',
            minimumFractionDigits: 0
        }).format(amount || 0);
    }
    
    getStatusBadge(status) {
        const statuses = {
            new: { label: 'Новый', class: 'badge-new' },
            processing: { label: 'В работе', class: 'badge-processing' },
            completed: { label: 'Выполнен', class: 'badge-completed' },
            cancelled: { label: 'Отменён', class: 'badge-cancelled' }
        };
        
        const statusInfo = statuses[status] || { label: status, class: 'badge-default' };
        return `<span class="badge ${statusInfo.class}">${statusInfo.label}</span>`;
    }
    
    showConfirm(message) {
        return confirm(message);
    }
    
    showLoading(container, message = 'Загрузка...') {
        if (typeof container === 'string') {
            container = document.getElementById(container);
        }
        
        if (container) {
            container.innerHTML = `
                <div class="loading-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>${message}</p>
                </div>
            `;
        }
    }
    
    showError(container, message = 'Ошибка загрузки данных') {
        if (typeof container === 'string') {
            container = document.getElementById(container);
        }
        
        if (container) {
            container.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>${message}</p>
                </div>
            `;
        }
    }
    
    showEmpty(container, message = 'Нет данных') {
        if (typeof container === 'string') {
            container = document.getElementById(container);
        }
        
        if (container) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>${message}</p>
                </div>
            `;
        }
    }
    
    // ========================================
    // Unified Modal System
    // ========================================
    
    createModal(options = {}) {
        const {
            title = 'Модальное окно',
            body = '',
            footer = null,
            size = 'medium',
            onClose = null
        } = options;
        
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.setAttribute('data-size', size);
        
        const footerHtml = footer || `
            <button class="btn btn-outline" onclick="this.closest('.modal').remove()">Отмена</button>
            <button class="btn btn-primary" id="modalSubmitBtn">Сохранить</button>
        `;
        
        modal.innerHTML = `
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2>${title}</h2>
                    <button class="btn-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">${body}</div>
                <div class="modal-footer">${footerHtml}</div>
            </div>
        `;
        
        const closeBtn = modal.querySelector('.btn-close');
        const overlay = modal.querySelector('.modal-overlay');
        
        const closeModal = () => {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.remove();
                if (onClose) onClose();
            }, 300);
        };
        
        closeBtn.addEventListener('click', closeModal);
        overlay.addEventListener('click', closeModal);
        
        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('show'), 10);
        
        return modal;
    }
    
    displayValidationErrors(errors, formId = null) {
        const errorContainer = document.querySelector('.validation-errors');
        if (errorContainer) {
            errorContainer.remove();
        }
        
        if (!errors || Object.keys(errors).length === 0) {
            return;
        }
        
        const errorsDiv = document.createElement('div');
        errorsDiv.className = 'validation-errors';
        errorsDiv.innerHTML = `
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Ошибки валидации:</strong>
                    <ul>
                        ${Object.entries(errors).map(([field, messages]) => 
                            messages.map(msg => `<li>${msg}</li>`).join('')
                        ).join('')}
                    </ul>
                </div>
            </div>
        `;
        
        const form = formId ? document.getElementById(formId) : document.querySelector('.modal-body form');
        if (form) {
            form.insertBefore(errorsDiv, form.firstChild);
        } else {
            const modalBody = document.querySelector('.modal-body');
            if (modalBody) {
                modalBody.insertBefore(errorsDiv, modalBody.firstChild);
            }
        }
    }
    
    // ========================================
    // File Upload Widget
    // ========================================
    
    createFileUpload(options = {}) {
        const {
            accept = 'image/*',
            maxSize = 5242880,
            preview = true,
            currentImage = null,
            onUpload = null
        } = options;
        
        const uploadId = 'upload_' + Date.now();
        
        const html = `
            <div class="file-upload-widget" id="${uploadId}">
                <input type="file" id="${uploadId}_input" accept="${accept}" style="display: none;">
                <div class="upload-area" id="${uploadId}_area">
                    ${currentImage ? `
                        <div class="upload-preview">
                            <img src="${currentImage}" alt="Preview">
                            <button type="button" class="btn-remove-image" title="Удалить изображение">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    ` : `
                        <div class="upload-placeholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Нажмите или перетащите файл</p>
                            <small>Максимум ${(maxSize / 1048576).toFixed(1)} МБ</small>
                        </div>
                    `}
                </div>
                <div class="upload-progress" id="${uploadId}_progress" style="display: none;">
                    <div class="progress-bar"></div>
                </div>
            </div>
        `;
        
        setTimeout(() => {
            const widget = document.getElementById(uploadId);
            const input = document.getElementById(uploadId + '_input');
            const area = document.getElementById(uploadId + '_area');
            
            if (!widget || !input || !area) return;
            
            area.addEventListener('click', (e) => {
                if (!e.target.closest('.btn-remove-image')) {
                    input.click();
                }
            });
            
            const removeBtn = area.querySelector('.btn-remove-image');
            if (removeBtn) {
                removeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    area.innerHTML = `
                        <div class="upload-placeholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Нажмите или перетащите файл</p>
                            <small>Максимум ${(maxSize / 1048576).toFixed(1)} МБ</small>
                        </div>
                    `;
                    input.value = '';
                });
            }
            
            input.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    this.handleFileUpload(file, uploadId, preview, maxSize, onUpload);
                }
            });
            
            area.addEventListener('dragover', (e) => {
                e.preventDefault();
                area.classList.add('drag-over');
            });
            
            area.addEventListener('dragleave', () => {
                area.classList.remove('drag-over');
            });
            
            area.addEventListener('drop', (e) => {
                e.preventDefault();
                area.classList.remove('drag-over');
                const file = e.dataTransfer.files[0];
                if (file) {
                    input.files = e.dataTransfer.files;
                    this.handleFileUpload(file, uploadId, preview, maxSize, onUpload);
                }
            });
        }, 0);
        
        return html;
    }
    
    async handleFileUpload(file, uploadId, preview, maxSize, onUpload) {
        if (file.size > maxSize) {
            this.showToast(`Файл слишком большой. Максимум ${(maxSize / 1048576).toFixed(1)} МБ`, 'error');
            return;
        }
        
        const area = document.getElementById(uploadId + '_area');
        
        if (preview && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                area.innerHTML = `
                    <div class="upload-preview">
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="btn-remove-image" title="Удалить изображение">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                
                const removeBtn = area.querySelector('.btn-remove-image');
                if (removeBtn) {
                    removeBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        area.innerHTML = `
                            <div class="upload-placeholder">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Нажмите или перетащите файл</p>
                                <small>Максимум ${(maxSize / 1048576).toFixed(1)} МБ</small>
                            </div>
                        `;
                        document.getElementById(uploadId + '_input').value = '';
                    });
                }
            };
            reader.readAsDataURL(file);
        }
        
        if (onUpload) {
            await onUpload(file);
        }
    }
    
    // ========================================
    // Markdown/HTML Preview
    // ========================================
    
    createMarkdownPreview(textareaId, previewId = null) {
        const textarea = document.getElementById(textareaId);
        if (!textarea) return;
        
        const previewContainerId = previewId || textareaId + '_preview';
        
        const previewHtml = `
            <div class="markdown-preview-container">
                <div class="preview-tabs">
                    <button type="button" class="preview-tab active" data-tab="edit">Редактировать</button>
                    <button type="button" class="preview-tab" data-tab="preview">Предпросмотр</button>
                </div>
                <div class="preview-content">
                    <div class="preview-pane active" data-pane="edit"></div>
                    <div class="preview-pane" data-pane="preview" id="${previewContainerId}">
                        <div class="preview-body"></div>
                    </div>
                </div>
            </div>
        `;
        
        const wrapper = document.createElement('div');
        wrapper.innerHTML = previewHtml;
        const container = wrapper.firstElementChild;
        
        textarea.parentNode.insertBefore(container, textarea);
        container.querySelector('[data-pane="edit"]').appendChild(textarea);
        
        const tabs = container.querySelectorAll('.preview-tab');
        const panes = container.querySelectorAll('.preview-pane');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.getAttribute('data-tab');
                
                tabs.forEach(t => t.classList.remove('active'));
                panes.forEach(p => p.classList.remove('active'));
                
                tab.classList.add('active');
                container.querySelector(`[data-pane="${target}"]`).classList.add('active');
                
                if (target === 'preview') {
                    this.updateMarkdownPreview(textarea.value, previewContainerId);
                }
            });
        });
    }
    
    updateMarkdownPreview(markdown, previewId) {
        const preview = document.getElementById(previewId);
        if (!preview) return;
        
        const body = preview.querySelector('.preview-body');
        if (!body) return;
        
        const html = this.markdownToHtml(markdown);
        body.innerHTML = html;
    }
    
    markdownToHtml(markdown) {
        if (!markdown) return '<p class="text-muted">Нет содержимого для предпросмотра</p>';
        
        let html = markdown
            .replace(/^### (.*$)/gim, '<h3>$1</h3>')
            .replace(/^## (.*$)/gim, '<h2>$1</h2>')
            .replace(/^# (.*$)/gim, '<h1>$1</h1>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>');
        
        return '<p>' + html + '</p>';
    }
    
    // ========================================
    // Drag & Drop Utilities
    // ========================================
    
    initDragAndDrop(containerId, itemSelector, onReorder) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        let draggedElement = null;
        let placeholder = null;
        
        const items = container.querySelectorAll(itemSelector);
        
        items.forEach((item, index) => {
            item.setAttribute('draggable', 'true');
            item.setAttribute('data-index', index);
            
            if (!item.querySelector('.drag-handle')) {
                const handle = document.createElement('div');
                handle.className = 'drag-handle';
                handle.innerHTML = '<i class="fas fa-grip-vertical"></i>';
                item.insertBefore(handle, item.firstChild);
            }
            
            item.addEventListener('dragstart', (e) => {
                draggedElement = item;
                item.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', item.innerHTML);
            });
            
            item.addEventListener('dragend', () => {
                item.classList.remove('dragging');
                if (placeholder && placeholder.parentNode) {
                    placeholder.remove();
                }
                draggedElement = null;
            });
            
            item.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                
                if (item === draggedElement) return;
                
                const rect = item.getBoundingClientRect();
                const midpoint = rect.top + rect.height / 2;
                
                if (e.clientY < midpoint) {
                    container.insertBefore(draggedElement, item);
                } else {
                    container.insertBefore(draggedElement, item.nextSibling);
                }
            });
        });
        
        container.addEventListener('drop', (e) => {
            e.preventDefault();
            
            const newOrder = Array.from(container.querySelectorAll(itemSelector)).map((item, index) => {
                item.setAttribute('data-index', index);
                return {
                    id: item.getAttribute('data-id'),
                    position: index
                };
            });
            
            if (onReorder) {
                onReorder(newOrder);
            }
        });
    }
    
    // ========================================
    // Optimistic State Management
    // ========================================
    
    async withOptimisticUpdate(operation, rollback, errorMessage = 'Операция не удалась') {
        try {
            const result = await operation();
            return result;
        } catch (error) {
            console.error('❌ Operation failed:', error);
            if (rollback) {
                rollback();
            }
            
            let message = errorMessage;
            if (error.errors) {
                this.displayValidationErrors(error.errors);
            } else if (error.message) {
                message = error.message;
            }
            
            this.showToast(message, 'error');
            throw error;
        }
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.AdminMain = new AdminMain();
        window.AdminMain.init();
    });
} else {
    window.AdminMain = new AdminMain();
    window.AdminMain.init();
}
