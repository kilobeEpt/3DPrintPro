<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create faq table
 * 
 * Frequently asked questions with categories
 */
class CreateFaqTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('faq', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('category_id')->nullable();
            $table->string('question', 500);
            $table->text('answer');
            $table->unsignedInteger('view_count')->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['category_id', 'active'], 'idx_category_active');
            $table->index('view_count');
            $table->index('deleted_at');
        });
        
        // Add fulltext index
        Capsule::connection()->statement(
            'ALTER TABLE faq ADD FULLTEXT INDEX ft_faq_search (question, answer)'
        );
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('faq');
    }
}
