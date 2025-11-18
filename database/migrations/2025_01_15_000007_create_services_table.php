<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create services table
 * 
 * Service offerings with structured pricing and relationships
 */
class CreateServicesTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('services', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('icon', 255)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price_amount', 10, 2)->nullable();
            $table->enum('price_unit', ['per_gram', 'per_hour', 'per_model', 'per_project', 'custom'])->default('custom');
            $table->string('price_display', 100)->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->boolean('featured')->default(false);
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['category_id', 'active', 'sort_order'], 'idx_category_active_sort');
            $table->index('featured');
            $table->index('slug');
            $table->index('deleted_at');
        });
        
        // Add fulltext index
        Capsule::connection()->statement(
            'ALTER TABLE services ADD FULLTEXT INDEX ft_service_search (name, description)'
        );
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('services');
    }
}
