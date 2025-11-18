<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create testimonials table
 * 
 * Customer reviews with verification
 */
class CreateTestimonialsTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('testimonials', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->text('text');
            $table->unsignedInteger('rating')->default(5);
            $table->string('position', 255)->nullable();
            $table->string('avatar', 500)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('approved')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['approved', 'active'], 'idx_approved_active');
            $table->index('customer_id');
            $table->index('rating');
            $table->index('deleted_at');
        });
        
        // Add check constraint
        Capsule::connection()->statement(
            'ALTER TABLE testimonials ADD CONSTRAINT chk_rating CHECK (rating >= 1 AND rating <= 5)'
        );
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('testimonials');
    }
}
