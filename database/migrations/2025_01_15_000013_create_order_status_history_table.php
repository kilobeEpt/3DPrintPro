<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create order_status_history table
 * 
 * Audit trail for order status changes
 */
class CreateOrderStatusHistoryTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('order_status_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedInteger('old_status_id')->nullable();
            $table->unsignedInteger('new_status_id');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('old_status_id')->references('id')->on('order_statuses')->onDelete('set null');
            $table->foreign('new_status_id')->references('id')->on('order_statuses')->onDelete('restrict');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['order_id', 'created_at'], 'idx_order_created');
            $table->index('changed_by');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('order_status_history');
    }
}
