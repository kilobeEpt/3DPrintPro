<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create tags table
 * 
 * Reusable tag taxonomy for portfolio
 */
class CreateTagsTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes
            $table->index('slug');
            $table->index('usage_count');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('tags');
    }
}
