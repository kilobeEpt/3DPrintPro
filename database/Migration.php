<?php

/**
 * Base Migration Class
 * 
 * All migrations should extend this class and implement up() and down() methods.
 * Uses Illuminate Schema Builder for database operations.
 */
abstract class Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    abstract public function up();

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    abstract public function down();

    /**
     * Get the migration name
     *
     * @return string
     */
    public function getName()
    {
        return get_class($this);
    }
}
