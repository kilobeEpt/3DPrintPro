<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create portfolio table
 * 
 * Project showcase with relationships
 */
class CreatePortfolioTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('portfolio', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->unsignedInteger('service_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('views')->default(0);
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['category_id', 'active'], 'idx_category_active');
            $table->index('service_id');
            $table->index('slug');
            $table->index('deleted_at');
        });
        
        // Add fulltext index
        Capsule::connection()->statement(
            'ALTER TABLE portfolio ADD FULLTEXT INDEX ft_portfolio_search (title, description)'
        );
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('portfolio');
    }
}
