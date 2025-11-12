// ========================================
// Dashboard Module - Stats, Charts & Recent Activity
// ========================================

class DashboardModule {
    constructor() {
        this.chart = null;
        this.refreshInterval = null;
    }
    
    async init() {
        console.log('📊 Loading dashboard...');
        
        if (!window.adminApi) {
            console.warn('⚠️ adminApi not ready yet, retrying...');
            setTimeout(() => this.init(), 100);
            return;
        }
        
        await this.loadStats();
        await this.loadRecentOrders();
        await this.loadChart();
        this.startAutoRefresh();
    }
    
    async loadStats() {
        try {
            const orders = await window.adminApi.getOrders();
            const now = new Date();
            const currentMonth = now.getMonth();
            const currentYear = now.getFullYear();
            
            // Total orders
            const totalOrders = orders.length;
            document.getElementById('statTotalOrders').textContent = totalOrders;
            
            // Month revenue
            const monthRevenue = orders
                .filter(o => {
                    const orderDate = new Date(o.created_at);
                    return orderDate.getMonth() === currentMonth && 
                           orderDate.getFullYear() === currentYear;
                })
                .reduce((sum, o) => sum + (parseFloat(o.amount) || 0), 0);
            
            document.getElementById('statMonthRevenue').textContent = 
                AdminMain.prototype.formatMoney(monthRevenue);
            
            // Unique clients
            const uniqueClients = new Set(
                orders.map(o => o.email).filter(Boolean)
            ).size;
            document.getElementById('statTotalClients').textContent = uniqueClients;
            
            // Processing orders
            const processing = orders.filter(o => 
                o.status === 'new' || o.status === 'processing'
            ).length;
            document.getElementById('statProcessing').textContent = processing;
            
            console.log('✅ Dashboard stats loaded');
        } catch (error) {
            console.error('❌ Failed to load dashboard stats:', error);
            AdminMain.prototype.showToast('Ошибка загрузки статистики', 'error');
        }
    }
    
    async loadRecentOrders() {
        const container = document.getElementById('recentOrdersList');
        if (!container) return;
        
        try {
            AdminMain.prototype.showLoading(container);
            
            const orders = await window.adminApi.getOrders();
            const recent = orders
                .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
                .slice(0, 5);
            
            if (recent.length === 0) {
                AdminMain.prototype.showEmpty(container, 'Заказов пока нет');
                return;
            }
            
            container.innerHTML = recent.map(order => `
                <div class="recent-order-item" onclick="location.href='/admin/orders.php?id=${order.id}'">
                    <div class="order-info">
                        <div class="order-number">#${order.order_number || order.id.substring(0, 8)}</div>
                        <div class="order-client">${this.escapeHtml(order.name)}</div>
                        <div class="order-service">${this.escapeHtml(order.service || order.subject || '-')}</div>
                    </div>
                    <div class="order-meta">
                        <div class="order-amount">${AdminMain.prototype.formatMoney(order.amount)}</div>
                        ${AdminMain.prototype.getStatusBadge(order.status)}
                    </div>
                </div>
            `).join('');
            
            console.log('✅ Recent orders loaded');
        } catch (error) {
            console.error('❌ Failed to load recent orders:', error);
            AdminMain.prototype.showError(container);
        }
    }
    
    async loadChart() {
        const canvas = document.getElementById('ordersChart');
        if (!canvas) return;
        
        try {
            const orders = await window.adminApi.getOrders();
            const last7Days = this.getLast7Days();
            
            const data = last7Days.map(date => {
                return orders.filter(o => {
                    const orderDate = new Date(o.created_at);
                    return orderDate.toDateString() === date.toDateString();
                }).length;
            });
            
            const labels = last7Days.map(date => 
                date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' })
            );
            
            // Destroy existing chart
            if (this.chart) {
                this.chart.destroy();
            }
            
            // Create new chart
            this.chart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Заказы',
                        data: data,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
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
            
            console.log('✅ Orders chart loaded');
        } catch (error) {
            console.error('❌ Failed to load chart:', error);
        }
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
    
    startAutoRefresh() {
        // Refresh stats every 5 minutes
        this.refreshInterval = setInterval(() => {
            this.loadStats();
            AdminMain.prototype.checkOrdersBadge();
        }, 5 * 60 * 1000);
    }
    
    destroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        if (this.chart) {
            this.chart.destroy();
        }
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
        window.dashboardModule = new DashboardModule();
        window.dashboardModule.init();
    });
} else {
    window.dashboardModule = new DashboardModule();
    window.dashboardModule.init();
}
