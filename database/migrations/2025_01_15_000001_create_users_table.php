<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create users table
 * 
 * Replaces admin credentials from settings table with proper user management
 */
class CreateUsersTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 100)->unique();
            $table->string('password_hash', 255);
            $table->string('email', 255)->unique();
            $table->string('full_name', 255)->nullable();
            $table->enum('role', ['super_admin', 'admin', 'manager', 'viewer'])->default('admin');
            $table->boolean('active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedInteger('login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('username');
            $table->index('email');
            $table->index('active');
            $table->index('role');
            $table->index('deleted_at');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('users');
    }
}
