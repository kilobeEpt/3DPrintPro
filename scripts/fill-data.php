
<?php

require_once __DIR__ . '/../bootstrap/eloquent.php';



use Illuminate\Support\Facades\DB;



echo "🌱 Filling demo data...\n";



// 1. Services

DB::table('services')->truncate();

DB::table('services')->insert([

    ['id' => 1, 'name' => '3D печать пластик PLA', 'slug' => '3d-pechat-plastik-pla', 'description' => 'Печать деталей из пластика PLA высокого качества', 'icon' => null, 'category' => null, 'price' => null, 'features' => null, 'sort_order' => 1, 'active' => 1, 'featured' => 0, 'created_at' => now(), 'updated_at' => now()],

    ['id' => 2, 'name' => '3D печать PETG', 'slug' => '3d-pechat-petg', 'description' => 'Прочные детали из PETG пластика', 'icon' => null, 'category' => null, 'price' => null, 'features' => null, 'sort_order' => 2, 'active' => 1, 'featured' => 0, 'created_at' => now(), 'updated_at' => now()],

    ['id' => 3, 'name' => 'Дизайн 3D модели', 'slug' => 'dizajn-3d', 'description' => 'Профессиональное создание 3D моделей', 'icon' => null, 'category' => null, 'price' => null, 'features' => null, 'sort_order' => 3, 'active' => 1, 'featured' => 0, 'created_at' => now(), 'updated_at' => now()],

]);

echo "✓ Services filled\n";



// 2. Portfolio

DB::table('portfolio')->truncate();

DB::table('portfolio')->insert([

    ['id' => 1, 'title' => 'Прототип корпуса', 'description' => 'Электронный корпус PLA', 'image_url' => '/images/portfolio-1.jpg', 'category' => 'Прототипы', 'tags' => '["PLA"]', 'sort_order' => 1, 'active' => 1, 'created_at' => now(), 'updated_at' => now()],

    ['id' => 2, 'title' => 'Запчасти оборудования', 'description' => 'Запчасти из PETG', 'image_url' => '/images/portfolio-2.jpg', 'category' => 'Запчасти', 'tags' => '["PETG"]', 'sort_order' => 2, 'active' => 1, 'created_at' => now(), 'updated_at' => now()],

]);

echo "✓ Portfolio filled\n";



// 3. Testimonials

DB::table('testimonials')->truncate();

DB::table('testimonials')->insert([

    ['id' => 1, 'name' => 'Иван Петров', 'position' => 'Разработчик', 'avatar' => null, 'text' => 'Отличное качество печати!', 'rating' => 5, 'sort_order' => 1, 'approved' => 1, 'active' => 1, 'created_at' => now(), 'updated_at' => now()],

    ['id' => 2, 'name' => 'Мария Сидорова', 'position' => 'Дизайнер', 'avatar' => null, 'text' => 'Спасибо за профессионализм', 'rating' => 5, 'sort_order' => 2, 'approved' => 1, 'active' => 1, 'created_at' => now(), 'updated_at' => now()],

]);

echo "✓ Testimonials filled\n";



// 4. FAQ

DB::table('faq')->truncate();

DB::table('faq')->insert([

    ['id' => 1, 'question' => 'Какие материалы используются?', 'answer' => 'PLA, PETG, ABS, смола и металл', 'sort_order' => 1, 'active' => 1, 'created_at' => now(), 'updated_at' => now()],

    ['id' => 2, 'question' => 'Какой срок печати?', 'answer' => 'Обычно 3-5 дней, срочная 24 часа', 'sort_order' => 2, 'active' => 1, 'created_at' => now(), 'updated_at' => now()],

]);

echo "✓ FAQ filled\n";



// 5. Admin user

DB::table('admin_users')->truncate();

DB::table('admin_users')->insert([

    [

        'id' => 1,

        'email' => 'admin@3dprint.ru',

        'name' => 'Administrator',

        'password_hash' => password_hash('Пароль123', PASSWORD_BCRYPT),

        'role' => 'super_admin',

        'status' => 'active',

        'last_login_at' => null,

        'last_login_ip' => null,

        'failed_login_attempts' => 0,

        'locked_until' => null,

        'remember_token' => null,

        'created_at' => now(),

        'updated_at' => now()

    ]

]);

echo "✓ Admin user created\n";



echo "\n✅ All demo data filled successfully!\n";

