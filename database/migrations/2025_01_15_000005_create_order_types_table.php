<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create order_types table
 * 
 * Extensible order type taxonomy (replaces ENUM)
 */
class CreateOrderTypesTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('order_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type_key', 50)->unique();
            $table->string('display_name', 100);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            
            $table->index('active');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('order_types');
    }
}
