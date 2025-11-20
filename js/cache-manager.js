// ========================================
// CACHE MANAGER - IndexedDB with TTL & ETag support
// ========================================

class CacheManager {
    constructor(dbName = '3dprintpro_cache', version = 1) {
        this.dbName = dbName;
        this.version = version;
        this.db = null;
        this.storeName = 'content_cache';
        this.defaultTTL = 300000; // 5 minutes
        this.initPromise = this.init();
    }

    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);

            request.onerror = () => {
                console.error('❌ Failed to open IndexedDB:', request.error);
                reject(request.error);
            };

            request.onsuccess = () => {
                this.db = request.result;
                console.log('✅ IndexedDB cache initialized');
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                if (!db.objectStoreNames.contains(this.storeName)) {
                    const objectStore = db.createObjectStore(this.storeName, { keyPath: 'key' });
                    objectStore.createIndex('resource', 'resource', { unique: false });
                    objectStore.createIndex('timestamp', 'timestamp', { unique: false });
                    objectStore.createIndex('expiresAt', 'expiresAt', { unique: false });
                    console.log('✅ IndexedDB object store created');
                }
            };
        });
    }

    async ensureDB() {
        if (!this.db) {
            await this.initPromise;
        }
        return this.db;
    }

    async get(key) {
        try {
            const db = await this.ensureDB();
            const transaction = db.transaction([this.storeName], 'readonly');
            const objectStore = transaction.objectStore(this.storeName);
            const request = objectStore.get(key);

            return new Promise((resolve, reject) => {
                request.onsuccess = () => {
                    const record = request.result;
                    
                    if (!record) {
                        console.log(`🔍 Cache miss: ${key}`);
                        resolve(null);
                        return;
                    }

                    const now = Date.now();
                    if (record.expiresAt && record.expiresAt < now) {
                        console.log(`⏰ Cache expired: ${key}`);
                        this.delete(key);
                        resolve(null);
                        return;
                    }

                    console.log(`✅ Cache hit: ${key}`);
                    resolve(record);
                };

                request.onerror = () => {
                    console.error('❌ Cache read error:', request.error);
                    reject(request.error);
                };
            });
        } catch (error) {
            console.error('❌ Cache get error:', error);
            return null;
        }
    }

    async set(key, data, options = {}) {
        try {
            const db = await this.ensureDB();
            const now = Date.now();
            
            const record = {
                key,
                resource: options.resource || key.split(':')[0],
                data,
                etag: options.etag || null,
                lastModified: options.lastModified || null,
                timestamp: now,
                expiresAt: options.ttl ? now + options.ttl : now + this.defaultTTL,
                metadata: options.metadata || {}
            };

            const transaction = db.transaction([this.storeName], 'readwrite');
            const objectStore = transaction.objectStore(this.storeName);
            const request = objectStore.put(record);

            return new Promise((resolve, reject) => {
                request.onsuccess = () => {
                    console.log(`✅ Cache set: ${key}`, { expiresIn: record.expiresAt - now });
                    resolve(record);
                };

                request.onerror = () => {
                    console.error('❌ Cache write error:', request.error);
                    reject(request.error);
                };
            });
        } catch (error) {
            console.error('❌ Cache set error:', error);
            return null;
        }
    }

    async delete(key) {
        try {
            const db = await this.ensureDB();
            const transaction = db.transaction([this.storeName], 'readwrite');
            const objectStore = transaction.objectStore(this.storeName);
            const request = objectStore.delete(key);

            return new Promise((resolve, reject) => {
                request.onsuccess = () => {
                    console.log(`🗑️ Cache deleted: ${key}`);
                    resolve(true);
                };

                request.onerror = () => {
                    console.error('❌ Cache delete error:', request.error);
                    reject(request.error);
                };
            });
        } catch (error) {
            console.error('❌ Cache delete error:', error);
            return false;
        }
    }

    async invalidateResource(resource) {
        try {
            const db = await this.ensureDB();
            const transaction = db.transaction([this.storeName], 'readwrite');
            const objectStore = transaction.objectStore(this.storeName);
            const index = objectStore.index('resource');
            const request = index.openCursor(IDBKeyRange.only(resource));

            return new Promise((resolve, reject) => {
                const deletedKeys = [];
                
                request.onsuccess = (event) => {
                    const cursor = event.target.result;
                    if (cursor) {
                        deletedKeys.push(cursor.value.key);
                        cursor.delete();
                        cursor.continue();
                    } else {
                        console.log(`🗑️ Invalidated ${deletedKeys.length} cache entries for resource: ${resource}`);
                        resolve(deletedKeys);
                    }
                };

                request.onerror = () => {
                    console.error('❌ Cache invalidation error:', request.error);
                    reject(request.error);
                };
            });
        } catch (error) {
            console.error('❌ Cache invalidation error:', error);
            return [];
        }
    }

    async clearExpired() {
        try {
            const db = await this.ensureDB();
            const transaction = db.transaction([this.storeName], 'readwrite');
            const objectStore = transaction.objectStore(this.storeName);
            const index = objectStore.index('expiresAt');
            const now = Date.now();
            const request = index.openCursor(IDBKeyRange.upperBound(now));

            return new Promise((resolve, reject) => {
                let deletedCount = 0;
                
                request.onsuccess = (event) => {
                    const cursor = event.target.result;
                    if (cursor) {
                        cursor.delete();
                        deletedCount++;
                        cursor.continue();
                    } else {
                        if (deletedCount > 0) {
                            console.log(`🧹 Cleared ${deletedCount} expired cache entries`);
                        }
                        resolve(deletedCount);
                    }
                };

                request.onerror = () => {
                    console.error('❌ Cache cleanup error:', request.error);
                    reject(request.error);
                };
            });
        } catch (error) {
            console.error('❌ Cache cleanup error:', error);
            return 0;
        }
    }

    async clearAll() {
        try {
            const db = await this.ensureDB();
            const transaction = db.transaction([this.storeName], 'readwrite');
            const objectStore = transaction.objectStore(this.storeName);
            const request = objectStore.clear();

            return new Promise((resolve, reject) => {
                request.onsuccess = () => {
                    console.log('🧹 All cache cleared');
                    resolve(true);
                };

                request.onerror = () => {
                    console.error('❌ Cache clear error:', request.error);
                    reject(request.error);
                };
            });
        } catch (error) {
            console.error('❌ Cache clear error:', error);
            return false;
        }
    }

    async getStats() {
        try {
            const db = await this.ensureDB();
            const transaction = db.transaction([this.storeName], 'readonly');
            const objectStore = transaction.objectStore(this.storeName);
            const countRequest = objectStore.count();
            const getAllRequest = objectStore.getAll();

            return new Promise((resolve, reject) => {
                let totalCount = 0;
                let allRecords = [];

                countRequest.onsuccess = () => {
                    totalCount = countRequest.result;
                };

                getAllRequest.onsuccess = () => {
                    allRecords = getAllRequest.result;
                    
                    const now = Date.now();
                    const expiredCount = allRecords.filter(r => r.expiresAt && r.expiresAt < now).length;
                    const byResource = {};
                    
                    allRecords.forEach(record => {
                        if (!byResource[record.resource]) {
                            byResource[record.resource] = 0;
                        }
                        byResource[record.resource]++;
                    });

                    resolve({
                        total: totalCount,
                        expired: expiredCount,
                        valid: totalCount - expiredCount,
                        byResource
                    });
                };

                getAllRequest.onerror = countRequest.onerror = () => {
                    console.error('❌ Cache stats error');
                    reject(new Error('Failed to get cache stats'));
                };
            });
        } catch (error) {
            console.error('❌ Cache stats error:', error);
            return { total: 0, expired: 0, valid: 0, byResource: {} };
        }
    }
}

// Auto cleanup expired entries on page load
if (typeof window !== 'undefined') {
    window.addEventListener('load', async () => {
        try {
            const cacheManager = new CacheManager();
            await cacheManager.clearExpired();
        } catch (error) {
            console.error('❌ Auto cleanup failed:', error);
        }
    });
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = CacheManager;
}
