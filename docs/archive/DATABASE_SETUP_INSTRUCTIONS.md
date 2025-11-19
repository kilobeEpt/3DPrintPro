# База Данных - Инструкция по Настройке и Запуску

## 📋 Описание

Полная инструкция по настройке и развертыванию базы данных MySQL для 3D Print Pro.

## 🔑 Данные для Доступа к БД

```
Database: ch167436_3dprint
Host: 3dprint-omsk.ru (production) или localhost (local)
User: ch167436_3dprint
Password: 852789456
Telegram Bot Token: 8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI
```

## 🗄️ Структура Базы Данных

База данных состоит из 7 таблиц:

1. **orders** - Заявки от клиентов (калькулятор + контактная форма)
2. **settings** - Настройки сайта (Telegram chat_id, company info, и т.д.)
3. **services** - Услуги (FDM печать, SLA, моделирование, и т.д.)
4. **portfolio** - Портфолио выполненных работ
5. **testimonials** - Отзывы клиентов
6. **faq** - Часто задаваемые вопросы
7. **content_blocks** - Текстовые блоки для страниц

## 🚀 Быстрый Старт

### Вариант A: Локальная Разработка

1. **Установка окружения:**
   ```bash
   # Убедитесь, что установлены Apache, PHP 7.4+, MySQL 8.0+
   # Windows: XAMPP / OpenServer
   # Mac: MAMP / Homebrew
   # Linux: apt-get install apache2 php mysql-server
   ```

2. **Создание БД:**
   ```sql
   CREATE DATABASE ch167436_3dprint CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'ch167436_3dprint'@'localhost' IDENTIFIED BY '852789456';
   GRANT ALL PRIVILEGES ON ch167436_3dprint.* TO 'ch167436_3dprint'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. **Импорт схемы:**
   ```bash
   mysql -u ch167436_3dprint -p852789456 ch167436_3dprint < database/schema.sql
   ```

4. **Проверка config.php:**
   ```bash
   # Файл api/config.php уже создан с правильными настройками
   # Для локальной разработки DB_HOST должен быть 'localhost'
   ```

5. **Запуск сайта:**
   ```bash
   # Откройте в браузере
   http://localhost/project-folder/
   
   # Инициализация данных (первый запуск)
   http://localhost/project-folder/api/init-database.php
   ```

### Вариант B: Production Деплой

1. **Подключение к хостингу:**
   ```bash
   # По FTP/SFTP загрузите все файлы на хостинг
   # Убедитесь, что загружена папка /api с файлом config.php
   ```

2. **Создание БД через PHPMyAdmin:**
   - Откройте: https://3dprint-omsk.ru/phpmyadmin
   - Логин: ch167436_3dprint
   - Пароль: 852789456
   - Создайте БД (если не создана): ch167436_3dprint
   - Выберите БД и импортируйте файл `database/schema.sql`

3. **Обновление api/config.php:**
   ```php
   define('DB_HOST', '3dprint-omsk.ru'); // Измените на реальный хост
   define('SITE_URL', 'https://your-domain.ru'); // Ваш домен
   ```

4. **Инициализация данных:**
   ```bash
   # Откройте в браузере
   https://your-domain.ru/api/init-database.php
   
   # Ожидаемый результат:
   # {"success":true,"message":"Database initialized successfully","created":{...}}
   ```

5. **Настройка Telegram (опционально):**
   - Откройте админ панель: https://your-domain.ru/admin.html
   - Перейдите в раздел "Настройки"
   - Нажмите "Получить Chat ID из Telegram"
   - Отправьте сообщение боту в Telegram
   - Сохраните полученный Chat ID

## 🔍 Проверка Работоспособности

### 1. Проверка API Endpoints

Откройте в браузере (или через curl):

```bash
# Проверка настроек
curl http://localhost/api/settings.php

# Проверка услуг
curl http://localhost/api/services.php

# Проверка FAQ
curl http://localhost/api/faq.php

# Проверка отзывов
curl http://localhost/api/testimonials.php
```

Все должны возвращать JSON с `{"success": true, ...}`

### 2. Проверка в Консоли Браузера

1. Откройте главную страницу: http://localhost/
2. Откройте консоль (F12 → Console)
3. Должны быть сообщения:
   ```
   ✅ APIClient initialized
   ✅ Database using API
   ✅ Settings loaded
   ✅ Services loaded: 6
   ✅ Testimonials loaded: 4
   ✅ FAQ loaded: 6
   ```

4. Проверьте отсутствие ошибок ❌

### 3. Проверка Форм

1. **Калькулятор:**
   - Заполните форму калькулятора
   - Отправьте заявку
   - Проверьте в консоли: `✅ Order submitted successfully`
   - Проверьте в БД таблицу `orders`

2. **Контактная форма:**
   - Заполните контактную форму
   - Отправьте сообщение
   - Проверьте в консоли: `✅ Order submitted successfully`

## 🛠️ Возможные Проблемы и Решения

### Ошибка: "Database connection failed"

**Причина:** Неверные credentials или БД не создана

**Решение:**
1. Проверьте, что БД `ch167436_3dprint` создана
2. Проверьте user/password в `api/config.php`
3. Проверьте, что пользователь имеет права доступа

```sql
-- Предоставить права
GRANT ALL PRIVILEGES ON ch167436_3dprint.* TO 'ch167436_3dprint'@'localhost';
FLUSH PRIVILEGES;
```

### Ошибка: "Table doesn't exist"

**Причина:** Схема БД не импортирована

**Решение:**
```bash
mysql -u ch167436_3dprint -p852789456 ch167436_3dprint < database/schema.sql
```

Или через PHPMyAdmin: Import → Выберите файл `database/schema.sql`

### Ошибка: "db.getData is not a function"

**Причина:** Старая версия telegram.js (уже исправлено)

**Решение:** Убедитесь, что используется последняя версия `js/telegram.js`

### API возвращает пустые данные

**Причина:** База данных пустая

**Решение:**
```bash
# Инициализируйте БД дефолтными данными
http://localhost/api/init-database.php
```

### Telegram не отправляет уведомления

**Причина:** Не настроен Chat ID

**Решение:**
1. Откройте админ панель: http://localhost/admin.html
2. Перейдите в "Настройки"
3. Нажмите "Получить Chat ID"
4. Отправьте сообщение боту: @YOUR_BOT_NAME
5. Нажмите снова "Получить Chat ID"
6. Сохраните полученный Chat ID

### CORS ошибки

**Причина:** Заголовки CORS не настроены

**Решение:** Проверьте, что `api/.htaccess` существует:
```apache
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization"
</IfModule>
```

## 📊 API Документация

### GET /api/settings.php
Получить все настройки

**Response:**
```json
{
  "success": true,
  "settings": {
    "telegram_chat_id": "123456789",
    "company_name": "3D Print Pro",
    ...
  }
}
```

### GET /api/services.php
Получить список услуг

**Query Parameters:**
- `active=true` - только активные
- `featured=true` - только избранные
- `limit=10` - ограничение
- `offset=0` - смещение

**Response:**
```json
{
  "success": true,
  "services": [...],
  "total": 6
}
```

### POST /api/orders.php
Создать заявку

**Request Body:**
```json
{
  "name": "Иван Иванов",
  "phone": "+7 999 123-45-67",
  "email": "ivan@example.com",
  "message": "Хочу заказать печать",
  "type": "contact"
}
```

**Response:**
```json
{
  "success": true,
  "order_id": 1,
  "order_number": "ORD-20250101123456-1234",
  "telegram_sent": true
}
```

Полная документация: `DATABASE_ARCHITECTURE.md`

## 🔒 Безопасность

1. **api/config.php** - НЕ коммитить в git (в .gitignore)
2. **Пароли** - Использовать сильные пароли в production
3. **HTTPS** - Обязательно в production
4. **SQL Injection** - Используем PDO prepared statements (защищены)
5. **XSS** - Используем `htmlspecialchars()` для всех пользовательских данных

## 📝 Заметки

- **Кодировка:** Все таблицы используют `utf8mb4_unicode_ci` для поддержки emoji и кириллицы
- **JSON поля:** Автоматически кодируются/декодируются в Database class
- **Timestamps:** Автоматически обновляются MySQL
- **Индексы:** Созданы для всех часто используемых полей

## 🎯 Чеклист После Установки

- [ ] База данных создана
- [ ] Схема импортирована (7 таблиц)
- [ ] api/config.php настроен
- [ ] init-database.php выполнен
- [ ] Все API endpoints возвращают данные
- [ ] Главная страница загружается без ошибок
- [ ] Консоль не показывает ошибок
- [ ] Калькулятор работает
- [ ] Контактная форма работает
- [ ] Заявки сохраняются в БД
- [ ] Telegram уведомления работают (если настроен)

## 📞 Поддержка

Если возникли проблемы:
1. Проверьте консоль браузера (F12)
2. Проверьте Network tab для API запросов
3. Проверьте PHP error log (`api/error.log`)
4. Проверьте MySQL error log

## 📚 Дополнительная Документация

- `DATABASE_ARCHITECTURE.md` - Полная документация архитектуры БД и API
- `DEPLOYMENT_CHECKLIST.md` - Чеклист для деплоя в production
- `README.md` - Общая документация проекта
