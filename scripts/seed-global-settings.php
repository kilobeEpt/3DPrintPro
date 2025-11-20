<?php
// Seed Global Settings
// This script populates default global settings from the site's hardcoded values

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Services\SettingsService;

$settingsService = new SettingsService();

// Default settings based on current site configuration
$defaultSettings = [
    // Contact Information
    'contact_phone' => '+7 (999) 123-45-67',
    'contact_email' => 'info@3dprint-omsk.ru',
    'contact_address' => 'ул. Ленина, д. 15',
    'contact_city' => 'Омск',
    'contact_postal_code' => '644000',
    'contact_region' => 'Омская область',
    'contact_country' => 'RU',
    'contact_working_hours' => 'Пн-Пт: 9:00-18:00',
    'contact_latitude' => 54.9885,
    'contact_longitude' => 73.3242,
    
    // Social Links
    'social_telegram' => 'https://t.me/PrintPro_Omsk',
    'social_vk' => '',
    'social_instagram' => '',
    'social_facebook' => '',
    'social_youtube' => '',
    'social_twitter' => '',
    'social_whatsapp' => '',
    
    // SEO Metadata
    'seo_title' => '3D печать в Омске — услуги 3D печати и моделирования | 3D Print Pro',
    'seo_description' => 'Профессиональная 3D печать в Омске: FDM, SLA, SLS технологии. 3D моделирование, постобработка, быстрое изготовление. Опыт 12 лет.',
    'seo_keywords' => '3D печать Омск, услуги 3D печати Омск, 3D моделирование Омск, FDM печать, SLA печать, прототипирование Омск',
    'seo_og_image' => 'https://3dprint-omsk.ru/images/og-image.jpg',
    'seo_og_type' => 'website',
    'seo_site_name' => '3D Print Pro',
    'seo_canonical_url' => 'https://3dprint-omsk.ru/',
    
    // Email SMTP Configuration
    'smtp_host' => '',
    'smtp_port' => 587,
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',
    'smtp_from_email' => 'info@3dprint-omsk.ru',
    'smtp_from_name' => '3D Print Pro',
    
    // Telegram Integration (existing)
    'telegram_chat_id' => '',
    'telegram_bot_token' => '',
    'telegram_contact_url' => 'https://t.me/PrintPro_Omsk',
    'telegram_notify_new_order' => true,
    'telegram_notify_status_change' => true,
    
    // Email Notifications
    'email_notifications_enabled' => false,
    'admin_email' => 'info@3dprint-omsk.ru',
    'notifications_enabled' => true,
    'notifications_telegram_status_change' => true,
    'notifications_email_status_change' => false,
    'notifications_email_address' => 'info@3dprint-omsk.ru',
    
    // Logging & Analytics
    'analytics_enabled' => false,
    'analytics_google_id' => '',
    'analytics_yandex_id' => '',
    'logging_enabled' => true,
    'logging_level' => 'info',
    'logging_max_files' => 30,
    
    // Caching Parameters
    'cache_enabled' => true,
    'cache_ttl' => 300,
    'cache_driver' => 'file',
    'cache_prefix' => '3dprint_',
];

echo "==================================================\n";
echo "  Seeding Global Settings\n";
echo "==================================================\n\n";

$successCount = 0;
$skipCount = 0;
$errorCount = 0;

foreach ($defaultSettings as $key => $value) {
    try {
        // Check if setting already exists
        $existing = $settingsService->get($key, null, false);
        
        if ($existing !== null) {
            echo "⏭️  Skipping '{$key}' (already exists)\n";
            $skipCount++;
            continue;
        }
        
        // Set the default value
        $settingsService->set($key, $value, 'seeder');
        echo "✅ Created '{$key}'\n";
        $successCount++;
        
    } catch (\Exception $e) {
        echo "❌ Error setting '{$key}': " . $e->getMessage() . "\n";
        $errorCount++;
    }
}

echo "\n==================================================\n";
echo "  Summary\n";
echo "==================================================\n";
echo "✅ Created: {$successCount}\n";
echo "⏭️  Skipped: {$skipCount}\n";
echo "❌ Errors:  {$errorCount}\n";
echo "\n";

if ($errorCount === 0) {
    echo "✅ Global settings seeded successfully!\n";
    exit(0);
} else {
    echo "⚠️  Global settings seeded with errors.\n";
    exit(1);
}
