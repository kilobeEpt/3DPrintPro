<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Base Seeder Class
 * 
 * All seeders should extend this class and implement run() method.
 */
abstract class Seeder
{
    /**
     * Run the seeder.
     *
     * @return void
     */
    abstract public function run();

    /**
     * Call another seeder
     *
     * @param string $seederClass
     * @return void
     */
    protected function call($seederClass)
    {
        $seeder = new $seederClass();
        $seeder->run();
    }

    /**
     * Get database connection
     *
     * @return \Illuminate\Database\Connection
     */
    protected function db()
    {
        return Capsule::connection();
    }

    /**
     * Insert data into table
     *
     * @param string $table
     * @param array $data
     * @return void
     */
    protected function insert($table, array $data)
    {
        Capsule::table($table)->insert($data);
    }

    /**
     * Check if record exists
     *
     * @param string $table
     * @param array $where
     * @return bool
     */
    protected function exists($table, array $where)
    {
        return Capsule::table($table)->where($where)->exists();
    }

    /**
     * Update or insert record
     *
     * @param string $table
     * @param array $attributes
     * @param array $values
     * @return void
     */
    protected function updateOrInsert($table, array $attributes, array $values = [])
    {
        Capsule::table($table)->updateOrInsert($attributes, $values);
    }
}
