<?php

/**
 * DatabaseSeeder
 * 
 * Main seeder that calls all other seeders in the correct order
 */
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║         3D PrintPro Database Seeder v3.0                     ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";
        
        // Seed lookup tables first (referenced by other tables)
        echo "📋 Seeding lookup tables...\n";
        $this->call('OrderTypesSeeder');
        $this->call('OrderStatusesSeeder');
        $this->call('CategoriesSeeder');
        $this->call('MaterialsSeeder');
        
        echo "\n📋 Seeding system data...\n";
        $this->call('DefaultUserSeeder');
        $this->call('SettingsSeeder');
        
        echo "\n";
        echo "✅ Database seeding completed successfully!\n";
        echo "\n";
        echo "Next steps:\n";
        echo "  1. Change default admin password (admin/admin123)\n";
        echo "  2. Configure Telegram settings in admin panel\n";
        echo "  3. Add services, portfolio, and content via admin panel\n";
        echo "\n";
    }
}
