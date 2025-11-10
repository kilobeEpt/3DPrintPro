<?php
// ========================================
// Database Configuration
// ========================================
// Copy this file to config.php and fill in your actual credentials
// NEVER commit config.php to git!

define('DB_HOST', 'localhost');
define('DB_NAME', 'ch167436_3dprint');
define('DB_USER', 'ch167436_3dprint'); // Change to your actual database user
define('DB_PASS', 'your_password_here'); // Change to your actual password
define('DB_CHARSET', 'utf8mb4');

// ========================================
// Telegram Configuration
// ========================================
define('TELEGRAM_BOT_TOKEN', '8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI');
define('TELEGRAM_CHAT_ID', ''); // Fill this in from admin panel or Telegram setup

// ========================================
// Site Configuration
// ========================================
define('SITE_URL', 'https://your-domain.ru');
define('SITE_NAME', '3D Print Pro');

// ========================================
// Error Reporting (disable in production)
// ========================================
// During development, enable error reporting
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// In production, disable error display
ini_set('display_errors', 0);
error_reporting(E_ALL);
