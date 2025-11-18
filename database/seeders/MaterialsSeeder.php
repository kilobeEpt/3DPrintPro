<?php

/**
 * MaterialsSeeder
 * 
 * Seeds materials for 3D printing calculator
 */
class MaterialsSeeder extends Seeder
{
    public function run()
    {
        $materials = [
            [
                'name' => 'PLA',
                'code' => 'PLA',
                'description' => 'Популярный биоразлагаемый пластик. Легок в печати, подходит для большинства проектов.',
                'price_per_gram' => 0.05,
                'density' => 1.24,
                'color_options' => json_encode(['Белый', 'Черный', 'Красный', 'Синий', 'Зеленый', 'Желтый']),
                'properties' => json_encode([
                    'temperature' => '190-220°C',
                    'bed_temp' => '50-60°C',
                    'strength' => 'Средняя',
                    'flexibility' => 'Низкая'
                ]),
                'active' => true,
                'sort_order' => 10
            ],
            [
                'name' => 'ABS',
                'code' => 'ABS',
                'description' => 'Прочный пластик для функциональных деталей. Устойчив к высоким температурам.',
                'price_per_gram' => 0.06,
                'density' => 1.04,
                'color_options' => json_encode(['Белый', 'Черный', 'Красный', 'Синий']),
                'properties' => json_encode([
                    'temperature' => '220-250°C',
                    'bed_temp' => '80-100°C',
                    'strength' => 'Высокая',
                    'flexibility' => 'Средняя'
                ]),
                'active' => true,
                'sort_order' => 20
            ],
            [
                'name' => 'PETG',
                'code' => 'PETG',
                'description' => 'Прочный и гибкий пластик. Сочетает преимущества PLA и ABS.',
                'price_per_gram' => 0.07,
                'density' => 1.27,
                'color_options' => json_encode(['Прозрачный', 'Белый', 'Черный', 'Красный', 'Синий']),
                'properties' => json_encode([
                    'temperature' => '220-250°C',
                    'bed_temp' => '70-80°C',
                    'strength' => 'Высокая',
                    'flexibility' => 'Средняя'
                ]),
                'active' => true,
                'sort_order' => 30
            ],
            [
                'name' => 'TPU',
                'code' => 'TPU',
                'description' => 'Гибкий эластичный пластик. Идеален для амортизирующих деталей.',
                'price_per_gram' => 0.10,
                'density' => 1.21,
                'color_options' => json_encode(['Черный', 'Прозрачный', 'Красный', 'Синий']),
                'properties' => json_encode([
                    'temperature' => '210-230°C',
                    'bed_temp' => '50-60°C',
                    'strength' => 'Средняя',
                    'flexibility' => 'Очень высокая'
                ]),
                'active' => true,
                'sort_order' => 40
            ],
            [
                'name' => 'Nylon',
                'code' => 'NYLON',
                'description' => 'Очень прочный и износостойкий пластик для технических деталей.',
                'price_per_gram' => 0.12,
                'density' => 1.14,
                'color_options' => json_encode(['Натуральный', 'Белый', 'Черный']),
                'properties' => json_encode([
                    'temperature' => '240-260°C',
                    'bed_temp' => '70-90°C',
                    'strength' => 'Очень высокая',
                    'flexibility' => 'Высокая'
                ]),
                'active' => true,
                'sort_order' => 50
            ],
            [
                'name' => 'Resin Standard',
                'code' => 'RESIN_STD',
                'description' => 'Стандартная смола для SLA печати. Высокая детализация.',
                'price_per_gram' => 0.15,
                'density' => 1.10,
                'color_options' => json_encode(['Серый', 'Белый', 'Черный', 'Прозрачный']),
                'properties' => json_encode([
                    'technology' => 'SLA',
                    'layer_height' => '0.025-0.1mm',
                    'detail' => 'Очень высокая',
                    'strength' => 'Средняя'
                ]),
                'active' => true,
                'sort_order' => 60
            ]
        ];

        foreach ($materials as $material) {
            $this->updateOrInsert('materials', ['code' => $material['code']], $material);
        }
    }
}
