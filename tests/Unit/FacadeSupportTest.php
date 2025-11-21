<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Test Facade support in Eloquent bootstrap
 * 
 * Verifies that the Facade root is properly set and that
 * DB and Schema facades work correctly.
 */
class FacadeSupportTest extends TestCase
{
    /**
     * Test that DB facade is available and functional
     */
    public function testDBFacadeIsAvailable()
    {
        $this->assertTrue(class_exists('Illuminate\Support\Facades\DB'));
    }
    
    /**
     * Test that DB::table() facade works
     */
    public function testDBTableFacadeWorks()
    {
        $count = DB::table('admin_users')->count();
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
    
    /**
     * Test that DB::select() facade works
     */
    public function testDBSelectFacadeWorks()
    {
        $result = DB::select('SELECT COUNT(*) as total FROM admin_users');
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertObjectHasAttribute('total', $result[0]);
    }
    
    /**
     * Test that Schema facade is available
     */
    public function testSchemaFacadeIsAvailable()
    {
        $this->assertTrue(class_exists('Illuminate\Support\Facades\Schema'));
    }
    
    /**
     * Test that Schema::hasTable() facade works
     */
    public function testSchemaHasTableFacadeWorks()
    {
        $hasTable = Schema::hasTable('admin_users');
        $this->assertIsBool($hasTable);
        $this->assertTrue($hasTable);
    }
    
    /**
     * Test multiple tables exist via Schema facade
     */
    public function testSchemaFacadeCanCheckMultipleTables()
    {
        $tables = ['admin_users', 'services', 'portfolio', 'orders', 'forms', 'settings'];
        
        foreach ($tables as $table) {
            $exists = Schema::hasTable($table);
            $this->assertTrue($exists, "Table '{$table}' should exist");
        }
    }
    
    /**
     * Test that Capsule static methods still work alongside Facades
     */
    public function testCapsuleStillWorksWithFacades()
    {
        $capsuleCount = Capsule::table('admin_users')->count();
        $this->assertIsInt($capsuleCount);
        $this->assertGreaterThanOrEqual(0, $capsuleCount);
    }
    
    /**
     * Test that DB facade and Capsule return consistent results
     */
    public function testDBFacadeAndCapsuleAreConsistent()
    {
        $dbCount = DB::table('admin_users')->count();
        $capsuleCount = Capsule::table('admin_users')->count();
        
        $this->assertEquals($dbCount, $capsuleCount, 
            'DB::table() and Capsule::table() should return the same count');
    }
    
    /**
     * Test that DB facade can execute complex queries
     */
    public function testDBFacadeCanExecuteComplexQueries()
    {
        $result = DB::table('admin_users')
            ->select('id', 'email', 'role', 'status')
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $this->assertInstanceOf('Illuminate\Support\Collection', $result);
    }
    
    /**
     * Test that DB facade can use where clauses
     */
    public function testDBFacadeSupportsWhereClause()
    {
        $count = DB::table('admin_users')
            ->where('status', 'active')
            ->count();
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
    
    /**
     * Test that Schema facade can get column listing
     */
    public function testSchemaFacadeCanGetColumns()
    {
        $columns = Schema::getColumnListing('admin_users');
        
        $this->assertIsArray($columns);
        $this->assertContains('id', $columns);
        $this->assertContains('email', $columns);
        $this->assertContains('role', $columns);
        $this->assertContains('status', $columns);
    }
}
