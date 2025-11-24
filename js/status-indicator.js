// ========================================
// STATUS INDICATOR - Connectivity Status UI
// ========================================

class StatusIndicator {
    constructor() {
        this.currentStatus = 'online';
        this.banner = null;
        this.showBanner = true;
        this.init();
    }
    
    init() {
        this.createBanner();
        this.attachListeners();
        this.checkStatus();
        
        setInterval(() => {
            this.checkStatus();
        }, 30000);
    }
    
    createBanner() {
        this.banner = document.createElement('div');
        this.banner.id = 'connectivity-banner';
        this.banner.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 12px 20px;
            background: #f59e0b;
            color: white;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            z-index: 10000;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        `;
        
        document.body.appendChild(this.banner);
    }
    
    attachListeners() {
        if (typeof apiClient !== 'undefined') {
            apiClient.on('online', () => {
                console.log('🌐 Status: ONLINE - API connection restored');
                this.updateStatus('online');
            });
            
            apiClient.on('offline', () => {
                console.log('🌐 Status: OFFLINE - Using cached data');
                this.updateStatus('offline');
            });
        }
        
        window.addEventListener('online', () => {
            console.log('🌐 Browser: Network connection detected');
            this.checkStatus();
        });
        
        window.addEventListener('offline', () => {
            console.log('🌐 Browser: Network connection lost');
            this.updateStatus('offline');
        });
    }
    
    async checkStatus() {
        if (typeof apiClient !== 'undefined') {
            const status = apiClient.getStatus();
            
            if (status.isOnline) {
                this.updateStatus('online');
            } else if (status.isStale) {
                this.updateStatus('stale');
            } else {
                this.updateStatus('cache');
            }
            
            this.logStatus(status);
        }
    }
    
    updateStatus(status) {
        if (this.currentStatus === status) return;
        
        this.currentStatus = status;
        
        switch (status) {
            case 'online':
                this.hideBanner();
                break;
                
            case 'offline':
                this.showBanner('⚠️ Нет соединения с сервером. Используются сохранённые данные.', '#ef4444');
                break;
                
            case 'cache':
                this.showBanner('📦 Используются кэшированные данные', '#f59e0b');
                break;
                
            case 'stale':
                this.showBanner('⚠️ Данные могут быть устаревшими. Проверьте соединение с интернетом.', '#ef4444');
                break;
        }
    }
    
    showBanner(message, color) {
        if (!this.showBanner) return;
        
        this.banner.innerHTML = `
            <span>${message}</span>
            <button onclick="statusIndicator.retry()" style="
                background: rgba(255,255,255,0.2);
                border: 1px solid rgba(255,255,255,0.3);
                color: white;
                padding: 4px 12px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
                font-weight: 600;
            ">Повторить</button>
            <button onclick="statusIndicator.dismiss()" style="
                background: transparent;
                border: none;
                color: white;
                cursor: pointer;
                font-size: 18px;
                padding: 0 8px;
                margin-left: 10px;
            ">×</button>
        `;
        this.banner.style.background = color;
        this.banner.style.transform = 'translateY(0)';
    }
    
    hideBanner() {
        this.banner.style.transform = 'translateY(-100%)';
    }
    
    dismiss() {
        this.hideBanner();
        setTimeout(() => {
            this.showBanner = true;
        }, 300000);
    }
    
    async retry() {
        console.log('🔄 Retrying connection...');
        
        if (typeof apiClient !== 'undefined') {
            try {
                await apiClient.checkConnectivity();
                
                if (apiClient.isOnline) {
                    this.updateStatus('online');
                    
                    if (typeof app !== 'undefined' && typeof app.showNotification === 'function') {
                        app.showNotification('✅ Соединение восстановлено', 'success');
                    }
                    
                    if (typeof app !== 'undefined' && typeof app.reloadData === 'function') {
                        app.reloadData();
                    }
                } else {
                    if (typeof app !== 'undefined' && typeof app.showNotification === 'function') {
                        app.showNotification('❌ Не удалось восстановить соединение', 'error');
                    }
                }
            } catch (error) {
                console.error('❌ Retry failed:', error);
            }
        }
    }
    
    logStatus(status) {
        if (typeof db !== 'undefined') {
            const syncInfo = db.getAllSyncInfo();
            
            console.log('📊 Database Status:', {
                apiOnline: status.isOnline,
                timeSinceLastSuccess: `${Math.floor(status.timeSinceLastSuccess / 1000)}s`,
                isStale: status.isStale,
                syncInfo
            });
        }
    }
    
    getSummary() {
        const status = typeof apiClient !== 'undefined' ? apiClient.getStatus() : null;
        const syncInfo = typeof db !== 'undefined' ? db.getAllSyncInfo() : null;
        
        return {
            currentStatus: this.currentStatus,
            api: status,
            database: syncInfo
        };
    }
}

const statusIndicator = new StatusIndicator();
console.log('✅ Status Indicator initialized');
