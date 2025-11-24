// ========================================
// SYNC CLIENT - SSE-based content synchronization
// ========================================

class SyncClient {
    constructor(options = {}) {
        this.baseUrl = options.baseUrl || '/api/updates';
        this.eventSource = null;
        this.reconnectDelay = options.reconnectDelay || 3000;
        this.maxReconnectDelay = options.maxReconnectDelay || 30000;
        this.reconnectAttempts = 0;
        this.connected = false;
        this.listeners = {
            connected: [],
            disconnected: [],
            invalidate: [],
            error: []
        };
        this.lastEventId = null;
        this.connectionStartTime = null;
        
        // Auto-connect if enabled
        if (options.autoConnect !== false) {
            this.connect();
        }
    }

    connect() {
        if (this.eventSource) {
            console.log('⚠️ SSE already connected');
            return;
        }

        const since = this.lastEventId || Math.floor(Date.now() / 1000);
        const url = `${this.baseUrl}/stream.php?since=${since}`;
        
        console.log('🔄 Connecting to SSE stream:', url);
        this.connectionStartTime = Date.now();

        try {
            this.eventSource = new EventSource(url);

            this.eventSource.addEventListener('init', (event) => {
                const data = JSON.parse(event.data);
                console.log('✅ SSE connected:', data);
                this.connected = true;
                this.reconnectAttempts = 0;
                this.emit('connected', data);
            });

            this.eventSource.addEventListener('invalidate', (event) => {
                const data = JSON.parse(event.data);
                console.log('🔄 Cache invalidation:', data);
                this.lastEventId = event.lastEventId || data.timestamp;
                this.emit('invalidate', data);
            });

            this.eventSource.addEventListener('heartbeat', (event) => {
                const data = JSON.parse(event.data);
                console.log('💓 SSE heartbeat:', data.timestamp);
            });

            this.eventSource.addEventListener('timeout', (event) => {
                const data = JSON.parse(event.data);
                console.log('⏰ SSE timeout:', data.message);
                this.disconnect();
                setTimeout(() => this.connect(), 1000);
            });

            this.eventSource.onerror = (error) => {
                console.error('❌ SSE error:', error);
                this.connected = false;
                this.emit('error', error);
                this.emit('disconnected', { reason: 'error' });
                this.handleReconnect();
            };

        } catch (error) {
            console.error('❌ Failed to create SSE connection:', error);
            this.emit('error', error);
            this.handleReconnect();
        }
    }

    disconnect() {
        if (this.eventSource) {
            console.log('🔌 Disconnecting SSE');
            this.eventSource.close();
            this.eventSource = null;
            this.connected = false;
            this.emit('disconnected', { reason: 'manual' });
        }
    }

    handleReconnect() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }

        this.reconnectAttempts++;
        const delay = Math.min(
            this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1),
            this.maxReconnectDelay
        );

        console.log(`⏳ Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts})...`);
        setTimeout(() => this.connect(), delay);
    }

    on(event, callback) {
        if (this.listeners[event]) {
            this.listeners[event].push(callback);
        }
    }

    off(event, callback) {
        if (this.listeners[event]) {
            this.listeners[event] = this.listeners[event].filter(cb => cb !== callback);
        }
    }

    emit(event, data) {
        if (this.listeners[event]) {
            this.listeners[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`❌ Error in ${event} listener:`, error);
                }
            });
        }
    }

    isConnected() {
        return this.connected && this.eventSource && this.eventSource.readyState === EventSource.OPEN;
    }

    getStatus() {
        return {
            connected: this.isConnected(),
            reconnectAttempts: this.reconnectAttempts,
            lastEventId: this.lastEventId,
            uptime: this.connectionStartTime ? Date.now() - this.connectionStartTime : 0
        };
    }
}

// Polyfill for environments without EventSource support
if (typeof window !== 'undefined' && !window.EventSource) {
    console.warn('⚠️ EventSource not supported, SSE disabled');
    
    // Create a dummy SyncClient that does nothing
    window.SyncClient = class {
        constructor() {
            console.warn('⚠️ SSE not available in this browser');
        }
        connect() {}
        disconnect() {}
        on() {}
        off() {}
        emit() {}
        isConnected() { return false; }
        getStatus() { return { connected: false }; }
    };
} else if (typeof window !== 'undefined') {
    window.SyncClient = SyncClient;
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = SyncClient;
}
