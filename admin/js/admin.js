// ========================================
// ADMIN PANEL - COMPLETE VERSION
// ========================================

class AdminPanel {
    constructor() {
        this.currentPage = 'dashboard';
        this.currentUser = null;
        this.notifications = [];
        this.activeDropdown = null;
    }

    init() {
        this.checkAuth();
        this.initEventListeners();
    }

    // ========================================
    // AUTHENTICATION
    // ========================================

    checkAuth() {
        const authData = localStorage.getItem('adminAuth');

        if (authData) {
            try {
                this.currentUser = JSON.parse(authData);
                this.showDashboard();
                this.initAdminPanel();
            } catch (error) {
                this.logout();
            }
        } else {
            this.showLoginScreen();
        }
    }

    showLoginScreen() {
        document.getElementById('loginScreen').style.display = 'flex';
        document.getElementById('adminDashboard').style.display = 'none';
        this.initLoginForm();
    }

    showDashboard() {
        document.getElementById('loginScreen').style.display = 'none';
        document.getElementById('adminDashboard').style.display = 'flex';
    }

    initLoginForm() {
        const form = document.getElementById('loginForm');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const login = document.getElementById('adminLogin').value;
            const password = document.getElementById('adminPassword').value;

            if (login === 'admin' && password === 'admin123') {
                const userData = {
                    id: 1,
                    login: login,
                    name: 'Администратор',
                    role: 'admin',
                    loginTime: new Date().toISOString()
                };

                localStorage.setItem('adminAuth', JSON.stringify(userData));
                this.currentUser = userData;

                this.showNotification('Вход выполнен успешно!', 'success');

                setTimeout(() => {
                    this.showDashboard();
                    this.initAdminPanel();
                }, 500);
            } else {
                this.showNotification('Неверный логин или пароль', 'error');
                document.getElementById('adminPassword').classList.add('error-shake');
                setTimeout(() => {
                    document.getElementById('adminPassword').classList.remove('error-shake');
                }, 500);
            }
        });
    }

    logout() {
        if (confirm('Вы уверены, что хотите выйти?')) {
            localStorage.removeItem('adminAuth');
            this.currentUser = null;
            location.reload();
        }
    }

    // ========================================
    // INITIALIZATION
    // ========================================

    initAdminPanel() {
        this.initNavigation();
        this.initSidebar();
        this.initTabs();
        this.initDropdowns();
        this.loadDashboard();
        this.loadNotifications();
        this.updateOrdersBadge();
    }

    initEventListeners() {
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.header-btn') && !e.target.closest('.user-menu') && !e.target.closest('.dropdown-menu')) {
                this.closeAllDropdowns();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllDropdowns();
                this.closeAllModals();
            }
        });
    }

    initNavigation() {
        const navItems = document.querySelectorAll('.nav-item');

        navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();

                const page = item.getAttribute('data-page');
                this.navigateToPage(page);

                navItems.forEach(nav => nav.classList.remove('active'));
                item.classList.add('active');

                const title = item.querySelector('span').textContent;
                document.getElementById('pageTitle').textContent = title;
            });
        });
    }

    navigateToPage(pageId) {
        this.currentPage = pageId;

        document.querySelectorAll('.page').forEach(page => {
            page.classList.remove('active');
        });

        const targetPage = document.getElementById(`page-${pageId}`);
        if (targetPage) {
            targetPage.classList.add('active');
            this.loadPageData(pageId);
        }
    }

    loadPageData(pageId) {
        switch (pageId) {
            case 'dashboard':
                this.loadDashboard();
                break;
            case 'orders':
                this.loadOrders();
                break;
            case 'portfolio':
                this.loadPortfolio();
                break;
            case 'services':
                this.loadServices();
                break;
            case 'testimonials':
                this.loadTestimonials();
                break;
            case 'calculator':
                this.loadCalculatorSettings();
                break;
            case 'content':
                this.loadContent();
                break;
            case 'forms':
                this.loadFormSettings();
                break;
            case 'settings':
                this.loadSettings();
                break;
        }
    }

    initSidebar() {
        const toggle = document.getElementById('toggleSidebar');
        const sidebar = document.querySelector('.admin-sidebar');

        if (toggle && sidebar) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }
    }

    initTabs() {
        const tabBtns = document.querySelectorAll('.tab-btn');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.getAttribute('data-tab');

                tabBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const tabs = document.querySelectorAll('.settings-tab');
                tabs.forEach(tab => tab.classList.remove('active'));

                const targetTab = document.getElementById(`tab-${tabId}`);
                if (targetTab) {
                    targetTab.classList.add('active');
                }
            });
        });
    }

    initDropdowns() {
        const notifBtn = document.getElementById('notificationsBtn');
        const notifDropdown = document.getElementById('notificationsDropdown');

        notifBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown(notifDropdown, notifBtn);
        });

        const settingsBtn = document.getElementById('quickSettingsBtn');
        const settingsDropdown = document.getElementById('quickSettingsDropdown');

        settingsBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown(settingsDropdown, settingsBtn);
        });

        const userMenuBtn = document.getElementById('userMenuBtn');
        const userMenuDropdown = document.getElementById('userMenuDropdown');

        userMenuBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown(userMenuDropdown, userMenuBtn);
            userMenuBtn.classList.toggle('active');
        });
    }

    toggleDropdown(dropdown, button) {
        if (!dropdown || !button) return;

        const isVisible = dropdown.style.display === 'block';

        this.closeAllDropdowns();

        if (!isVisible) {
            const rect = button.getBoundingClientRect();
            dropdown.style.display = 'block';
            dropdown.style.top = (rect.bottom + 10) + 'px';
            dropdown.style.right = (window.innerWidth - rect.right) + 'px';
            this.activeDropdown = dropdown;
        }
    }

    closeAllDropdowns() {
        document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
            dropdown.style.display = 'none';
        });
        document.getElementById('userMenuBtn')?.classList.remove('active');
        this.activeDropdown = null;
    }

    // ========================================
    // DASHBOARD (с динамическими процентами #8)
    // ========================================

    loadDashboard() {
        this.loadDashboardStats();
        this.loadRecentOrders();
        this.loadActivityFeed();
        this.loadPopularServices();
        this.loadChart();
    }

    loadDashboardStats() {
        const orders = db.getData('orders') || [];
        const now = new Date();
        const currentMonth = now.getMonth();
        const currentYear = now.getFullYear();

        const prevMonth = currentMonth === 0 ? 11 : currentMonth - 1;
        const prevMonthYear = currentMonth === 0 ? currentYear - 1 : currentYear;

        const totalOrders = orders.length;
        document.getElementById('dashTotalOrders').textContent = totalOrders;

        const prevMonthOrders = orders.filter(o => {
            const orderDate = new Date(o.createdAt);
            return orderDate.getMonth() === prevMonth && orderDate.getFullYear() === prevMonthYear;
        }).length;

        const ordersGrowth = this.calculateGrowth(totalOrders, prevMonthOrders);

        const monthRevenue = orders
            .filter(o => {
                const orderDate = new Date(o.createdAt);
                return orderDate.getMonth() === currentMonth && orderDate.getFullYear() === currentYear;
            })
            .reduce((sum, o) => sum + (o.amount || 0), 0);

        document.getElementById('dashMonthRevenue').textContent = '₽' + monthRevenue.toLocaleString('ru-RU');

        const prevMonthRevenue = orders
            .filter(o => {
                const orderDate = new Date(o.createdAt);
                return orderDate.getMonth() === prevMonth && orderDate.getFullYear() === prevMonthYear;
            })
            .reduce((sum, o) => sum + (o.amount || 0), 0);

        const revenueGrowth = this.calculateGrowth(monthRevenue, prevMonthRevenue);

        const uniqueClients = new Set(orders.map(o => o.clientEmail || o.email)).size;
        document.getElementById('dashTotalClients').textContent = uniqueClients;

        const prevMonthClients = new Set(
            orders
                .filter(o => {
                    const orderDate = new Date(o.createdAt);
                    return orderDate.getMonth() === prevMonth && orderDate.getFullYear() === prevMonthYear;
                })
                .map(o => o.clientEmail || o.email)
        ).size;

        const clientsGrowth = this.calculateGrowth(uniqueClients, prevMonthClients);

        const processing = orders.filter(o => o.status === 'new' || o.status === 'processing').length;
        document.getElementById('dashProcessing').textContent = processing;

        const statBoxes = document.querySelectorAll('.stat-box');
        if (statBoxes[0]) {
            this.updateStatChange(statBoxes[0], ordersGrowth);
        }
        if (statBoxes[1]) {
            this.updateStatChange(statBoxes[1], revenueGrowth);
        }
        if (statBoxes[2]) {
            this.updateStatChange(statBoxes[2], clientsGrowth);
        }
        if (statBoxes[3]) {
            const processingPercent = totalOrders > 0 ? ((processing / totalOrders) * 100).toFixed(1) : 0;
            const changeSpan = statBoxes[3].querySelector('.stat-change');
            if (changeSpan) {
                changeSpan.innerHTML = `<i class="fas fa-equals"></i> ${processingPercent}% от общего`;
                changeSpan.className = 'stat-change';
                if (processing > 5) {
                    changeSpan.classList.add('negative');
                } else {
                    changeSpan.classList.add('positive');
                }
            }
        }
    }

    calculateGrowth(current, previous) {
        if (previous === 0) return current > 0 ? 100 : 0;
        return (((current - previous) / previous) * 100).toFixed(1);
    }

    updateStatChange(statBox, growth) {
        const changeSpan = statBox.querySelector('.stat-change');
        if (!changeSpan) return;

        const isPositive = growth >= 0;
        const icon = isPositive ? 'fa-arrow-up' : 'fa-arrow-down';

        changeSpan.innerHTML = `<i class="fas ${icon}"></i> ${Math.abs(growth)}%`;
        changeSpan.className = 'stat-change';
        changeSpan.classList.add(isPositive ? 'positive' : 'negative');
    }

    loadRecentOrders() {
        const orders = (db.getData('orders') || [])
            .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
            .slice(0, 5);

        const container = document.getElementById('recentOrders');
        if (!container) return;

        if (orders.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: var(--admin-text-secondary); padding: 20px;">Заказов пока нет</p>';
            return;
        }

        container.innerHTML = orders.map(order => `
            <div class="order-item" style="display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid var(--admin-border); cursor: pointer; transition: background 0.2s;" onclick="admin.viewOrder('${order.id}')">
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                        <strong>#${order.orderNumber || order.id.substr(0, 8)}</strong>
                        <span class="type-badge type-${order.type || 'order'}">
                            ${order.type === 'contact' ? 'Обращение' : 'Заказ'}
                        </span>
                    </div>
                    <div style="color: var(--admin-text-secondary); font-size: 14px;">
                        ${order.clientName || order.name} • ${order.service || order.subject || '-'}
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 600; margin-bottom: 5px;">₽${(order.amount || 0).toLocaleString('ru-RU')}</div>
                    <span class="status-badge status-${order.status}">
                        ${this.getStatusName(order.status)}
                    </span>
                </div>
            </div>
        `).join('');
    }

    loadActivityFeed() {
        const orders = (db.getData('orders') || []).slice(-4).reverse();
        const container = document.getElementById('activityFeed');
        if (!container) return;

        if (orders.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: var(--admin-text-secondary); padding: 20px;">Нет активности</p>';
            return;
        }

        container.innerHTML = orders.map(order => {
            const iconClass = order.status === 'completed' ? 'success' :
                order.status === 'new' ? 'info' : 'warning';
            const icon = order.status === 'completed' ? 'fa-check' :
                order.status === 'new' ? 'fa-shopping-cart' : 'fa-clock';

            return `
                <div class="activity-item">
                    <div class="activity-icon ${iconClass}">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="activity-content">
                        <p>${order.type === 'contact' ? 'Новое обращение' : 'Новый заказ'} от <strong>${order.clientName || order.name}</strong></p>
                        <span>${this.getRelativeTime(order.createdAt)}</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    loadPopularServices() {
        const orders = db.getData('orders') || [];
        const services = {};

        orders.forEach(order => {
            const service = order.service || 'Другое';
            services[service] = (services[service] || 0) + 1;
        });

        const total = orders.length || 1;
        const sorted = Object.entries(services)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 4);

        const container = document.getElementById('popularServices');
        if (!container) return;

        if (sorted.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: var(--admin-text-secondary); padding: 20px;">Нет данных</p>';
            return;
        }

        container.innerHTML = sorted.map(([service, count]) => {
            const percent = Math.round((count / total) * 100);
            return `
                <div class="service-item">
                    <div class="service-bar">
                        <div class="service-progress" style="width: ${percent}%"></div>
                    </div>
                    <div class="service-stats">
                        <span>${service}</span>
                        <strong>${count} (${percent}%)</strong>
                    </div>
                </div>
            `;
        }).join('');
    }

    loadChart() {
        const ctx = document.getElementById('ordersChart');
        if (!ctx) return;

        const orders = db.getData('orders') || [];
        const last7Days = this.getLast7Days();

        const data = last7Days.map(date => {
            return orders.filter(o => {
                const orderDate = new Date(o.createdAt).toDateString();
                return orderDate === date.toDateString();
            }).length;
        });

        if (window.ordersChartInstance) {
            window.ordersChartInstance.destroy();
        }

        window.ordersChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: last7Days.map(d => d.toLocaleDateString('ru-RU', { weekday: 'short' })),
                datasets: [{
                    label: 'Заказы',
                    data: data,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    getLast7Days() {
        const days = [];
        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            days.push(date);
        }
        return days;
    }
    // ========================================
    // ORDERS MANAGEMENT
    // ========================================

    loadOrders() {
        this.renderOrdersTable();
        this.initOrdersFilters();
        this.updateOrdersBadge();
    }

    renderOrdersTable(orders = null) {
        if (!orders) {
            orders = db.getData('orders') || [];
        }

        const statusFilter = document.getElementById('orderStatusFilter')?.value;
        const typeFilter = document.getElementById('orderTypeFilter')?.value;
        const searchFilter = document.getElementById('orderSearchFilter')?.value;

        if (statusFilter && statusFilter !== 'all') {
            orders = orders.filter(o => o.status === statusFilter);
        }

        if (typeFilter && typeFilter !== 'all') {
            orders = orders.filter(o => (o.type || 'order') === typeFilter);
        }

        if (searchFilter) {
            const search = searchFilter.toLowerCase();
            orders = orders.filter(o =>
                (o.clientName || o.name || '').toLowerCase().includes(search) ||
                (o.clientEmail || o.email || '').toLowerCase().includes(search) ||
                (o.orderNumber || '').toLowerCase().includes(search)
            );
        }

        orders.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));

        const tbody = document.getElementById('ordersTable');
        if (!tbody) return;

        if (orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 40px; color: var(--admin-text-secondary);">Заказов не найдено</td></tr>';
            return;
        }

        tbody.innerHTML = orders.map((order, index) => `
        <tr>
            <td><input type="checkbox" class="order-checkbox" data-id="${order.id}"></td>
            <td><strong>#${order.orderNumber || (index + 1)}</strong></td>
            <td>
                <span class="type-badge type-${order.type || 'order'}">
                    ${order.type === 'contact' ? 'Обращение' : 'Заказ'}
                </span>
            </td>
            <td>${order.clientName || order.name || '-'}</td>
            <td style="font-size: 13px;">
                ${order.clientEmail || order.email || '-'}<br>
                ${order.clientPhone || order.phone || '-'}
                ${order.telegram ? `<br><i class="fab fa-telegram" style="color: var(--admin-info);"></i> ${order.telegram}` : ''}
            </td>
            <td>${order.service || order.subject || '-'}</td>
            <td>${this.formatDate(order.createdAt)}</td>
            <td><strong>₽${(order.amount || 0).toLocaleString('ru-RU')}</strong></td>
            <td>
                <select class="status-select status-${order.status}" onchange="admin.quickChangeStatus('${order.id}', this.value)" onclick="event.stopPropagation()">
                    <option value="new" ${order.status === 'new' ? 'selected' : ''}>Новый</option>
                    <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>В работе</option>
                    <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>Выполнен</option>
                    <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Отменён</option>
                </select>
            </td>
            <td>
                <div class="action-btns">
                    <button class="action-btn view" onclick="admin.viewOrder('${order.id}')" title="Просмотр">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn edit" onclick="admin.editOrder('${order.id}')" title="Редактировать">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn delete" onclick="admin.deleteOrder('${order.id}')" title="Удалить">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    }
    quickChangeStatus(id, newStatus) {
        const order = db.getItem('orders', id);
        if (!order) return;

        const oldStatus = order.status;

        db.updateItem('orders', id, { status: newStatus });

        this.showNotification(`✅ Статус изменён: ${this.getStatusName(oldStatus)} → ${this.getStatusName(newStatus)}`, 'success');

        this.updateOrdersBadge();
        this.loadDashboard();
    }
    editOrder(id) {
        const order = db.getItem('orders', id);
        if (!order) return;

        const isCalculatorOrder = order.type === 'order' && order.calculatorData;

        let formHTML = `
        <form id="editOrderForm" style="max-width: 600px;">
            <div class="form-group">
                <label>Номер заказа</label>
                <input type="text" class="form-control" value="${order.orderNumber || ''}" readonly style="background: var(--admin-bg);">
            </div>
            
            <div class="form-group">
                <label>Тип заявки</label>
                <select class="form-control" id="editOrderType">
                    <option value="order" ${order.type === 'order' ? 'selected' : ''}>Заказ</option>
                    <option value="contact" ${order.type === 'contact' ? 'selected' : ''}>Обращение</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Имя клиента <span style="color: var(--admin-danger);">*</span></label>
                <input type="text" class="form-control" id="editClientName" value="${order.clientName || order.name || ''}" required>
            </div>
            
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Email <span style="color: var(--admin-danger);">*</span></label>
                    <input type="email" class="form-control" id="editClientEmail" value="${order.clientEmail || order.email || ''}" required>
                </div>
                
                <div class="form-group">
                    <label>Телефон <span style="color: var(--admin-danger);">*</span></label>
                    <input type="tel" class="form-control" id="editClientPhone" value="${order.clientPhone || order.phone || ''}" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Telegram</label>
                <input type="text" class="form-control" id="editTelegram" value="${order.telegram || ''}" placeholder="@username">
            </div>
            
            <div class="form-group">
                <label>Услуга/Тема</label>
                <input type="text" class="form-control" id="editService" value="${order.service || order.subject || ''}">
            </div>
            
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Сумма (₽)</label>
                    <input type="number" class="form-control" id="editAmount" value="${order.amount || 0}" min="0">
                </div>
                
                <div class="form-group">
                    <label>Статус <span style="color: var(--admin-danger);">*</span></label>
                    <select class="form-control" id="editStatus" required>
                        <option value="new" ${order.status === 'new' ? 'selected' : ''}>Новый</option>
                        <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>В работе</option>
                        <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>Выполнен</option>
                        <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Отменён</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Детали / Сообщение</label>
                <textarea class="form-control" id="editDetails" rows="4">${order.details || order.message || ''}</textarea>
            </div>
            
            ${isCalculatorOrder ? `
            <div class="alert alert-info" style="margin: 20px 0; padding: 15px; background: rgba(59,130,246,0.1); border-radius: 10px; border-left: 4px solid var(--admin-info);">
                <strong><i class="fas fa-info-circle"></i> Данные расчёта калькулятора:</strong><br>
                <div style="margin-top: 10px; font-size: 14px; color: var(--admin-text-secondary);">
                    <strong>Технология:</strong> ${order.calculatorData.technology || '-'}<br>
                    <strong>Материал:</strong> ${order.calculatorData.material || '-'}<br>
                    <strong>Вес:</strong> ${order.calculatorData.weight || 0}г<br>
                    <strong>Количество:</strong> ${order.calculatorData.quantity || 0} шт<br>
                    <strong>Заполнение:</strong> ${order.calculatorData.infill || 0}%<br>
                    <strong>Качество:</strong> ${order.calculatorData.quality || '-'}
                </div>
            </div>
            ` : ''}
            
            <div class="form-group" style="display: flex; align-items: center; gap: 10px; padding: 15px; background: var(--admin-bg); border-radius: 10px;">
                <i class="fas fa-clock" style="color: var(--admin-text-secondary);"></i>
                <div style="flex: 1; font-size: 13px; color: var(--admin-text-secondary);">
                    <strong>Создан:</strong> ${this.formatDate(order.createdAt)}<br>
                    ${order.updatedAt ? `<strong>Обновлён:</strong> ${this.formatDate(order.updatedAt)}` : ''}
                </div>
                <div style="text-align: right;">
                    ${order.telegramSent ?
                '<span class="telegram-status telegram-sent"><i class="fab fa-telegram"></i> Отправлено</span>' :
                '<span class="telegram-status telegram-failed"><i class="fab fa-telegram"></i> Не отправлено</span>'}
                </div>
            </div>
            
            <div class="modal-footer" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--admin-border); display: flex; justify-content: space-between; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="admin.closeAllModals()">
                    <i class="fas fa-times"></i>
                    Отмена
                </button>
                <div style="display: flex; gap: 10px;">
                    ${!order.telegramSent ? `
                    <button type="button" class="btn btn-outline" onclick="admin.resendToTelegram('${order.id}')">
                        <i class="fab fa-telegram"></i>
                        Отправить в Telegram
                    </button>
                    ` : ''}
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Сохранить изменения
                    </button>
                </div>
            </div>
        </form>
    `;

        const modal = this.createModal(`Редактирование заявки #${order.orderNumber || order.id.substr(0, 8)}`, formHTML);

        const form = document.getElementById('editOrderForm');
        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const updatedData = {
                type: document.getElementById('editOrderType').value,
                clientName: document.getElementById('editClientName').value,
                name: document.getElementById('editClientName').value,
                clientEmail: document.getElementById('editClientEmail').value,
                email: document.getElementById('editClientEmail').value,
                clientPhone: document.getElementById('editClientPhone').value,
                phone: document.getElementById('editClientPhone').value,
                telegram: document.getElementById('editTelegram').value,
                service: document.getElementById('editService').value,
                subject: document.getElementById('editService').value,
                amount: parseFloat(document.getElementById('editAmount').value) || 0,
                status: document.getElementById('editStatus').value,
                details: document.getElementById('editDetails').value,
                message: document.getElementById('editDetails').value
            };

            db.updateItem('orders', id, updatedData);

            this.showNotification('✅ Заявка успешно обновлена', 'success');
            this.closeAllModals();
            this.renderOrdersTable();
            this.updateOrdersBadge();
            this.loadDashboard();
        });
    }

    initOrdersFilters() {
        ['orderStatusFilter', 'orderTypeFilter', 'orderSearchFilter'].forEach(id => {
            const filter = document.getElementById(id);
            if (filter) {
                const event = id.includes('Search') ? 'input' : 'change';
                filter.addEventListener(event, () => {
                    this.renderOrdersTable();
                });
            }
        });
    }

    updateOrdersBadge() {
        const orders = db.getData('orders') || [];
        const newOrders = orders.filter(o => o.status === 'new').length;

        const badge = document.getElementById('ordersBadge');
        if (badge) {
            badge.textContent = newOrders;
            badge.style.display = newOrders > 0 ? 'block' : 'none';
        }

        const notifDot = document.getElementById('notificationDot');
        if (notifDot) {
            notifDot.style.display = newOrders > 0 ? 'block' : 'none';
        }
    }

    viewOrder(id) {
        const order = db.getItem('orders', id);
        if (!order) return;

        let content = `
        <div class="detail-section">
            <h4>Информация о клиенте</h4>
            <p><strong>Имя:</strong> ${order.clientName || order.name}</p>
            <p><strong>Email:</strong> ${order.clientEmail || order.email}</p>
            <p><strong>Телефон:</strong> ${order.clientPhone || order.phone}</p>
            ${order.telegram ? `<p><strong>Telegram:</strong> ${order.telegram}</p>` : ''}
        </div>
    `;


        if (order.type !== 'contact' && order.calculatorData) {
            const calc = order.calculatorData;
            content += `
                <div class="detail-section">
                    <h4>Детали заказа</h4>
                    <p><strong>Услуга:</strong> ${order.service}</p>
                    <p><strong>Сумма:</strong> ₽${order.amount.toLocaleString('ru-RU')}</p>
                    <p><strong>Технология:</strong> ${(calc.technology || '').toUpperCase()}</p>
                    <p><strong>Материал:</strong> ${calc.material || '-'}</p>
                    <p><strong>Вес:</strong> ${calc.weight || '-'}г</p>
                    <p><strong>Количество:</strong> ${calc.quantity || '-'} шт</p>
                    <p><strong>Заполнение:</strong> ${calc.infill || '-'}%</p>
                    <p><strong>Качество:</strong> ${calc.quality || '-'}</p>
                    <p><strong>Срок:</strong> ${calc.timeEstimate || '-'}</p>
                </div>
            `;
        }

        if (order.subject) {
            content += `
                <div class="detail-section">
                    <h4>Тема обращения</h4>
                    <p>${order.subject}</p>
                </div>
            `;
        }

        if (order.message || order.details) {
            content += `
                <div class="detail-section">
                    <h4>Сообщение</h4>
                    <p>${order.message || order.details}</p>
                </div>
            `;
        }

        content += `
            <div class="detail-section">
                <h4>Статус</h4>
                <p><strong>Текущий статус:</strong> <span class="status-badge status-${order.status}">${this.getStatusName(order.status)}</span></p>
                <p><strong>Дата создания:</strong> ${this.formatDate(order.createdAt)}</p>
                ${order.telegramSent ?
                '<p><span class="telegram-status telegram-sent"><i class="fab fa-telegram"></i> Отправлено в Telegram</span></p>' :
                '<p><span class="telegram-status telegram-failed"><i class="fab fa-telegram"></i> Не отправлено</span></p>'}
            </div>
        `;

        if (order.status === 'new') {
            content += `
    <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
        ${order.status === 'new' ? `
        <button class="btn btn-primary" onclick="admin.markOrderProcessed('${order.id}')">
            <i class="fas fa-check"></i>
            Отметить как обработанный
        </button>
        ` : ''}
        ${order.status !== 'completed' ? `
        <button class="btn btn-outline" onclick="admin.markOrderCompleted('${order.id}')">
            <i class="fas fa-check-double"></i>
            Выполнен
        </button>
        ` : ''}
        <button class="btn btn-outline" onclick="admin.editOrder('${order.id}')">
            <i class="fas fa-edit"></i>
            Редактировать
        </button>
        ${!order.telegramSent ? `
        <button class="btn btn-outline" onclick="admin.resendToTelegram('${order.id}')">
            <i class="fab fa-telegram"></i>
            Отправить в Telegram
        </button>
        ` : ''}
    </div>
`;
        }

        this.createModal(`Заказ #${order.orderNumber || order.id.substr(0, 8)}`, content);
    }

    markOrderCompleted(id) {
        if (!confirm('Отметить заявку как выполненную?')) return;

        db.updateItem('orders', id, { status: 'completed' });
        this.showNotification('✅ Заявка отмечена как выполненная', 'success');
        this.closeAllModals();
        this.renderOrdersTable();
        this.updateOrdersBadge();
        this.loadDashboard();
    }

    markOrderProcessed(id) {
        db.updateItem('orders', id, { status: 'processing' });
        this.showNotification('Заказ переведён в обработку', 'success');
        this.closeAllModals();
        this.renderOrdersTable();
        this.updateOrdersBadge();
        this.loadDashboard();
    }

    deleteOrder(id) {
        if (!confirm('Удалить этот заказ?')) return;

        db.deleteItem('orders', id);
        this.showNotification('Заказ удалён', 'success');
        this.renderOrdersTable();
        this.updateOrdersBadge();
        this.loadDashboard();
    }
    bulkChangeStatus() {
        const checkboxes = document.querySelectorAll('.order-checkbox:checked');
        const ids = Array.from(checkboxes).map(cb => cb.getAttribute('data-id'));

        if (ids.length === 0) {
            this.showNotification('Выберите заявки для изменения статуса', 'warning');
            return;
        }

        const newStatus = prompt('Введите новый статус:\nnew - Новый\nprocessing - В работе\ncompleted - Выполнен\ncancelled - Отменён');

        if (!['new', 'processing', 'completed', 'cancelled'].includes(newStatus)) {
            this.showNotification('Неверный статус', 'error');
            return;
        }

        ids.forEach(id => {
            db.updateItem('orders', id, { status: newStatus });
        });

        this.showNotification(`✅ Статус изменён для ${ids.length} заявок`, 'success');
        this.renderOrdersTable();
        this.updateOrdersBadge();
        this.loadDashboard();
    }
    sortOrders(column, direction = 'desc') {
        let orders = db.getData('orders') || [];

        orders.sort((a, b) => {
            let valA, valB;

            switch (column) {
                case 'date':
                    valA = new Date(a.createdAt);
                    valB = new Date(b.createdAt);
                    break;
                case 'amount':
                    valA = a.amount || 0;
                    valB = b.amount || 0;
                    break;
                case 'status':
                    valA = a.status || '';
                    valB = b.status || '';
                    break;
                default:
                    return 0;
            }

            if (direction === 'asc') {
                return valA > valB ? 1 : -1;
            } else {
                return valA < valB ? 1 : -1;
            }
        });

        this.renderOrdersTable(orders);
    }
    async resendToTelegram(id) {
        const order = db.getItem('orders', id);
        if (!order) return;

        this.showNotification('Отправка в Telegram...', 'info');

        const result = order.type === 'contact'
            ? await telegramBot.sendContactNotification(order)
            : await telegramBot.sendOrderNotification(order);

        if (result.success) {
            db.updateItem('orders', id, { telegramSent: true });
            this.showNotification('Успешно отправлено в Telegram', 'success');
            this.closeAllModals();
            this.viewOrder(id);
        } else {
            this.showNotification('Ошибка отправки: ' + result.error, 'error');
        }
    }

    exportOrders() {
        const orders = db.getData('orders') || [];
        const blob = new Blob([JSON.stringify(orders, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `orders_${new Date().toISOString().split('T')[0]}.json`;
        link.click();
        URL.revokeObjectURL(url);

        this.showNotification('Заказы экспортированы', 'success');
    }

    bulkDeleteOrders() {
        const checkboxes = document.querySelectorAll('.order-checkbox:checked');
        const ids = Array.from(checkboxes).map(cb => cb.getAttribute('data-id'));

        if (ids.length === 0) {
            this.showNotification('Выберите заказы для удаления', 'warning');
            return;
        }

        if (!confirm(`Удалить ${ids.length} заказов?`)) return;

        ids.forEach(id => db.deleteItem('orders', id));

        this.showNotification(`Удалено заказов: ${ids.length}`, 'success');
        this.renderOrdersTable();
        this.updateOrdersBadge();
        this.loadDashboard();
    }

    addOrder() {
        const fields = [
            new FormField({
                name: 'clientName',
                label: 'Имя клиента',
                type: 'text',
                required: true,
                placeholder: 'Иван Петров'
            }),
            new FormField({
                name: 'clientPhone',
                label: 'Телефон',
                type: 'tel',
                required: true,
                placeholder: '+7 999 123-45-67'
            }),
            new FormField({
                name: 'clientEmail',
                label: 'Email',
                type: 'email',
                required: true,
                placeholder: 'example@mail.com'
            }),
            new FormField({
                name: 'service',
                label: 'Услуга',
                type: 'text',
                required: true,
                placeholder: 'FDM печать'
            }),
            new FormField({
                name: 'amount',
                label: 'Сумма (₽)',
                type: 'number',
                required: true,
                placeholder: '5000',
                value: '0'
            }),
            new FormField({
                name: 'details',
                label: 'Детали заказа',
                type: 'textarea',
                placeholder: 'Дополнительная информация'
            }),
            new FormField({
                name: 'status',
                label: 'Статус',
                type: 'select',
                required: true,
                value: 'new',
                options: [
                    { value: 'new', label: 'Новый' },
                    { value: 'processing', label: 'В работе' },
                    { value: 'completed', label: 'Выполнен' },
                    { value: 'cancelled', label: 'Отменён' }
                ]
            })
        ];

        this.showFormModal('Добавить заказ вручную', fields, (formData) => {
            const order = {
                type: 'order',
                clientName: formData.clientName,
                clientPhone: formData.clientPhone,
                clientEmail: formData.clientEmail,
                service: formData.service,
                amount: parseFloat(formData.amount) || 0,
                details: formData.details || '',
                status: formData.status || 'new',
                orderNumber: this.generateOrderNumber(),
                telegramSent: false
            };

            const savedOrder = db.addItem('orders', order);

            if (CONFIG.features.telegramNotifications && CONFIG.telegram.chatId && order.status === 'new') {
                telegramBot.sendOrderNotification(savedOrder).then(result => {
                    if (result.success) {
                        db.updateItem('orders', savedOrder.id, { telegramSent: true });
                    }
                });
            }

            this.showNotification('Заказ успешно добавлен', 'success');
            this.renderOrdersTable();
            this.updateOrdersBadge();
            this.loadDashboard();
        });
    }

    generateOrderNumber() {
        const orders = db.getData('orders') || [];
        const maxNumber = orders.reduce((max, o) => {
            const num = parseInt(o.orderNumber) || 0;
            return num > max ? num : max;
        }, 1000);
        return (maxNumber + 1).toString();
    }

    // ========================================
    // PORTFOLIO MANAGEMENT
    // ========================================

    loadPortfolio() {
        const items = db.getData('portfolio') || [];
        this.renderPortfolio(items);
    }

    renderPortfolio(items = null) {
        if (!items) {
            items = db.getData('portfolio') || [];
        }

        const grid = document.getElementById('portfolioAdminGrid');
        if (!grid) return;

        if (items.length === 0) {
            grid.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--admin-text-secondary); grid-column: 1/-1;">Портфолио пусто. Добавьте первую работу!</p>';
            return;
        }

        grid.innerHTML = items.map(item => `
            <div class="portfolio-admin-item hover-lift" style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <img src="${item.image}" alt="${item.title}" style="width: 100%; height: 200px; object-fit: cover;" loading="lazy">
                <div style="padding: 20px;">
                    <h3 style="margin-bottom: 10px; font-size: 18px;">${item.title}</h3>
                    <p style="color: var(--admin-text-secondary); font-size: 14px; margin-bottom: 15px; line-height: 1.5;">${item.description}</p>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="status-badge" style="background: rgba(99,102,241,0.1); color: var(--admin-primary);">
                            ${this.getCategoryName(item.category)}
                        </span>
                        <div class="action-btns">
                            <button class="action-btn edit" onclick="admin.editPortfolio('${item.id}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn delete" onclick="admin.deletePortfolio('${item.id}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    addPortfolio() {
        const fields = [
            new FormField({
                name: 'title',
                label: 'Название проекта',
                type: 'text',
                required: true,
                placeholder: 'Например: Прототип корпуса'
            }),
            new FormField({
                name: 'category',
                label: 'Категория',
                type: 'select',
                required: true,
                options: [
                    { value: 'prototype', label: 'Прототипы' },
                    { value: 'functional', label: 'Функциональные' },
                    { value: 'art', label: 'Художественные' },
                    { value: 'industrial', label: 'Промышленные' }
                ]
            }),
            new FormField({
                name: 'description',
                label: 'Описание',
                type: 'textarea',
                required: true,
                placeholder: 'Краткое описание проекта'
            }),
            new FormField({
                name: 'image',
                label: 'URL изображения',
                type: 'url',
                required: true,
                placeholder: 'https://...',
                helpText: 'Ссылка на изображение проекта'
            }),
            new FormField({
                name: 'details',
                label: 'Детали проекта',
                type: 'textarea',
                placeholder: 'Материал, технология, время печати...'
            })
        ];

        this.showFormModal('Добавить работу в портфолио', fields, (formData) => {
            db.addItem('portfolio', formData);
            this.showNotification('Работа добавлена в портфолио', 'success');
            this.renderPortfolio();
        });
    }

    editPortfolio(id) {
        const item = db.getItem('portfolio', id);
        if (!item) return;

        const fields = [
            new FormField({
                name: 'title',
                label: 'Название проекта',
                type: 'text',
                required: true,
                value: item.title
            }),
            new FormField({
                name: 'category',
                label: 'Категория',
                type: 'select',
                required: true,
                value: item.category,
                options: [
                    { value: 'prototype', label: 'Прототипы' },
                    { value: 'functional', label: 'Функциональные' },
                    { value: 'art', label: 'Художественные' },
                    { value: 'industrial', label: 'Промышленные' }
                ]
            }),
            new FormField({
                name: 'description',
                label: 'Описание',
                type: 'textarea',
                required: true,
                value: item.description
            }),
            new FormField({
                name: 'image',
                label: 'URL изображения',
                type: 'url',
                required: true,
                value: item.image
            }),
            new FormField({
                name: 'details',
                label: 'Детали проекта',
                type: 'textarea',
                value: item.details || ''
            })
        ];

        this.showFormModal('Редактировать работу', fields, (formData) => {
            db.updateItem('portfolio', id, formData);
            this.showNotification('Работа обновлена', 'success');
            this.renderPortfolio();
        });
    }

    deletePortfolio(id) {
        if (!confirm('Удалить эту работу из портфолио?')) return;

        db.deleteItem('portfolio', id);
        this.showNotification('Работа удалена', 'success');
        this.renderPortfolio();
    }

    getCategoryName(category) {
        const names = {
            'prototype': 'Прототипы',
            'functional': 'Функциональные',
            'art': 'Художественные',
            'industrial': 'Промышленные'
        };
        return names[category] || category;
    }
    // ========================================
    // SERVICES MANAGEMENT
    // ========================================

    loadServices() {
        const services = db.getData('services') || [];
        this.renderServices(services);
    }

    renderServices(services = null) {
        if (!services) {
            services = db.getData('services') || [];
        }

        services.sort((a, b) => (a.order || 0) - (b.order || 0));

        const list = document.getElementById('servicesAdminList');
        if (!list) return;

        if (services.length === 0) {
            list.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--admin-text-secondary);">Услуг пока нет</p>';
            return;
        }

        list.innerHTML = services.map(service => `
            <div class="service-admin-item" style="background: white; padding: 25px; border-radius: 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                        <div style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--admin-primary), var(--admin-primary-dark)); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                            <i class="fas ${service.icon}"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0;">${service.name}</h3>
                            ${service.featured ? '<span class="status-badge status-new" style="font-size: 11px; margin-left: 10px;">Популярное</span>' : ''}
                        </div>
                    </div>
                    <p style="color: var(--admin-text-secondary); margin-bottom: 10px;">${service.description}</p>
                    <strong style="color: var(--admin-primary);">${service.price}</strong>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <label class="toggle-switch">
                        <input type="checkbox" ${service.active ? 'checked' : ''} onchange="admin.toggleService('${service.id}')">
                        <span class="toggle-slider"></span>
                    </label>
                    <div class="action-btns">
                        <button class="action-btn" onclick="admin.moveService('${service.id}', 'up')" title="Вверх">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <button class="action-btn" onclick="admin.moveService('${service.id}', 'down')" title="Вниз">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button class="action-btn edit" onclick="admin.editService('${service.id}')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="admin.deleteService('${service.id}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    addService() {
        const fields = [
            new FormField({
                name: 'name',
                label: 'Название услуги',
                type: 'text',
                required: true,
                placeholder: 'Например: FDM печать'
            }),
            new FormField({
                name: 'slug',
                label: 'Слаг (для URL)',
                type: 'text',
                required: true,
                placeholder: 'fdm-pechat'
            }),
            new FormField({
                name: 'icon',
                label: 'Иконка (Font Awesome)',
                type: 'text',
                required: true,
                placeholder: 'fa-cube',
                helpText: 'Класс иконки без "fas", например: fa-cube'
            }),
            new FormField({
                name: 'description',
                label: 'Описание',
                type: 'textarea',
                required: true
            }),
            new FormField({
                name: 'price',
                label: 'Цена',
                type: 'text',
                required: true,
                placeholder: 'от 50₽/г'
            }),
            new FormField({
                name: 'featured',
                label: '',
                type: 'checkbox',
                placeholder: 'Отметить как популярное'
            })
        ];

        this.showFormModal('Добавить услугу', fields, (formData) => {
            const service = {
                ...formData,
                features: [],
                active: true,
                order: (db.getData('services') || []).length + 1
            };

            db.addItem('services', service);
            this.showNotification('Услуга добавлена', 'success');
            this.renderServices();
        });
    }

    editService(id) {
        const service = db.getItem('services', id);
        if (!service) return;

        const fields = [
            new FormField({
                name: 'name',
                label: 'Название',
                type: 'text',
                required: true,
                value: service.name
            }),
            new FormField({
                name: 'slug',
                label: 'Слаг',
                type: 'text',
                required: true,
                value: service.slug
            }),
            new FormField({
                name: 'icon',
                label: 'Иконка',
                type: 'text',
                required: true,
                value: service.icon
            }),
            new FormField({
                name: 'description',
                label: 'Описание',
                type: 'textarea',
                required: true,
                value: service.description
            }),
            new FormField({
                name: 'price',
                label: 'Цена',
                type: 'text',
                required: true,
                value: service.price
            }),
            new FormField({
                name: 'featured',
                label: '',
                type: 'checkbox',
                placeholder: 'Популярное',
                value: service.featured
            })
        ];

        this.showFormModal('Редактировать услугу', fields, (formData) => {
            db.updateItem('services', id, formData);
            this.showNotification('Услуга обновлена', 'success');
            this.renderServices();
        });
    }

    deleteService(id) {
        if (!confirm('Удалить эту услугу?')) return;

        db.deleteItem('services', id);
        this.showNotification('Услуга удалена', 'success');
        this.renderServices();
    }

    toggleService(id) {
        const service = db.getItem('services', id);
        if (service) {
            db.updateItem('services', id, { active: !service.active });
            this.showNotification(`Услуга ${!service.active ? 'активирована' : 'деактивирована'}`, 'success');
        }
    }

    moveService(id, direction) {
        const services = db.getData('services') || [];
        const index = services.findIndex(s => s.id === id);

        if (index === -1) return;

        if (direction === 'up' && index > 0) {
            [services[index], services[index - 1]] = [services[index - 1], services[index]];
        } else if (direction === 'down' && index < services.length - 1) {
            [services[index], services[index + 1]] = [services[index + 1], services[index]];
        } else {
            return;
        }

        services.forEach((service, i) => {
            service.order = i + 1;
        });

        db.saveData('services', services);
        this.renderServices();
    }

    // ========================================
    // TESTIMONIALS MANAGEMENT
    // ========================================

    loadTestimonials() {
        const testimonials = db.getData('testimonials') || [];
        this.renderTestimonials(testimonials);
    }

    renderTestimonials(testimonials = null) {
        if (!testimonials) {
            testimonials = db.getData('testimonials') || [];
        }

        testimonials.sort((a, b) => (a.order || 0) - (b.order || 0));

        const grid = document.getElementById('testimonialsAdminGrid');
        if (!grid) return;

        if (testimonials.length === 0) {
            grid.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--admin-text-secondary); grid-column: 1/-1;">Отзывов пока нет</p>';
            return;
        }

        grid.innerHTML = testimonials.map(item => `
            <div class="testimonial-admin-item" style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <img src="${item.avatar}" alt="${item.name}" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
                    <div style="flex: 1;">
                        <h4 style="margin-bottom: 5px;">${item.name}</h4>
                        <p style="color: var(--admin-text-secondary); font-size: 14px; margin-bottom: 5px;">${item.position}</p>
                        <div style="color: #fbbf24;">
                            ${'★'.repeat(item.rating)}${'☆'.repeat(5 - item.rating)}
                        </div>
                    </div>
                </div>
                <p style="color: var(--admin-text-secondary); font-style: italic; margin-bottom: 15px; line-height: 1.6;">"${item.text}"</p>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--admin-border);">
                    <span class="status-badge ${item.approved ? 'status-completed' : 'status-new'}">
                        ${item.approved ? 'Опубликован' : 'На модерации'}
                    </span>
                    <div class="action-btns">
                        ${!item.approved ? `<button class="action-btn" style="background: rgba(16,185,129,0.1); color: var(--admin-success);" onclick="admin.approveTestimonial('${item.id}')">
                            <i class="fas fa-check"></i>
                        </button>` : ''}
                        <button class="action-btn" onclick="admin.moveTestimonial('${item.id}', 'up')">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <button class="action-btn" onclick="admin.moveTestimonial('${item.id}', 'down')">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button class="action-btn edit" onclick="admin.editTestimonial('${item.id}')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="admin.deleteTestimonial('${item.id}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    addTestimonial() {
        const fields = [
            new FormField({
                name: 'name',
                label: 'Имя клиента',
                type: 'text',
                required: true,
                placeholder: 'Иван Петров'
            }),
            new FormField({
                name: 'position',
                label: 'Должность/Компания',
                type: 'text',
                required: true,
                placeholder: 'Директор, Tech Solutions'
            }),
            new FormField({
                name: 'avatar',
                label: 'URL аватара',
                type: 'url',
                required: true,
                placeholder: 'https://...'
            }),
            new FormField({
                name: 'rating',
                label: 'Оценка',
                type: 'select',
                required: true,
                options: [
                    { value: '5', label: '5 звёзд' },
                    { value: '4', label: '4 звезды' },
                    { value: '3', label: '3 звезды' }
                ]
            }),
            new FormField({
                name: 'text',
                label: 'Текст отзыва',
                type: 'textarea',
                required: true
            }),
            new FormField({
                name: 'approved',
                label: '',
                type: 'checkbox',
                placeholder: 'Опубликовать сразу'
            })
        ];

        this.showFormModal('Добавить отзыв', fields, (formData) => {
            const testimonial = {
                ...formData,
                rating: parseInt(formData.rating),
                order: (db.getData('testimonials') || []).length + 1
            };

            db.addItem('testimonials', testimonial);
            this.showNotification('Отзыв добавлен', 'success');
            this.renderTestimonials();
        });
    }

    editTestimonial(id) {
        const item = db.getItem('testimonials', id);
        if (!item) return;

        const fields = [
            new FormField({
                name: 'name',
                label: 'Имя',
                type: 'text',
                required: true,
                value: item.name
            }),
            new FormField({
                name: 'position',
                label: 'Должность',
                type: 'text',
                required: true,
                value: item.position
            }),
            new FormField({
                name: 'avatar',
                label: 'URL аватара',
                type: 'url',
                required: true,
                value: item.avatar
            }),
            new FormField({
                name: 'rating',
                label: 'Оценка',
                type: 'select',
                required: true,
                value: item.rating.toString(),
                options: [
                    { value: '5', label: '5 звёзд' },
                    { value: '4', label: '4 звезды' },
                    { value: '3', label: '3 звезды' }
                ]
            }),
            new FormField({
                name: 'text',
                label: 'Текст',
                type: 'textarea',
                required: true,
                value: item.text
            }),
            new FormField({
                name: 'approved',
                label: '',
                type: 'checkbox',
                placeholder: 'Опубликован',
                value: item.approved
            })
        ];

        this.showFormModal('Редактировать отзыв', fields, (formData) => {
            db.updateItem('testimonials', id, {
                ...formData,
                rating: parseInt(formData.rating)
            });
            this.showNotification('Отзыв обновлён', 'success');
            this.renderTestimonials();
        });
    }

    deleteTestimonial(id) {
        if (!confirm('Удалить этот отзыв?')) return;

        db.deleteItem('testimonials', id);
        this.showNotification('Отзыв удалён', 'success');
        this.renderTestimonials();
    }

    approveTestimonial(id) {
        db.updateItem('testimonials', id, { approved: true });
        this.showNotification('Отзыв одобрен', 'success');
        this.renderTestimonials();
    }

    moveTestimonial(id, direction) {
        const items = db.getData('testimonials') || [];
        const index = items.findIndex(t => t.id === id);

        if (index === -1) return;

        if (direction === 'up' && index > 0) {
            [items[index], items[index - 1]] = [items[index - 1], items[index]];
        } else if (direction === 'down' && index < items.length - 1) {
            [items[index], items[index + 1]] = [items[index + 1], items[index]];
        } else {
            return;
        }

        items.forEach((item, i) => {
            item.order = i + 1;
        });

        db.saveData('testimonials', items);
        this.renderTestimonials();
    }
    // ========================================
    // CALCULATOR SETTINGS (ИСПРАВЛЕНО #9)
    // ========================================

    loadCalculatorSettings() {
        console.log('⚙️ Загрузка настроек калькулятора...');

        const settings = db.getOrCreateSettings();

        if (settings.calculator) {
            if (settings.calculator.materialPrices) {
                CONFIG.materialPrices = settings.calculator.materialPrices;
            }
            if (settings.calculator.servicePrices) {
                CONFIG.servicePrices = settings.calculator.servicePrices;
            }
            if (settings.calculator.qualityMultipliers) {
                CONFIG.qualityMultipliers = settings.calculator.qualityMultipliers;
            }
            if (settings.calculator.discounts) {
                CONFIG.discounts = settings.calculator.discounts;
            }
        }

        this.renderMaterialPrices();
        this.renderServicePrices();
        this.renderDiscounts();
        this.renderQualityMultipliers();
    }

    renderMaterialPrices() {
        const materials = CONFIG.materialPrices;
        const container = document.getElementById('materialPrices');
        if (!container) return;

        container.innerHTML = Object.entries(materials).map(([key, mat]) => `
            <div class="price-input-group">
                <label>${mat.name} (₽/г) <small style="color: var(--admin-text-secondary);">${mat.technology.toUpperCase()}</small></label>
                <input type="number" 
                       value="${mat.price}" 
                       class="form-control" 
                       data-material="${key}"
                       min="0"
                       step="1">
            </div>
        `).join('');
    }

    renderServicePrices() {
        const prices = CONFIG.servicePrices;

        if (document.getElementById('modelingPrice')) {
            document.getElementById('modelingPrice').value = prices.modeling.price;
        }
        if (document.getElementById('postProcessingPrice')) {
            document.getElementById('postProcessingPrice').value = prices.postProcessing.price;
        }
        if (document.getElementById('paintingPrice')) {
            document.getElementById('paintingPrice').value = prices.painting.price;
        }
        if (document.getElementById('expressPrice')) {
            document.getElementById('expressPrice').value = prices.express.price;
        }
    }

    renderDiscounts() {
        const discounts = CONFIG.discounts;

        if (document.getElementById('discount10')) {
            const discount10 = discounts.find(d => d.minQuantity === 10);
            document.getElementById('discount10').value = discount10 ? discount10.percent : 10;
        }
        if (document.getElementById('discount50')) {
            const discount50 = discounts.find(d => d.minQuantity === 50);
            document.getElementById('discount50').value = discount50 ? discount50.percent : 15;
        }
        if (document.getElementById('discount100')) {
            const discount100 = discounts.find(d => d.minQuantity === 100);
            document.getElementById('discount100').value = discount100 ? discount100.percent : 20;
        }
    }

    renderQualityMultipliers() {
        const quality = CONFIG.qualityMultipliers;

        if (document.getElementById('qualityDraft')) {
            document.getElementById('qualityDraft').value = quality.draft.multiplier;
        }
        if (document.getElementById('qualityNormal')) {
            document.getElementById('qualityNormal').value = quality.normal.multiplier;
        }
        if (document.getElementById('qualityHigh')) {
            document.getElementById('qualityHigh').value = quality.high.multiplier;
        }
        if (document.getElementById('qualityUltra')) {
            document.getElementById('qualityUltra').value = quality.ultra.multiplier;
        }
    }

    saveCalculatorSettings() {
        console.log('💾 Сохранение настроек калькулятора...');

        // Material prices
        const materialInputs = document.querySelectorAll('#materialPrices input');
        materialInputs.forEach(input => {
            const key = input.getAttribute('data-material');
            const value = parseFloat(input.value);
            if (key && !isNaN(value)) {
                CONFIG.materialPrices[key].price = value;
            }
        });

        // Service prices
        CONFIG.servicePrices.modeling.price = parseFloat(document.getElementById('modelingPrice').value) || 500;
        CONFIG.servicePrices.postProcessing.price = parseFloat(document.getElementById('postProcessingPrice').value) || 300;
        CONFIG.servicePrices.painting.price = parseFloat(document.getElementById('paintingPrice').value) || 500;
        CONFIG.servicePrices.express.price = parseFloat(document.getElementById('expressPrice').value) || 1000;

        // Discounts
        CONFIG.discounts = [
            { minQuantity: 10, percent: parseFloat(document.getElementById('discount10')?.value || 10) },
            { minQuantity: 50, percent: parseFloat(document.getElementById('discount50')?.value || 15) },
            { minQuantity: 100, percent: parseFloat(document.getElementById('discount100')?.value || 20) }
        ];

        // Quality multipliers
        CONFIG.qualityMultipliers.draft.multiplier = parseFloat(document.getElementById('qualityDraft')?.value || 0.8);
        CONFIG.qualityMultipliers.normal.multiplier = parseFloat(document.getElementById('qualityNormal')?.value || 1.0);
        CONFIG.qualityMultipliers.high.multiplier = parseFloat(document.getElementById('qualityHigh')?.value || 1.3);
        CONFIG.qualityMultipliers.ultra.multiplier = parseFloat(document.getElementById('qualityUltra')?.value || 1.6);

        // Сохраняем через новый метод db.updateSettings()
        db.updateSettings({
            calculator: {
                materialPrices: CONFIG.materialPrices,
                servicePrices: CONFIG.servicePrices,
                discounts: CONFIG.discounts,
                qualityMultipliers: CONFIG.qualityMultipliers
            }
        });

        console.log('✅ Настройки калькулятора сохранены в БД');
        console.log('Цены материалов:', CONFIG.materialPrices);
        console.log('Цены услуг:', CONFIG.servicePrices);

        // ДОБАВЛЕНО: Открываем новую вкладку с главной для проверки
        const updateMessage = `
        ✅ Настройки сохранены!
        
        Для применения цен на главной странице:
        1. Откройте главную страницу (index.html)
        2. Обновите страницу (F5)
        3. Проверьте калькулятор
    `;

        this.showNotification('✅ Настройки калькулятора сохранены!', 'success');

        // Показываем диалог с инструкцией
        if (confirm(updateMessage + '\n\nОткрыть главную страницу сейчас?')) {
            window.open('index.html', '_blank');
        }
    }

    // ========================================
    // CONTENT MANAGEMENT (ИСПРАВЛЕНО #11)
    // ========================================

    loadContent() {
        console.log('📄 Загрузка контента...');

        const content = db.getOrCreateContent();
        const settings = db.getOrCreateSettings();

        console.log('Content:', content);
        console.log('Settings:', settings);

        // Hero
        if (document.getElementById('heroTitle')) {
            document.getElementById('heroTitle').value = content.hero?.title || '';
        }
        if (document.getElementById('heroDescription')) {
            document.getElementById('heroDescription').value = content.hero?.subtitle || '';
        }

        // Contact info
        if (document.getElementById('contentAddress')) {
            document.getElementById('contentAddress').value = settings.address || '';
        }
        if (document.getElementById('contentPhone')) {
            document.getElementById('contentPhone').value = settings.contactPhone || '';
        }
        if (document.getElementById('contentEmail')) {
            document.getElementById('contentEmail').value = settings.contactEmail || '';
        }
        if (document.getElementById('contentWorkingHours')) {
            document.getElementById('contentWorkingHours').value = settings.workingHours || '';
        }

        // Social
        if (settings.socialLinks) {
            if (document.getElementById('socialVk')) {
                document.getElementById('socialVk').value = settings.socialLinks.vk || '';
            }
            if (document.getElementById('socialTelegram')) {
                document.getElementById('socialTelegram').value = settings.socialLinks.telegram || 'https://t.me/PrintPro_Omsk';
            }
            if (document.getElementById('socialWhatsapp')) {
                document.getElementById('socialWhatsapp').value = settings.socialLinks.whatsapp || '';
            }
            if (document.getElementById('socialYoutube')) {
                document.getElementById('socialYoutube').value = settings.socialLinks.youtube || '';
            }
        }

        this.loadFAQEditor();
    }

    loadFAQEditor() {
        const faqs = db.getData('faq') || [];
        const container = document.getElementById('faqEditor');
        if (!container) return;

        container.innerHTML = faqs.map((faq, index) => `
            <div class="faq-editor-item" style="background: var(--admin-bg); padding: 20px; border-radius: 10px; margin-bottom: 15px;">
                <div style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <div class="form-group">
                            <label>Вопрос</label>
                            <input type="text" class="form-control" value="${faq.question}" data-faq-id="${faq.id}" data-field="question">
                        </div>
                        <div class="form-group">
                            <label>Ответ</label>
                            <textarea class="form-control" rows="3" data-faq-id="${faq.id}" data-field="answer">${faq.answer}</textarea>
                        </div>
                        <label class="checkbox-label">
                            <input type="checkbox" ${faq.active ? 'checked' : ''} data-faq-id="${faq.id}" data-field="active">
                            <span>Активен</span>
                        </label>
                    </div>
                    <div class="action-btns" style="flex-direction: column;">
                        <button class="action-btn" onclick="admin.moveFAQ('${faq.id}', 'up')">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <button class="action-btn" onclick="admin.moveFAQ('${faq.id}', 'down')">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button class="action-btn delete" onclick="admin.deleteFAQ('${faq.id}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        // Add change listeners
        container.querySelectorAll('input, textarea').forEach(field => {
            field.addEventListener('change', (e) => {
                const id = e.target.getAttribute('data-faq-id');
                const fieldName = e.target.getAttribute('data-field');
                const value = e.target.type === 'checkbox' ? e.target.checked : e.target.value;

                db.updateItem('faq', id, { [fieldName]: value });
            });
        });
    }

    addFAQItem() {
        const fields = [
            new FormField({
                name: 'question',
                label: 'Вопрос',
                type: 'text',
                required: true
            }),
            new FormField({
                name: 'answer',
                label: 'Ответ',
                type: 'textarea',
                required: true
            }),
            new FormField({
                name: 'active',
                label: '',
                type: 'checkbox',
                placeholder: 'Активен',
                value: true
            })
        ];

        this.showFormModal('Добавить вопрос', fields, (formData) => {
            const faq = {
                ...formData,
                order: (db.getData('faq') || []).length + 1
            };

            db.addItem('faq', faq);
            this.showNotification('Вопрос добавлен', 'success');
            this.loadFAQEditor();
        });
    }

    deleteFAQ(id) {
        if (!confirm('Удалить этот вопрос?')) return;

        db.deleteItem('faq', id);
        this.showNotification('Вопрос удалён', 'success');
        this.loadFAQEditor();
    }

    moveFAQ(id, direction) {
        const items = db.getData('faq') || [];
        const index = items.findIndex(f => f.id === id);

        if (index === -1) return;

        if (direction === 'up' && index > 0) {
            [items[index], items[index - 1]] = [items[index - 1], items[index]];
        } else if (direction === 'down' && index < items.length - 1) {
            [items[index], items[index + 1]] = [items[index + 1], items[index]];
        } else {
            return;
        }

        items.forEach((item, i) => {
            item.order = i + 1;
        });

        db.saveData('faq', items);
        this.loadFAQEditor();
    }

    saveContent() {
        console.log('💾 Сохранение контента...');

        const contentData = {
            hero: {
                title: document.getElementById('heroTitle')?.value || '',
                subtitle: document.getElementById('heroDescription')?.value || ''
            }
        };

        const settingsData = {
            address: document.getElementById('contentAddress')?.value || '',
            contactPhone: document.getElementById('contentPhone')?.value || '',
            contactEmail: document.getElementById('contentEmail')?.value || '',
            workingHours: document.getElementById('contentWorkingHours')?.value || '',
            socialLinks: {
                vk: document.getElementById('socialVk')?.value || '',
                telegram: document.getElementById('socialTelegram')?.value || 'https://t.me/PrintPro_Omsk',
                whatsapp: document.getElementById('socialWhatsapp')?.value || '',
                youtube: document.getElementById('socialYoutube')?.value || ''
            }
        };

        console.log('Content data:', contentData);
        console.log('Settings data:', settingsData);

        // Сохраняем через новые методы
        db.updateContent(contentData);
        db.updateSettings(settingsData);

        console.log('✅ Контент сохранён в БД');

        this.showNotification('✅ Контент успешно сохранён', 'success');
    }

    // ========================================
    // FORMS MANAGEMENT (ИСПРАВЛЕНО #12)
    // ========================================

    loadFormSettings() {
        console.log('📝 Загрузка настроек форм...');

        const settings = db.getOrCreateSettings();

        if (settings.formFields) {
            CONFIG.formFields = settings.formFields;
        }

        this.renderFormFields('contact');
        this.loadTelegramSettings();
    }

    renderFormFields(formType) {
        const fields = CONFIG.formFields[formType] || [];
        const container = document.getElementById(`${formType}FormFields`);
        if (!container) return;

        // Сортируем по order
        const sortedFields = [...fields].sort((a, b) => (a.order || 0) - (b.order || 0));

        container.innerHTML = sortedFields.map((field, index) => `
        <div class="form-field-item" style="padding: 20px; background: ${field.enabled ? 'var(--admin-bg)' : 'rgba(239,68,68,0.05)'}; border-radius: 10px; margin-bottom: 15px; border: 2px solid ${field.enabled ? 'var(--admin-border)' : 'rgba(239,68,68,0.2)'};">
            <div style="display: flex; justify-content: space-between; align-items: start; gap: 15px;">
                <div style="flex: 1; ${!field.enabled ? 'opacity: 0.5;' : ''}">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="font-size: 13px; color: var(--admin-text-secondary); font-weight: 600;">Название поля</label>
                        <input type="text" class="form-control" value="${field.label}" 
                               onchange="admin.updateFormField('${formType}', ${index}, 'label', this.value)"
                               style="font-weight: 500;">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label style="font-size: 13px; color: var(--admin-text-secondary);">Тип поля</label>
                            <select class="form-control" onchange="admin.updateFormField('${formType}', ${index}, 'type', this.value)">
                                <option value="text" ${field.type === 'text' ? 'selected' : ''}>Текст</option>
                                <option value="email" ${field.type === 'email' ? 'selected' : ''}>Email</option>
                                <option value="tel" ${field.type === 'tel' ? 'selected' : ''}>Телефон</option>
                                <option value="textarea" ${field.type === 'textarea' ? 'selected' : ''}>Текст (многострочный)</option>
                                <option value="select" ${field.type === 'select' ? 'selected' : ''}>Выбор из списка</option>
                                <option value="checkbox" ${field.type === 'checkbox' ? 'selected' : ''}>Чекбокс</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label style="font-size: 13px; color: var(--admin-text-secondary);">Атрибут name</label>
                            <input type="text" class="form-control" value="${field.name}" readonly 
                                   style="background: var(--admin-bg); cursor: not-allowed; font-family: monospace; font-size: 12px;">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label style="font-size: 13px; color: var(--admin-text-secondary);">Порядок</label>
                            <input type="number" class="form-control" value="${field.order || index + 1}" 
                                   onchange="admin.updateFormField('${formType}', ${index}, 'order', parseInt(this.value))"
                                   min="1" max="100">
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 10px;">
                        <label style="font-size: 13px; color: var(--admin-text-secondary);">Placeholder / Подсказка</label>
                        <input type="text" class="form-control" value="${field.placeholder || ''}" 
                               onchange="admin.updateFormField('${formType}', ${index}, 'placeholder', this.value)"
                               placeholder="Введите подсказку...">
                    </div>
                    
                    ${field.type === 'select' ? `
                    <div class="form-group" style="margin-bottom: 10px;">
                        <label style="font-size: 13px; color: var(--admin-text-secondary);">Варианты для выбора (через запятую)</label>
                        <input type="text" class="form-control" value="${Array.isArray(field.options) ? field.options.join(', ') : ''}" 
                               onchange="admin.updateFormFieldOptions('${formType}', ${index}, this.value)"
                               placeholder="Вариант 1, Вариант 2, Вариант 3">
                    </div>
                    ` : ''}
                    
                    <div style="display: flex; gap: 20px; align-items: center; margin-top: 10px;">
    <label class="checkbox-label" style="margin: 0;">
        <input type="checkbox" ${field.required ? 'checked' : ''} 
               ${['name', 'email', 'phone', 'message'].includes(field.name) ? 'disabled' : ''}
               onchange="admin.updateFormField('${formType}', ${index}, 'required', this.checked)">
        <span ${['name', 'email', 'phone', 'message'].includes(field.name) ? 'style="opacity: 0.5;" title="Системное поле"' : ''}>
            Обязательное поле ${field.required ? '✓' : ''}
        </span>
    </label>
    <label class="checkbox-label" style="margin: 0;">
        <input type="checkbox" ${field.enabled ? 'checked' : ''} 
               onchange="admin.updateFormField('${formType}', ${index}, 'enabled', this.checked)">
        <span style="font-weight: 600; color: ${field.enabled ? 'var(--admin-success)' : 'var(--admin-danger)'};">
            ${field.enabled ? '✓ Активно' : '✗ Отключено'}
        </span>
    </label>
</div>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 5px; flex-shrink: 0;">
                    <button class="action-btn" onclick="admin.moveFormField('${formType}', ${index}, 'up')" title="Вверх">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <button class="action-btn" onclick="admin.moveFormField('${formType}', ${index}, 'down')" title="Вниз">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                    <button class="action-btn delete" onclick="admin.deleteFormField('${formType}', ${index})" title="Удалить поле">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
    }
    updateFormField(formType, index, property, value) {
        if (!CONFIG.formFields[formType][index]) return;

        const field = CONFIG.formFields[formType][index];

        // ИСПРАВЛЕНО: Защита от некорректных изменений
        if (property === 'required') {
            // Обязательные поля name, email, phone, message нельзя сделать необязательными
            if (['name', 'email', 'phone', 'message'].includes(field.name) && value === false) {
                this.showNotification('⚠️ Это системное поле должно оставаться обязательным', 'warning');
                this.renderFormFields(formType);
                return;
            }
        }

        CONFIG.formFields[formType][index][property] = value;

        console.log(`✏️ Обновлено поле ${formType}[${index}].${property} = ${value}`);

        // Автосохранение
        db.updateSettings({
            formFields: CONFIG.formFields
        });

        console.log('✅ Изменения сохранены в БД');

        // Перерисовываем админку
        this.renderFormFields(formType);
    }
    updateFormFieldOptions(formType, index, optionsString) {
        if (!CONFIG.formFields[formType][index]) return;

        // Преобразуем строку в массив
        const options = optionsString.split(',').map(opt => opt.trim()).filter(opt => opt);

        CONFIG.formFields[formType][index].options = options;

        console.log(`✏️ Обновлены опции для ${formType}[${index}]:`, options);

        // Автосохранение
        db.updateSettings({
            formFields: CONFIG.formFields
        });

        this.renderFormFields(formType);
    }
    moveFormField(formType, index, direction) {
        const fields = CONFIG.formFields[formType];

        if (direction === 'up' && index > 0) {
            [fields[index], fields[index - 1]] = [fields[index - 1], fields[index]];
        } else if (direction === 'down' && index < fields.length - 1) {
            [fields[index], fields[index + 1]] = [fields[index + 1], fields[index]];
        } else {
            return;
        }

        // Обновляем order
        fields.forEach((field, i) => {
            field.order = i + 1;
        });

        db.updateSettings({
            formFields: CONFIG.formFields
        });

        this.renderFormFields(formType);
    }
    saveFormSettings() {
        console.log('💾 Сохранение настроек форм...');

        const telegramToggle = document.getElementById('telegramNotifications');
        if (telegramToggle) {
            CONFIG.features.telegramNotifications = telegramToggle.checked;
        }

        db.updateSettings({
            formFields: CONFIG.formFields,
            telegramNotifications: telegramToggle ? telegramToggle.checked : true
        });

        console.log('✅ Настройки формы сохранены в БД');

        // НОВОЕ: Уведомление о необходимости обновить главную
        this.showNotification('✅ Настройки сохранены! Обновите главную страницу для применения.', 'success');

        // Предложение открыть главную
        if (confirm('Настройки сохранены!\n\nОткрыть главную страницу для проверки изменений?')) {
            window.open('index.html', '_blank');
        }
    }
    addFormField(formType) {
        const newField = {
            name: 'custom_field_' + Date.now(),
            label: 'Новое поле',
            type: 'text',
            required: false,
            enabled: true,
            placeholder: 'Введите значение...',
            order: CONFIG.formFields[formType].length + 1
        };

        CONFIG.formFields[formType].push(newField);

        db.updateSettings({
            formFields: CONFIG.formFields
        });

        this.showNotification('✅ Поле добавлено. Отредактируйте его настройки.', 'success');
        this.renderFormFields(formType);
    }

    deleteFormField(formType, index) {
        const field = CONFIG.formFields[formType][index];

        // Защита от удаления обязательных системных полей
        if (['name', 'email', 'phone', 'message'].includes(field.name)) {
            if (!confirm(`⚠️ ВНИМАНИЕ!\n\nВы удаляете системное поле "${field.label}".\nЭто может нарушить работу формы.\n\nПродолжить?`)) {
                return;
            }
        } else {
            if (!confirm(`Удалить поле "${field.label}"?`)) {
                return;
            }
        }

        CONFIG.formFields[formType].splice(index, 1);

        // Обновляем order
        CONFIG.formFields[formType].forEach((f, i) => {
            f.order = i + 1;
        });

        db.updateSettings({
            formFields: CONFIG.formFields
        });

        this.showNotification('✅ Поле удалено', 'success');
        this.renderFormFields(formType);
    }

    addFormField(formType) {
        const newField = {
            name: 'custom_field_' + Date.now(),
            label: 'Новое поле',
            type: 'text',
            required: false,
            enabled: true,
            placeholder: 'Введите значение...'
        };

        CONFIG.formFields[formType].push(newField);

        db.updateSettings({
            formFields: CONFIG.formFields
        });

        this.showNotification('Поле добавлено', 'success');
        this.renderFormFields(formType);
    }
    saveFormSettings() {
        console.log('💾 Сохранение настроек форм...');

        const telegramToggle = document.getElementById('telegramNotifications');
        if (telegramToggle) {
            CONFIG.features.telegramNotifications = telegramToggle.checked;
        }

        db.updateSettings({
            formFields: CONFIG.formFields,
            telegramNotifications: telegramToggle ? telegramToggle.checked : true
        });

        console.log('✅ Настройки формы сохранены в БД');

        this.showNotification('✅ Настройки формы сохранены', 'success');
    }

    // ========================================
    // TELEGRAM SETTINGS
    // ========================================

    loadTelegramSettings() {
        const settings = db.getOrCreateSettings();
        const chatIdInput = document.getElementById('telegramChatId');
        const notifToggle = document.getElementById('telegramNotifications');

        if (chatIdInput) {
            chatIdInput.value = settings?.telegram?.chatId || CONFIG.telegram.chatId || '';
        }

        if (notifToggle) {
            notifToggle.checked = settings?.telegramNotifications !== undefined
                ? settings.telegramNotifications
                : CONFIG.features.telegramNotifications;
        }
    }

    async getTelegramChatId() {
        this.showNotification('🔄 Получение Chat ID из Telegram...', 'info');

        try {
            const result = await telegramBot.getUpdates();

            if (result.success && result.chatId) {
                const chatIdInput = document.getElementById('telegramChatId');
                if (chatIdInput) {
                    chatIdInput.value = result.chatId;
                }

                CONFIG.telegram.chatId = result.chatId;

                this.showNotification(`✅ Chat ID получен: ${result.chatId}`, 'success');
            } else {
                this.showNotification('⚠️ Не удалось получить Chat ID. Отправьте сообщение боту и попробуйте снова.', 'warning');
            }
        } catch (error) {
            this.showNotification('❌ Ошибка: ' + error.message, 'error');
        }
    }

    async testTelegramConnection() {
        const chatId = document.getElementById('telegramChatId')?.value;

        if (!chatId) {
            this.showNotification('⚠️ Сначала укажите или получите Chat ID', 'warning');
            return;
        }

        CONFIG.telegram.chatId = chatId;

        this.showNotification('📤 Отправка тестового сообщения...', 'info');

        try {
            const result = await telegramBot.sendTestMessage();

            if (result.success) {
                this.showNotification('✅ Тестовое сообщение успешно отправлено! Проверьте Telegram.', 'success');
            } else {
                this.showNotification('❌ Ошибка отправки: ' + result.error, 'error');
            }
        } catch (error) {
            this.showNotification('❌ Ошибка: ' + error.message, 'error');
        }
    }

    saveTelegramSettings() {
        const chatId = document.getElementById('telegramChatId')?.value;
        const notifEnabled = document.getElementById('telegramNotifications')?.checked;

        if (!chatId) {
            this.showNotification('⚠️ Введите Chat ID', 'warning');
            return;
        }

        console.log('💾 Сохранение Telegram настроек...', { chatId, notifEnabled });

        CONFIG.telegram.chatId = chatId;
        CONFIG.features.telegramNotifications = notifEnabled;

        db.updateSettings({
            telegram: {
                chatId: chatId
            },
            telegramNotifications: notifEnabled
        });

        console.log('✅ Telegram настройки сохранены');

        this.showNotification('✅ Настройки Telegram сохранены', 'success');
    }
    // ========================================
    // GENERAL SETTINGS (ИСПРАВЛЕНО #15)
    // ========================================

    loadSettings() {
        console.log('⚙️ Загрузка общих настроек...');

        const settings = db.getOrCreateSettings();

        console.log('Settings:', settings);

        if (document.getElementById('settingsSiteName')) {
            document.getElementById('settingsSiteName').value = settings.siteName || '3D Print Pro';
        }
        if (document.getElementById('settingsAdminEmail')) {
            document.getElementById('settingsAdminEmail').value = settings.contactEmail || '';
        }
        if (document.getElementById('settingsTimezone')) {
            document.getElementById('settingsTimezone').value = settings.timezone || 'Europe/Moscow';
        }

        // Color picker
        if (document.getElementById('colorPrimary')) {
            document.getElementById('colorPrimary').value = settings.colorPrimary || '#6366f1';
        }
        if (document.getElementById('colorSecondary')) {
            document.getElementById('colorSecondary').value = settings.colorSecondary || '#ec4899';
        }
    }

    saveGeneralSettings() {
        console.log('💾 Сохранение общих настроек...');

        const settingsData = {
            siteName: document.getElementById('settingsSiteName')?.value || '3D Print Pro',
            contactEmail: document.getElementById('settingsAdminEmail')?.value || '',
            timezone: document.getElementById('settingsTimezone')?.value || 'Europe/Moscow',
            colorPrimary: document.getElementById('colorPrimary')?.value || '#6366f1',
            colorSecondary: document.getElementById('colorSecondary')?.value || '#ec4899'
        };

        console.log('Settings data:', settingsData);

        db.updateSettings(settingsData);

        console.log('✅ Настройки сохранены в БД');

        this.showNotification('✅ Настройки успешно сохранены', 'success');
    }

    changePassword() {
        const current = document.getElementById('currentPassword')?.value;
        const newPass = document.getElementById('newPassword')?.value;
        const confirm = document.getElementById('confirmPassword')?.value;

        if (!current || !newPass || !confirm) {
            this.showNotification('⚠️ Заполните все поля', 'warning');
            return;
        }

        if (current !== 'admin123') {
            this.showNotification('❌ Неверный текущий пароль', 'error');
            return;
        }

        if (newPass !== confirm) {
            this.showNotification('❌ Пароли не совпадают', 'error');
            return;
        }

        if (newPass.length < 6) {
            this.showNotification('⚠️ Пароль должен быть минимум 6 символов', 'warning');
            return;
        }

        this.showNotification('⚠️ В демо-версии смена пароля недоступна', 'warning');
    }

    exportAllData() {
        db.exportData();
        this.showNotification('✅ Все данные экспортированы', 'success');
    }

    importAllData() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';

        input.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (event) => {
                try {
                    const success = db.importData(event.target.result);
                    if (success) {
                        this.showNotification('✅ Данные импортированы успешно', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        this.showNotification('❌ Ошибка импорта данных', 'error');
                    }
                } catch (error) {
                    this.showNotification('❌ Неверный формат файла', 'error');
                }
            };
            reader.readAsText(file);
        });

        input.click();
    }

    resetDatabase() {
        if (!confirm('⚠️ ВЫ УВЕРЕНЫ? Все данные будут удалены и восстановлены начальные!')) return;
        if (!confirm('❗ Последнее предупреждение! Это действие необратимо!')) return;

        db.resetToDefault();
        this.showNotification('✅ База данных сброшена', 'success');
        setTimeout(() => location.reload(), 1500);
    }

    // ========================================
    // NOTIFICATIONS
    // ========================================

    loadNotifications() {
        this.notifications = [];

        const newOrders = (db.getData('orders') || []).filter(o => o.status === 'new');

        newOrders.forEach(order => {
            this.notifications.push({
                id: order.id,
                type: 'order',
                title: 'Новый заказ',
                text: `От ${order.clientName || order.name}`,
                time: order.createdAt,
                unread: true
            });
        });

        this.renderNotifications();
    }

    renderNotifications() {
        const container = document.getElementById('notificationsList');
        if (!container) return;

        if (this.notifications.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--admin-text-secondary);">Нет новых уведомлений</div>';
            return;
        }

        container.innerHTML = this.notifications.map(notif => `
            <div class="notification-item ${notif.unread ? 'unread' : ''}" onclick="admin.handleNotificationClick('${notif.id}', '${notif.type}')">
                <div class="notification-title">${notif.title}</div>
                <div class="notification-text">${notif.text}</div>
                <div class="notification-time">${this.getRelativeTime(notif.time)}</div>
            </div>
        `).join('');
    }

    handleNotificationClick(id, type) {
        this.closeAllDropdowns();

        if (type === 'order') {
            this.navigateToPage('orders');
            setTimeout(() => {
                this.viewOrder(id);
            }, 300);
        }
    }

    markAllRead() {
        this.notifications.forEach(n => n.unread = false);
        this.renderNotifications();
        document.getElementById('notificationDot')?.style.setProperty('display', 'none');
    }

    // ========================================
    // QUICK ACTIONS
    // ========================================

    quickToggleTelegram(enabled) {
        CONFIG.features.telegramNotifications = enabled;

        db.updateSettings({
            telegramNotifications: enabled
        });

        this.showNotification(enabled ? '✅ Telegram уведомления включены' : '⚠️ Telegram уведомления выключены', 'info');
    }

    quickToggleTheme(isDark) {
        if (isDark) {
            document.body.classList.add('dark-mode');
            localStorage.setItem('adminTheme', 'dark');
        } else {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('adminTheme', 'light');
        }
    }

    clearCache() {
        if (!confirm('Очистить кеш браузера?')) return;

        localStorage.removeItem('3dprintpro_cache');
        this.showNotification('✅ Кеш очищен', 'success');
    }

    viewProfile() {
        const content = `
            <div class="detail-section">
                <h4>Информация профиля</h4>
                <p><strong>Имя:</strong> ${this.currentUser?.name || 'Администратор'}</p>
                <p><strong>Логин:</strong> ${this.currentUser?.login || 'admin'}</p>
                <p><strong>Роль:</strong> ${this.currentUser?.role || 'admin'}</p>
                <p><strong>Последний вход:</strong> ${this.formatDate(this.currentUser?.loginTime || new Date().toISOString())}</p>
            </div>
            <div class="detail-section">
                <h4>Статистика</h4>
                <p><strong>Всего заказов:</strong> ${(db.getData('orders') || []).length}</p>
                <p><strong>Работ в портфолио:</strong> ${(db.getData('portfolio') || []).length}</p>
                <p><strong>Отзывов:</strong> ${(db.getData('testimonials') || []).length}</p>
            </div>
        `;

        this.createModal('Профиль администратора', content);
    }

    // ========================================
    // MODAL SYSTEM
    // ========================================

    createModal(title, content) {
        const modal = document.createElement('div');
        modal.className = 'custom-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-y: auto;
        `;

        modal.innerHTML = `
            <div class="admin-modal-content" style="max-width: 600px; width: 100%; background: white; border-radius: 15px; max-height: 90vh; overflow-y: auto; animation: modalSlideIn 0.3s ease;">
                <div class="modal-header" style="padding: 25px; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; background: white; z-index: 10; border-radius: 15px 15px 0 0;">
                    <h3 style="font-size: 20px; margin: 0; color: var(--admin-text);">${title}</h3>
                    <button class="modal-close" style="width: 35px; height: 35px; border-radius: 8px; border: none; background: var(--admin-bg); font-size: 24px; cursor: pointer; transition: all 0.2s; color: var(--admin-text); display: flex; align-items: center; justify-content: center;">&times;</button>
                </div>
                <div class="modal-body" style="padding: 25px; color: var(--admin-text);">
                    ${content}
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        if (document.body.classList.contains('dark-mode')) {
            modal.querySelector('.admin-modal-content').style.background = 'var(--admin-card)';
            modal.querySelector('.modal-header').style.background = 'var(--admin-card)';
        }

        const closeBtn = modal.querySelector('.modal-close');
        closeBtn.addEventListener('click', () => modal.remove());

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });

        closeBtn.addEventListener('mouseenter', () => {
            closeBtn.style.background = 'var(--admin-danger)';
            closeBtn.style.color = 'white';
        });
        closeBtn.addEventListener('mouseleave', () => {
            closeBtn.style.background = 'var(--admin-bg)';
            closeBtn.style.color = 'var(--admin-text)';
        });

        return modal;
    }

    showFormModal(title, fields, onSubmit) {
        const formId = 'dynamicForm_' + Date.now();

        const formHTML = `
            <form id="${formId}" class="dynamic-form">
                ${fields.map(field => field.render()).join('')}
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--admin-border);">
                    <button type="button" class="btn btn-outline cancel-btn">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        `;

        const modal = this.createModal(title, formHTML);
        const form = document.getElementById(formId);

        form.querySelector('.cancel-btn').addEventListener('click', () => {
            modal.remove();
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const formData = new FormData(form);
            const data = {};
            const validator = new Validator();
            let isValid = true;

            fields.forEach(field => {
                let value = formData.get(field.name);

                if (field.type === 'checkbox') {
                    value = form.querySelector(`[name="${field.name}"]`)?.checked || false;
                } else if (field.type === 'number') {
                    value = value ? parseFloat(value) : 0;
                }

                data[field.name] = value;

                const validation = field.validate(value);
                if (!validation.isValid) {
                    isValid = false;
                    Object.assign(validator.errors, validation.errors);
                }
            });

            if (!isValid) {
                validator.showErrors(form);
                this.showNotification('Исправьте ошибки в форме', 'error');
                return;
            }

            onSubmit(data);
            modal.remove();
        });
    }

    closeAllModals() {
        document.querySelectorAll('.custom-modal').forEach(modal => {
            modal.remove();
        });
    }

    // ========================================
    // NOTIFICATIONS TOAST
    // ========================================

    showNotification(message, type = 'info') {
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#6366f1'
        };

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const notification = document.createElement('div');
        notification.className = 'admin-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 18px 25px;
            background: ${colors[type]};
            color: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 100000;
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 400px;
            animation: slideInRight 0.3s ease;
            font-weight: 500;
        `;

        notification.innerHTML = `
            <i class="fas ${icons[type]}" style="font-size: 20px;"></i>
            <span>${message}</span>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }

    // ========================================
    // UTILITY METHODS
    // ========================================

    getStatusName(status) {
        const names = {
            'new': 'Новый',
            'processing': 'В работе',
            'completed': 'Выполнен',
            'cancelled': 'Отменён'
        };
        return names[status] || status;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('ru-RU', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    getRelativeTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'только что';
        if (diffMins < 60) return `${diffMins} мин назад`;
        if (diffHours < 24) return `${diffHours} ч назад`;
        if (diffDays < 7) return `${diffDays} дн назад`;

        return this.formatDate(dateString);
    }
}

// ========================================
// GLOBAL INSTANCE & INITIALIZATION
// ========================================

const admin = new AdminPanel();

document.addEventListener('DOMContentLoaded', () => {
    admin.init();
});

function logout() {
    admin.logout();
}