<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create content_revisions table
 * 
 * Version history for content blocks
 */
class CreateContentRevisionsTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('content_revisions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('content_block_id');
            $table->string('title', 500)->nullable();
            $table->longText('content')->nullable();
            $table->json('data')->nullable();
            $table->text('revision_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            // Foreign keys
            $table->foreign('content_block_id')->references('id')->on('content_blocks')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            
            // Index
            $table->index(['content_block_id', 'created_at'], 'idx_content_created');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('content_revisions');
    }
}
