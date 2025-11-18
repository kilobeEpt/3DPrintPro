<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create portfolio_tags table
 * 
 * Many-to-many relationship between portfolio and tags
 */
class CreatePortfolioTagsTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('portfolio_tags', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('portfolio_id');
            $table->unsignedInteger('tag_id');
            
            // Foreign keys with cascade delete
            $table->foreign('portfolio_id')->references('id')->on('portfolio')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
            
            // Unique constraint
            $table->unique(['portfolio_id', 'tag_id'], 'uk_portfolio_tag');
            
            // Index
            $table->index(['tag_id', 'portfolio_id'], 'idx_tag_portfolio');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('portfolio_tags');
    }
}
