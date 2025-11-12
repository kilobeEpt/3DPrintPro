<?php
// ========================================
// Database Configuration Example
// ========================================
// 
// SETUP INSTRUCTIONS:
// 1. Copy this file to config.php:
//    cp api/config.example.php api/config.php
// 
// 2. Edit api/config.php with your actual credentials
// 
// 3. NEVER commit api/config.php to git!
//    (it's already in .gitignore)
//
// ========================================

// ========================================
// Database Configuration
// ========================================
// Production Target:
//   Host: localhost (or ch167436.tw1.ru for remote access)
//   Database: ch167436_3dprint
//   User: ch167436_3dprint
//   Password: [YOUR_PASSWORD_HERE]

define('DB_HOST', 'localhost'); // Use 'localhost' on server, 'ch167436.tw1.ru' for remote
define('DB_NAME', 'ch167436_3dprint'); // Production database name
define('DB_USER', 'ch167436_3dprint'); // Production database user
define('DB_PASS', 'YOUR_PASSWORD_HERE'); // ⚠️ CHANGE THIS - Use actual password from hosting panel
define('DB_CHARSET', 'utf8mb4'); // Full Unicode support (emojis, Russian, etc.)

// ========================================
// Telegram Configuration
// ========================================
// Bot for sending order notifications
define('TELEGRAM_BOT_TOKEN', '8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI');
define('TELEGRAM_CHAT_ID', ''); // Fill this in from admin panel or Telegram setup
// To get CHAT_ID: Send /start to bot, then visit:
// https://api.telegram.org/bot{TELEGRAM_BOT_TOKEN}/getUpdates

// ========================================
// Site Configuration
// ========================================
define('SITE_URL', 'https://ch167436.tw1.ru'); // Production URL
define('SITE_NAME', '3D Print Pro');

// ========================================
// Rate Limiting Configuration
// ========================================
// Protects API endpoints from abuse
define('RATE_LIMIT_MAX_REQUESTS', 60); // Maximum requests per time window
define('RATE_LIMIT_TIME_WINDOW', 60); // Time window in seconds (60 = 1 minute)
// Default: 60 requests per minute per IP address

// ========================================
// Error Reporting
// ========================================
// IMPORTANT: Set based on environment
//
// DEVELOPMENT / STAGING:
// Uncomment these lines to see errors during development
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
//
// PRODUCTION:
// Keep errors hidden from users, but log them
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
// ini_set('error_log', '/path/to/php-error.log'); // Optional: custom error log path

// ========================================
// Security Notes
// ========================================
// 1. Change DB_PASS to your actual database password
// 2. Keep api/config.php out of git (already in .gitignore)
// 3. Set proper file permissions: chmod 600 api/config.php
// 4. Update TELEGRAM_CHAT_ID with your actual chat ID
// 5. Change RESET_TOKEN in api/init-database.php
// 6. Change BACKUP_TOKEN in database/backup.php if using HTTP backups
//
// ========================================
