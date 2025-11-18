<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create customers table
 * 
 * Unified customer records with order history tracking
 */
class CreateCustomersTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email', 255)->nullable()->unique();
            $table->string('name', 255);
            $table->string('phone', 20);
            $table->string('telegram', 100)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('lifetime_value', 12, 2)->default(0.00);
            $table->enum('preferred_contact', ['email', 'phone', 'telegram'])->default('phone');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('email');
            $table->index('phone');
            $table->index('deleted_at');
            $table->index('lifetime_value');
        });
        
        // Add fulltext index separately
        Capsule::connection()->statement(
            'ALTER TABLE customers ADD FULLTEXT INDEX ft_customer_search (name, email, phone)'
        );
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('customers');
    }
}
