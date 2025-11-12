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
