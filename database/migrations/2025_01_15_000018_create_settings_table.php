<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create settings table
 * 
 * Application configuration (credentials removed)
 */
class CreateSettingsTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('setting_key', 100);
            $table->string('namespace', 50)->default('general');
            $table->text('setting_value')->nullable();
            $table->enum('data_type', ['string', 'integer', 'boolean', 'json', 'decimal'])->default('string');
            $table->boolean('encrypted')->default(false);
            $table->text('description')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            // Unique constraints
            $table->unique(['namespace', 'setting_key'], 'uk_namespace_key');
            
            // Index
            $table->index('namespace');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('settings');
    }
}
