// ========================================
// TELEGRAM BOT INTEGRATION
// ========================================

class TelegramBot {
    constructor() {
        this.botToken = CONFIG.telegram.botToken;
        this.chatId = CONFIG.telegram.chatId;
        this.apiUrl = `${CONFIG.telegram.apiUrl}${this.botToken}`;
    }

    async getChatId() {
        // Получаем chat_id из настроек базы данных
        try {
            if (typeof db !== 'undefined' && db.getOrCreateSettings) {
                const settings = await db.getOrCreateSettings();
                return settings?.telegram_chat_id || CONFIG.telegram.chatId;
            }
        } catch (error) {
            console.warn('⚠️ Failed to get chat_id from database:', error);
        }
        return CONFIG.telegram.chatId;
    }

    async sendMessage(text, options = {}) {
        // Обновляем chatId перед отправкой
        this.chatId = await this.getChatId();
        
        if (!this.chatId) {
            console.warn('Telegram Chat ID не настроен');
            return { success: false, error: 'Chat ID not configured' };
        }

        const url = `${this.apiUrl}/sendMessage`;
        
        const data = {
            chat_id: this.chatId,
            text: text,
            parse_mode: options.parseMode || 'HTML',
            disable_web_page_preview: true
        };

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            
            if (result.ok) {
                return { success: true, data: result };
            } else {
                console.error('Telegram API Error:', result);
                return { success: false, error: result.description };
            }
        } catch (error) {
            console.error('Telegram Send Error:', error);
            return { success: false, error: error.message };
        }
    }

    formatOrderMessage(order) {
    let message = `🔔 <b>НОВЫЙ ЗАКАЗ #${order.orderNumber || 'N/A'}</b>\n\n`;
    message += `👤 <b>Клиент:</b> ${order.clientName}\n`;
    message += `📧 <b>Email:</b> ${order.clientEmail}\n`;
    message += `📱 <b>Телефон:</b> ${order.clientPhone}\n`;
    
    if (order.telegram) {
        message += `💬 <b>Telegram:</b> ${order.telegram}\n`;
    }
    
    message += `\n🛠 <b>Услуга:</b> ${order.service}\n`;
    message += `💰 <b>Сумма:</b> ${order.amount.toLocaleString('ru-RU')} ₽\n\n`;
    
    if (order.calculatorData) {
        const calc = order.calculatorData;
        message += `📊 <b>Детали расчета:</b>\n`;
        message += `• Технология: ${calc.technology?.toUpperCase() || '-'}\n`;
        message += `• Материал: ${calc.material || '-'}\n`;
        message += `• Вес: ${calc.weight || 0}г\n`;
        message += `• Количество: ${calc.quantity || 1} шт\n`;
        message += `• Заполнение: ${calc.infill || 0}%\n`;
        message += `• Качество: ${calc.quality || '-'}\n`;
        message += `• Срок: ${calc.timeEstimate || '-'}\n\n`;
    }
    
    if (order.details) {
        message += `💬 <b>Комментарий:</b>\n${order.details}\n\n`;
    }
    
    message += `⏰ <b>Дата:</b> ${new Date().toLocaleString('ru-RU')}\n`;
    message += `🌐 <b>Сайт:</b> ${CONFIG.siteUrl}`;
    
    return message;
}

formatContactMessage(contact) {
    let message = `📧 <b>НОВОЕ ОБРАЩЕНИЕ</b>\n\n`;
    message += `👤 <b>Имя:</b> ${contact.name}\n`;
    message += `📧 <b>Email:</b> ${contact.email}\n`;
    message += `📱 <b>Телефон:</b> ${contact.phone}\n`;
    
    if (contact.telegram) {
        message += `💬 <b>Telegram:</b> ${contact.telegram}\n`;
    }
    
    if (contact.subject) {
        message += `📋 <b>Тема:</b> ${contact.subject}\n`;
    }
    
    message += `\n💬 <b>Сообщение:</b>\n${contact.message}\n\n`;
    message += `⏰ <b>Дата:</b> ${new Date().toLocaleString('ru-RU')}`;
    
    return message;
}

    async sendOrderNotification(order) {
        if (!CONFIG.features.telegramNotifications) {
            return { success: false, error: 'Notifications disabled' };
        }

        const message = this.formatOrderMessage(order);
        return await this.sendMessage(message);
    }

    async sendContactNotification(contact) {
        if (!CONFIG.features.telegramNotifications) {
            return { success: false, error: 'Notifications disabled' };
        }

        const message = this.formatContactMessage(contact);
        return await this.sendMessage(message);
    }

    async getUpdates() {
        const url = `${this.apiUrl}/getUpdates`;
        
        try {
            const response = await fetch(url);
            const result = await response.json();
            
            if (result.ok && result.result.length > 0) {
                const lastUpdate = result.result[result.result.length - 1];
                const chatId = lastUpdate.message?.chat?.id || lastUpdate.callback_query?.message?.chat?.id;
                
                if (chatId) {
                    return {
                        success: true,
                        chatId: chatId.toString()
                    };
                }
            }
            
            return { success: false, error: 'No updates found. Please send a message to the bot first.' };
        } catch (error) {
            return { success: false, error: error.message };
        }
    }

    async sendTestMessage() {
        const message = `✅ <b>Тестовое сообщение</b>\n\nСвязь с Telegram ботом установлена!\n\n⏰ ${new Date().toLocaleString('ru-RU')}`;
        return await this.sendMessage(message);
    }

    async setWebhook(url) {
        const webhookUrl = `${this.apiUrl}/setWebhook`;
        
        try {
            const response = await fetch(webhookUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ url })
            });

            const result = await response.json();
            return { success: result.ok, data: result };
        } catch (error) {
            return { success: false, error: error.message };
        }
    }

    async deleteWebhook() {
        const url = `${this.apiUrl}/deleteWebhook`;
        
        try {
            const response = await fetch(url);
            const result = await response.json();
            return { success: result.ok, data: result };
        } catch (error) {
            return { success: false, error: error.message };
        }
    }
}

// Create global instance
const telegramBot = new TelegramBot();
