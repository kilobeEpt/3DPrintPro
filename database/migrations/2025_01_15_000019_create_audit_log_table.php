<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create audit_log table
 * 
 * Centralized audit trail for all critical operations
 */
class CreateAuditLogTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('audit_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('table_name', 100);
            $table->unsignedBigInteger('record_id');
            $table->enum('action', ['INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT']);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['table_name', 'record_id'], 'idx_table_record');
            $table->index(['user_id', 'created_at'], 'idx_user_created');
            $table->index(['action', 'created_at'], 'idx_action_created');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('audit_log');
    }
}
