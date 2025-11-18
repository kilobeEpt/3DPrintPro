<?php

/**
 * OrderStatusesSeeder
 * 
 * Seeds order statuses lookup table
 */
class OrderStatusesSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            [
                'status_key' => 'new',
                'display_name' => 'Новый',
                'color' => '#007bff',
                'description' => 'Новый заказ, требует обработки',
                'is_active' => true,
                'is_terminal' => false,
                'sort_order' => 10
            ],
            [
                'status_key' => 'processing',
                'display_name' => 'В работе',
                'color' => '#ffc107',
                'description' => 'Заказ в процессе выполнения',
                'is_active' => true,
                'is_terminal' => false,
                'sort_order' => 20
            ],
            [
                'status_key' => 'pending_approval',
                'display_name' => 'Ожидает подтверждения',
                'color' => '#17a2b8',
                'description' => 'Заказ ожидает подтверждения от клиента',
                'is_active' => true,
                'is_terminal' => false,
                'sort_order' => 30
            ],
            [
                'status_key' => 'completed',
                'display_name' => 'Выполнен',
                'color' => '#28a745',
                'description' => 'Заказ успешно выполнен',
                'is_active' => true,
                'is_terminal' => true,
                'sort_order' => 40
            ],
            [
                'status_key' => 'cancelled',
                'display_name' => 'Отменен',
                'color' => '#dc3545',
                'description' => 'Заказ отменен',
                'is_active' => true,
                'is_terminal' => true,
                'sort_order' => 50
            ],
            [
                'status_key' => 'on_hold',
                'display_name' => 'Приостановлен',
                'color' => '#6c757d',
                'description' => 'Заказ временно приостановлен',
                'is_active' => true,
                'is_terminal' => false,
                'sort_order' => 60
            ]
        ];

        foreach ($statuses as $status) {
            $this->updateOrInsert('order_statuses', ['status_key' => $status['status_key']], $status);
        }
    }
}
