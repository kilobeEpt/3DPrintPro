<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create service_features table
 * 
 * Normalized service features (replaces JSON array)
 */
class CreateServiceFeaturesTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('service_features', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('service_id');
            $table->string('feature_text', 500);
            $table->integer('sort_order')->default(0);
            
            // Foreign key with cascade delete
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            
            // Index
            $table->index(['service_id', 'sort_order'], 'idx_service_sort');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('service_features');
    }
}
