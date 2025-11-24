// ========================================
// SHARED UTILITIES
// ========================================

class Utils {
    // ========================================
    // PHONE FORMATTING
    // ========================================
    
    static formatPhone(input) {
        if (typeof input === 'string') {
            let value = input.replace(/\D/g, '');
            
            if (value.length > 0 && value[0] === '8') {
                value = '7' + value.slice(1);
            }
            
            if (value.length > 0 && value[0] !== '7') {
                value = '7' + value;
            }
            
            let formatted = '';
            if (value.length > 0) {
                formatted = '+7';
                if (value.length > 1) {
                    formatted += ' (' + value.slice(1, 4);
                }
                if (value.length >= 5) {
                    formatted += ') ' + value.slice(4, 7);
                }
                if (value.length >= 8) {
                    formatted += '-' + value.slice(7, 9);
                }
                if (value.length >= 10) {
                    formatted += '-' + value.slice(9, 11);
                }
            }
            
            return formatted;
        } else if (input && input.target) {
            const element = input.target || input;
            let value = element.value.replace(/\D/g, '');
            
            if (value.length > 0 && value[0] === '8') {
                value = '7' + value.slice(1);
            }
            
            if (value.length > 0 && value[0] !== '7') {
                value = '7' + value;
            }
            
            let formatted = '';
            if (value.length > 0) {
                formatted = '+7';
                if (value.length > 1) {
                    formatted += ' (' + value.slice(1, 4);
                }
                if (value.length >= 5) {
                    formatted += ') ' + value.slice(4, 7);
                }
                if (value.length >= 8) {
                    formatted += '-' + value.slice(7, 9);
                }
                if (value.length >= 10) {
                    formatted += '-' + value.slice(9, 11);
                }
            }
            
            element.value = formatted;
            return formatted;
        }
        
        return '';
    }
    
    static initPhoneMask(input) {
        input.addEventListener('input', (e) => Utils.formatPhone(e.target));
        input.addEventListener('focus', (e) => {
            if (!e.target.value) {
                e.target.value = '+7 ';
            }
        });
        input.addEventListener('blur', (e) => {
            if (e.target.value === '+7 ') {
                e.target.value = '';
            }
        });
    }
    
    // ========================================
    // NOTIFICATIONS
    // ========================================
    
    static showNotification(message, type = 'info', duration = 5000) {
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
        notification.className = 'notification';
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 20px 30px;
            background: ${colors[type]};
            color: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: slideInRight 0.3s ease;
            max-width: 400px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        `;
        
        notification.innerHTML = `
            <i class="fas ${icons[type]}" style="font-size: 20px;"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, duration);
    }
    
    // ========================================
    // NUMBER FORMATTING
    // ========================================
    
    static formatNumber(num, decimals = 0) {
        if (!Number.isFinite(num)) {
            return '0';
        }
        
        return num.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }
    
    static formatCurrency(amount, currency = '₽') {
        if (!Number.isFinite(amount)) {
            return `0${currency}`;
        }
        
        return `${Utils.formatNumber(amount)}${currency}`;
    }
    
    // ========================================
    // DATE FORMATTING
    // ========================================
    
    static formatDate(date, format = 'DD.MM.YYYY') {
        const d = new Date(date);
        if (isNaN(d.getTime())) {
            return '';
        }
        
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        
        return format
            .replace('DD', day)
            .replace('MM', month)
            .replace('YYYY', year)
            .replace('HH', hours)
            .replace('mm', minutes);
    }
    
    static formatRelativeTime(timestamp) {
        const now = Date.now();
        const diff = now - timestamp;
        
        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);
        
        if (seconds < 60) {
            return 'только что';
        } else if (minutes < 60) {
            return `${minutes} мин. назад`;
        } else if (hours < 24) {
            return `${hours} ч. назад`;
        } else if (days < 7) {
            return `${days} дн. назад`;
        } else {
            return Utils.formatDate(timestamp);
        }
    }
    
    // ========================================
    // STRING UTILITIES
    // ========================================
    
    static truncate(str, maxLength, suffix = '...') {
        if (!str || str.length <= maxLength) {
            return str;
        }
        
        return str.substring(0, maxLength - suffix.length) + suffix;
    }
    
    static escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    static slugify(str) {
        return str
            .toString()
            .toLowerCase()
            .trim()
            .replace(/\s+/g, '-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-');
    }
    
    // ========================================
    // ORDER NUMBER GENERATION
    // ========================================
    
    static async generateOrderNumber() {
        try {
            if (typeof db !== 'undefined') {
                const orders = await db.getOrders();
                const maxNumber = orders.reduce((max, o) => {
                    const num = parseInt(o.order_number || o.orderNumber) || 0;
                    return num > max ? num : max;
                }, 1000);
                return (maxNumber + 1).toString();
            }
        } catch (error) {
            console.error('❌ Failed to generate order number:', error);
        }
        
        return Date.now().toString();
    }
    
    // ========================================
    // SCROLL UTILITIES
    // ========================================
    
    static scrollTo(target, offset = 80) {
        const element = typeof target === 'string' 
            ? document.querySelector(target) 
            : target;
        
        if (!element) return;
        
        window.scrollTo({
            top: element.offsetTop - offset,
            behavior: 'smooth'
        });
    }
    
    static scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
    
    // ========================================
    // DEBOUNCE & THROTTLE
    // ========================================
    
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    static throttle(func, limit) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    // ========================================
    // COOKIE UTILITIES
    // ========================================
    
    static setCookie(name, value, days = 365) {
        const expires = new Date();
        expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
        document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
    }
    
    static getCookie(name) {
        const nameEQ = name + '=';
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
    
    static deleteCookie(name) {
        document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;`;
    }
    
    // ========================================
    // LOCAL STORAGE UTILITIES
    // ========================================
    
    static setLocalStorage(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (error) {
            console.error('❌ LocalStorage write error:', error);
            return false;
        }
    }
    
    static getLocalStorage(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (error) {
            console.error('❌ LocalStorage read error:', error);
            return defaultValue;
        }
    }
    
    static removeLocalStorage(key) {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (error) {
            console.error('❌ LocalStorage remove error:', error);
            return false;
        }
    }
    
    // ========================================
    // VALIDATION HELPERS
    // ========================================
    
    static isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    }
    
    static isValidPhone(phone) {
        const cleaned = phone.replace(/\D/g, '');
        return cleaned.length === 11 && cleaned.startsWith('7');
    }
    
    static isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
}

if (typeof window !== 'undefined') {
    window.Utils = Utils;
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = Utils;
}
