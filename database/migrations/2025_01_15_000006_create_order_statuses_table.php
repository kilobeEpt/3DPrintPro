<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create order_statuses table
 * 
 * Extensible order status workflow (replaces ENUM)
 */
class CreateOrderStatusesTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('order_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('status_key', 50)->unique();
            $table->string('display_name', 100);
            $table->string('color', 20)->default('#6c757d');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_terminal')->default(false);
            $table->integer('sort_order')->default(0);
            
            $table->index('is_active');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('order_statuses');
    }
}
