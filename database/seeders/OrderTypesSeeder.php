<?php

/**
 * OrderTypesSeeder
 * 
 * Seeds order types lookup table
 */
class OrderTypesSeeder extends Seeder
{
    public function run()
    {
        $types = [
            [
                'type_key' => 'order',
                'display_name' => 'Заказ',
                'description' => 'Заказ на печать с заполненными параметрами калькулятора',
                'active' => true,
                'sort_order' => 10
            ],
            [
                'type_key' => 'contact',
                'display_name' => 'Обращение',
                'description' => 'Контактная форма без расчета стоимости',
                'active' => true,
                'sort_order' => 20
            ],
            [
                'type_key' => 'consultation',
                'display_name' => 'Консультация',
                'description' => 'Запрос на консультацию',
                'active' => true,
                'sort_order' => 30
            ],
            [
                'type_key' => 'custom',
                'display_name' => 'Индивидуальный',
                'description' => 'Индивидуальный заказ по ТЗ',
                'active' => true,
                'sort_order' => 40
            ]
        ];

        foreach ($types as $type) {
            $this->updateOrInsert('order_types', ['type_key' => $type['type_key']], $type);
        }
    }
}
