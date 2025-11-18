<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create materials table
 * 
 * 3D printing materials catalog for calculator and orders
 */
class CreateMaterialsTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('materials', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100)->unique();
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->decimal('price_per_gram', 8, 4);
            $table->decimal('density', 6, 3)->nullable();
            $table->json('color_options')->nullable();
            $table->json('properties')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index(['active', 'sort_order'], 'idx_active_sort');
            $table->index('code');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('materials');
    }
}
