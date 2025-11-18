<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create categories table
 * 
 * Shared taxonomy for services, portfolio, and FAQ
 */
class CreateCategoriesTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->enum('type', ['service', 'portfolio', 'faq']);
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->unique(['type', 'slug'], 'uk_type_slug');
            $table->index(['type', 'active'], 'idx_type_active');
            $table->index('sort_order');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('categories');
    }
}
