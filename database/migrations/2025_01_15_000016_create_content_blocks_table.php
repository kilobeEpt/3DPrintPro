<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create content_blocks table
 * 
 * Dynamic page content with versioning support
 */
class CreateContentBlocksTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('content_blocks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('block_name', 255)->unique();
            $table->string('title', 500)->nullable();
            $table->longText('content')->nullable();
            $table->json('data')->nullable();
            $table->string('page', 100)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('current_revision_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['page', 'sort_order', 'active'], 'idx_page_sort_active');
            $table->index('block_name');
            $table->index('deleted_at');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('content_blocks');
    }
}
