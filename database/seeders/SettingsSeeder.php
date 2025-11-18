<?php

/**
 * SettingsSeeder
 * 
 * Seeds application settings with default values
 */
class SettingsSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            // Site Settings
            ['namespace' => 'site', 'setting_key' => 'name', 'setting_value' => '3D PrintPro', 'data_type' => 'string', 'description' => 'Название сайта'],
            ['namespace' => 'site', 'setting_key' => 'description', 'setting_value' => 'Профессиональные услуги 3D печати в Омске', 'data_type' => 'string', 'description' => 'Описание сайта'],
            ['namespace' => 'site', 'setting_key' => 'keywords', 'setting_value' => '3D печать, Омск, FDM, SLA, прототипирование, 3D моделирование', 'data_type' => 'string', 'description' => 'Ключевые слова'],
            
            // Company Settings
            ['namespace' => 'company', 'setting_key' => 'name', 'setting_value' => '3D PrintPro', 'data_type' => 'string', 'description' => 'Название компании'],
            ['namespace' => 'company', 'setting_key' => 'address', 'setting_value' => 'г. Омск', 'data_type' => 'string', 'description' => 'Адрес компании'],
            ['namespace' => 'company', 'setting_key' => 'phone', 'setting_value' => '+7 (383) 000-00-00', 'data_type' => 'string', 'description' => 'Телефон'],
            ['namespace' => 'company', 'setting_key' => 'email', 'setting_value' => 'info@3dprintpro.ru', 'data_type' => 'string', 'description' => 'Email'],
            ['namespace' => 'company', 'setting_key' => 'hours', 'setting_value' => 'Пн-Пт: 10:00-18:00, Сб-Вс: 10:00-16:00', 'data_type' => 'string', 'description' => 'Часы работы'],
            
            // Telegram Settings
            ['namespace' => 'telegram', 'setting_key' => 'bot_token', 'setting_value' => '', 'data_type' => 'string', 'encrypted' => true, 'description' => 'Telegram Bot Token'],
            ['namespace' => 'telegram', 'setting_key' => 'chat_id', 'setting_value' => '', 'data_type' => 'string', 'description' => 'Telegram Chat ID для уведомлений'],
            ['namespace' => 'telegram', 'setting_key' => 'contact_url', 'setting_value' => 'https://t.me/PrintPro_Omsk', 'data_type' => 'string', 'description' => 'Ссылка на Telegram контакт'],
            ['namespace' => 'telegram', 'setting_key' => 'notify_new_order', 'setting_value' => 'true', 'data_type' => 'boolean', 'description' => 'Уведомлять о новых заказах'],
            ['namespace' => 'telegram', 'setting_key' => 'notify_status_change', 'setting_value' => 'true', 'data_type' => 'boolean', 'description' => 'Уведомлять об изменении статуса'],
            
            // Email Settings
            ['namespace' => 'email', 'setting_key' => 'admin_email', 'setting_value' => 'info@3dprintpro.ru', 'data_type' => 'string', 'description' => 'Email администратора'],
            ['namespace' => 'email', 'setting_key' => 'notifications_enabled', 'setting_value' => 'false', 'data_type' => 'boolean', 'description' => 'Включить email уведомления'],
            
            // Calculator Settings
            ['namespace' => 'calculator', 'setting_key' => 'base_price', 'setting_value' => '50', 'data_type' => 'decimal', 'description' => 'Базовая цена за грамм'],
            ['namespace' => 'calculator', 'setting_key' => 'currency', 'setting_value' => '₽', 'data_type' => 'string', 'description' => 'Символ валюты'],
            ['namespace' => 'calculator', 'setting_key' => 'weight_unit', 'setting_value' => 'г', 'data_type' => 'string', 'description' => 'Единица измерения веса'],
            ['namespace' => 'calculator', 'setting_key' => 'markup_percentage', 'setting_value' => '15.00', 'data_type' => 'decimal', 'description' => 'Наценка в процентах'],
        ];

        foreach ($settings as $setting) {
            $key = ['namespace' => $setting['namespace'], 'setting_key' => $setting['setting_key']];
            unset($setting['namespace'], $setting['setting_key']);
            
            // Only insert if doesn't exist (don't overwrite existing values)
            if (!$this->exists('settings', $key)) {
                $this->insert('settings', array_merge($key, $setting));
            }
        }
    }
}
