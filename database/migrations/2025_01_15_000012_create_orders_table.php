<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create orders table
 * 
 * Customer orders and contact inquiries with relationships
 */
class CreateOrdersTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_number', 50)->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedInteger('service_id')->nullable();
            $table->unsignedInteger('material_id')->nullable();
            $table->unsignedInteger('order_type_id');
            $table->unsignedInteger('order_status_id');
            $table->json('customer_snapshot')->nullable();
            $table->string('subject', 255)->nullable();
            $table->text('message')->nullable();
            $table->decimal('amount', 12, 2)->default(0.00);
            $table->json('calculator_data')->nullable();
            $table->boolean('telegram_sent')->default(false);
            $table->text('telegram_error')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('set null');
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('set null');
            $table->foreign('order_type_id')->references('id')->on('order_types')->onDelete('restrict');
            $table->foreign('order_status_id')->references('id')->on('order_statuses')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['customer_id', 'created_at'], 'idx_customer_created');
            $table->index(['order_status_id', 'created_at'], 'idx_status_created');
            $table->index('order_number');
            $table->index(['order_type_id', 'order_status_id'], 'idx_type_status');
        });
        
        // Add fulltext index
        Capsule::connection()->statement(
            'ALTER TABLE orders ADD FULLTEXT INDEX ft_order_search (subject, message)'
        );
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('orders');
    }
}
