# PHP Backend Setup Guide

## Обзор

Этот проект теперь использует PHP backend с MySQL базой данных для хранения заявок. Это решает проблемы с localStorage (не работает в инкогнито, не синхронизируется между пользователями).

## Архитектура

```
Клиент (браузер)
    ↓ fetch()
PHP API (api/submit-form.php)
    ↓ PDO
MySQL База данных (ch167436_3dprint)
    ↓
Telegram Bot API
```

## Файлы

### Backend
- `api/config.php` - Конфигурация БД и Telegram (НЕ коммитится в git)
- `api/config.example.php` - Пример конфигурации
- `api/submit-form.php` - API endpoint для отправки форм
- `api/get-orders.php` - API endpoint для получения заявок
- `api/.htaccess` - Защита конфигурации и CORS

### Database
- `database/schema.sql` - SQL схема для создания таблиц

### Frontend
- `js/main.js` - Обновлен метод `handleUniversalForm()` для использования PHP API

## Установка на хостинге

### Шаг 1: Создание базы данных

1. Откройте **PHPMyAdmin** на вашем хостинге
2. Выберите базу данных `ch167436_3dprint` (или создайте новую)
3. Перейдите в раздел **SQL**
4. Скопируйте содержимое файла `database/schema.sql`
5. Вставьте в окно SQL и нажмите **Выполнить**

Это создаст таблицы:
- `orders` - для хранения заказов и обращений
- `settings` - для хранения настроек (Chat ID и т.д.)

### Шаг 2: Настройка конфигурации

1. Откройте файл `api/config.php` на хостинге
2. Обновите данные подключения к БД:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ch167436_3dprint');
define('DB_USER', 'ВАШ_ПОЛЬЗОВАТЕЛЬ_БД');
define('DB_PASS', 'ВАШ_ПАРОЛЬ_БД');
```

3. Убедитесь, что `TELEGRAM_BOT_TOKEN` указан правильно
4. `TELEGRAM_CHAT_ID` можно оставить пустым - он заполнится из админ панели

### Шаг 3: Настройка прав доступа

Убедитесь, что файлы имеют правильные права:

```bash
chmod 644 api/submit-form.php
chmod 644 api/get-orders.php
chmod 600 api/config.php  # Только для чтения владельцем
```

### Шаг 4: Настройка Telegram

1. Откройте админ панель: `https://ваш-домен.ru/admin.html`
2. Войдите (admin / admin123) и **СМЕНИТЕ ПАРОЛЬ**
3. Перейдите в **Settings → Telegram**
4. Введите **Chat ID** (см. [TELEGRAM_SETUP_GUIDE.md](TELEGRAM_SETUP_GUIDE.md))
5. Нажмите **Save**
6. Нажмите **Send Test Message** для проверки

### Шаг 5: Тестирование

1. Откройте сайт в **обычном режиме**
2. Заполните форму контактов или калькулятор
3. Отправьте форму
4. Проверьте:
   - ✅ Появилось сообщение об успехе
   - ✅ Сообщение пришло в Telegram
   - ✅ Заявка сохранена в PHPMyAdmin (таблица `orders`)

5. Откройте сайт в **режиме инкогнито**
6. Заполните и отправьте форму
7. Проверьте:
   - ✅ Форма отправляется успешно
   - ✅ Заявка в БД
   - ✅ Сообщение в Telegram

8. Откройте сайт в **другом браузере**
9. Повторите тест

## API Endpoints

### POST /api/submit-form.php

Отправка формы (заказ или обращение).

**Request:**
```json
{
  "name": "Иван Петров",
  "email": "ivan@example.com",
  "phone": "+7 (999) 123-45-67",
  "telegram": "@ivan",
  "subject": "Консультация",
  "message": "Хочу заказать 3D печать",
  "service": "FDM печать (PLA)",
  "amount": 1500,
  "calculatorData": {
    "technology": "fdm",
    "material": "PLA",
    "weight": 100,
    "quantity": 1,
    "total": 1500
  },
  "orderNumber": "ORD-20250110-ABC123"
}
```

**Response (Success):**
```json
{
  "success": true,
  "order_id": 42,
  "order_number": "ORD-20250110-ABC123",
  "telegram_sent": true,
  "message": "Form submitted successfully"
}
```

**Response (Error):**
```json
{
  "success": false,
  "error": "Name is required"
}
```

### GET /api/get-orders.php

Получение списка заявок (для админ панели).

**Query Parameters:**
- `limit` - количество записей (по умолчанию 100)
- `offset` - смещение (по умолчанию 0)
- `status` - фильтр по статусу (new, processing, completed, cancelled)

**Example:**
```
GET /api/get-orders.php?limit=50&offset=0&status=new
```

**Response:**
```json
{
  "success": true,
  "orders": [
    {
      "id": 42,
      "order_number": "ORD-20250110-ABC123",
      "type": "order",
      "name": "Иван Петров",
      "email": "ivan@example.com",
      "phone": "+7 (999) 123-45-67",
      "telegram": "@ivan",
      "service": "FDM печать (PLA)",
      "amount": 1500,
      "status": "new",
      "telegram_sent": true,
      "created_at": "2025-01-10 14:30:00"
    }
  ],
  "total": 1,
  "limit": 50,
  "offset": 0
}
```

## Безопасность

### ✅ Что реализовано:

1. **Защита конфигурации**
   - `api/config.php` защищен через `.htaccess`
   - Файл не коммитится в git (`.gitignore`)

2. **SQL Injection защита**
   - Используются PDO prepared statements
   - Все параметры привязываются через `bindValue()`

3. **XSS защита**
   - `htmlspecialchars()` для всех пользовательских данных в Telegram сообщениях
   - JSON encoding для безопасной передачи данных

4. **CORS**
   - Настроен через `.htaccess` для API endpoints
   - Разрешены только POST/GET методы

5. **Error Handling**
   - Ошибки не показываются пользователю в production
   - Логируются на стороне сервера
   - Пользователю возвращаются только безопасные сообщения

### ⚠️ Рекомендации для production:

1. **HTTPS обязательно!**
   - Все данные должны передаваться через HTTPS
   - Настройте SSL сертификат на хостинге

2. **Смените пароль администратора**
   - По умолчанию: admin / admin123
   - Смените сразу после первого входа!

3. **Ограничьте доступ к admin.html**
   - Можно добавить `.htpasswd` защиту
   - Или настроить IP whitelist

4. **Регулярные бэкапы БД**
   - Настройте автоматические бэкапы в PHPMyAdmin
   - Или через cron job

5. **Мониторинг**
   - Проверяйте логи ошибок PHP
   - Мониторьте количество заявок

## Устранение неполадок

### Проблема: "Database connection failed"

**Решение:**
1. Проверьте `api/config.php` - правильные ли данные БД
2. Проверьте, что пользователь БД имеет права на базу
3. В PHPMyAdmin: Привилегии → Добавить пользователя

### Проблема: "Chat ID not configured"

**Решение:**
1. Откройте админ панель
2. Settings → Telegram
3. Введите Chat ID
4. Нажмите Save

См. также: [TELEGRAM_SETUP_GUIDE.md](TELEGRAM_SETUP_GUIDE.md)

### Проблема: "CORS error"

**Решение:**
1. Убедитесь, что файл `api/.htaccess` загружен на сервер
2. Проверьте, что `mod_headers` включен на хостинге
3. Если не работает - добавьте headers в PHP:
```php
header('Access-Control-Allow-Origin: *');
```

### Проблема: Форма отправляется, но данных нет в БД

**Решение:**
1. Откройте консоль браузера (F12)
2. Проверьте ответ от `api/submit-form.php`
3. Если ошибка - скопируйте и исправьте
4. Проверьте права доступа к файлам PHP

### Проблема: В инкогнито работает, в обычном режиме - нет

**Решение:**
1. Очистите кэш браузера (Ctrl+Shift+Delete)
2. Очистите localStorage: Console → `localStorage.clear()`
3. Перезагрузите страницу (Ctrl+Shift+R)

## Миграция данных

Если у вас были заявки в localStorage, можно их экспортировать:

1. Откройте консоль (F12)
2. Выполните:
```javascript
const orders = JSON.parse(localStorage.getItem('orders') || '[]');
console.log(JSON.stringify(orders, null, 2));
```
3. Скопируйте JSON
4. Импортируйте в БД через PHPMyAdmin или PHP скрипт

## Дальнейшее развитие

### Возможные улучшения:

1. **Email уведомления**
   - Добавить отправку на email владельца
   - PHPMailer для надежной отправки

2. **SMS уведомления**
   - Интеграция с SMS.ru или другим провайдером

3. **Статистика**
   - Дашборд с графиками
   - Конверсия, источники трафика

4. **Экспорт данных**
   - Экспорт заявок в Excel/CSV
   - Интеграция с CRM

5. **Автоматизация**
   - Автоответы клиентам
   - Напоминания менеджерам

6. **API для мобильного приложения**
   - REST API с JWT аутентификацией

## Поддержка

При возникновении проблем:

1. Проверьте консоль браузера (F12)
2. Проверьте логи PHP на хостинге
3. Проверьте PHPMyAdmin - есть ли данные в таблице
4. Проверьте Telegram - приходят ли сообщения

Все заявки сохраняются в БД **даже если Telegram не работает**.
