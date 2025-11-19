<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\SettingsService;
use InvalidArgumentException;

class SettingsServiceTest extends TestCase
{
    private $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SettingsService();
        cleanTestData();
    }
    
    protected function tearDown(): void
    {
        cleanTestData();
        $this->service->invalidateCache();
        parent::tearDown();
    }
    
    public function testGetAllReturnsEmptyArrayWhenNoSettings()
    {
        $settings = $this->service->getAll(false);
        $this->assertIsArray($settings);
        $this->assertEmpty($settings);
    }
    
    public function testSetAndGetSingleSetting()
    {
        $this->service->set('test_key', 'test_value', 'phpunit');
        $value = $this->service->get('test_key', null, false);
        
        $this->assertEquals('test_value', $value);
    }
    
    public function testGetReturnsDefaultWhenKeyNotFound()
    {
        $value = $this->service->get('nonexistent_key', 'default_value', false);
        $this->assertEquals('default_value', $value);
    }
    
    public function testSetMultipleSettings()
    {
        $settings = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        
        $result = $this->service->setMultiple($settings, 'phpunit');
        
        $this->assertEquals(3, $result['success']);
        $this->assertEmpty($result['errors']);
        
        $this->assertEquals('value1', $this->service->get('key1', null, false));
        $this->assertEquals('value2', $this->service->get('key2', null, false));
        $this->assertEquals('value3', $this->service->get('key3', null, false));
    }
    
    public function testDeleteSetting()
    {
        $this->service->set('test_delete', 'value', 'phpunit');
        $deleted = $this->service->delete('test_delete', 'phpunit');
        
        $this->assertTrue($deleted);
        
        $value = $this->service->get('test_delete', null, false);
        $this->assertNull($value);
    }
    
    public function testDeleteNonExistentSettingReturnsFalse()
    {
        $deleted = $this->service->delete('nonexistent', 'phpunit');
        $this->assertFalse($deleted);
    }
    
    public function testGetGroupedSettings()
    {
        $this->service->set('telegram_bot_token', 'token123', 'phpunit');
        $this->service->set('telegram_chat_id', 'chat456', 'phpunit');
        $this->service->set('other_setting', 'value', 'phpunit');
        
        $grouped = $this->service->getGrouped('telegram', false);
        
        $this->assertCount(2, $grouped);
        $this->assertArrayHasKey('telegram_bot_token', $grouped);
        $this->assertArrayHasKey('telegram_chat_id', $grouped);
        $this->assertArrayNotHasKey('other_setting', $grouped);
    }
    
    public function testTypeCastingString()
    {
        $this->service->set('string_key', 'test_string', 'phpunit');
        $value = $this->service->get('string_key', null, false);
        
        $this->assertIsString($value);
        $this->assertEquals('test_string', $value);
    }
    
    public function testTypeCastingInteger()
    {
        $this->service->set('max_file_size', 1024, 'phpunit');
        $value = $this->service->get('max_file_size', null, false);
        
        $this->assertEquals(1024, $value);
    }
    
    public function testTypeCastingBoolean()
    {
        $this->service->set('notifications_enabled', true, 'phpunit');
        $value = $this->service->get('notifications_enabled', null, false);
        
        $this->assertTrue($value === true || $value === '1' || $value === 1);
    }
    
    public function testTypeCastingFloat()
    {
        $this->service->set('price_per_gram', 3.14, 'phpunit');
        $value = $this->service->get('price_per_gram', null, false);
        
        $this->assertEquals(3.14, (float)$value);
    }
    
    public function testTypeCastingArray()
    {
        $array = ['option1', 'option2', 'option3'];
        $this->service->set('allowed_extensions', $array, 'phpunit');
        $value = $this->service->get('allowed_extensions', null, false);
        
        $this->assertIsArray($value);
        $this->assertEquals($array, $value);
    }
    
    public function testTypeCastingJson()
    {
        $json = ['key1' => 'value1', 'key2' => 'value2'];
        $this->service->set('calculator_config', $json, 'phpunit');
        $value = $this->service->get('calculator_config', null, false);
        
        $this->assertIsArray($value);
        $this->assertEquals($json, $value);
    }
    
    public function testValidationMaxLength()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("exceeds maximum length");
        
        $longString = str_repeat('x', 300);
        $this->service->set('telegram_bot_token', $longString, 'phpunit');
    }
    
    public function testValidationMinValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("must be at least");
        
        $this->service->set('max_file_size', -100, 'phpunit');
    }
    
    public function testValidationMaxValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("must not exceed");
        
        $this->service->set('max_file_size', 999999999999, 'phpunit');
    }
    
    public function testValidationInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("must be a number");
        
        $this->service->set('max_file_size', 'not_a_number', 'phpunit');
    }
    
    public function testAuditLogging()
    {
        $this->service->set('test_audit', 'initial_value', 'phpunit');
        $this->service->set('test_audit', 'updated_value', 'phpunit');
        
        $history = $this->service->getAuditHistory('test_audit', 10);
        
        $this->assertIsArray($history);
        $this->assertGreaterThanOrEqual(2, count($history));
        
        $latestAudit = $history[0];
        $this->assertEquals('test_audit', $latestAudit['setting_key']);
        $this->assertEquals('phpunit', $latestAudit['changed_by']);
    }
    
    public function testAuditLoggingOnDelete()
    {
        $this->service->set('test_delete_audit', 'value', 'phpunit');
        $this->service->delete('test_delete_audit', 'phpunit');
        
        $history = $this->service->getAuditHistory('test_delete_audit', 10);
        
        $this->assertIsArray($history);
        $this->assertGreaterThanOrEqual(2, count($history));
        
        $deleteAudit = $history[0];
        $this->assertNull($deleteAudit['new_value']);
    }
    
    public function testCacheWarmup()
    {
        $this->service->set('cache_test', 'value', 'phpunit');
        $result = $this->service->warmCache();
        
        $this->assertTrue($result);
    }
    
    public function testCacheInvalidation()
    {
        $this->service->warmCache();
        $result = $this->service->invalidateCache();
        
        $this->assertTrue($result);
    }
    
    public function testGetAllUsesCache()
    {
        $this->service->set('cached_key', 'cached_value', 'phpunit');
        
        $settings1 = $this->service->getAll(false);
        $this->service->warmCache();
        $settings2 = $this->service->getAll(true);
        
        $this->assertEquals($settings1, $settings2);
    }
    
    public function testSetInvalidatesCache()
    {
        $this->service->set('key1', 'value1', 'phpunit');
        $this->service->warmCache();
        
        $this->service->set('key2', 'value2', 'phpunit');
        
        $settings = $this->service->getAll(true);
        $this->assertArrayHasKey('key2', $settings);
    }
    
    public function testSetMultipleWithValidationErrors()
    {
        $settings = [
            'valid_key' => 'valid_value',
            'telegram_bot_token' => str_repeat('x', 300),
            'another_valid_key' => 'another_value',
        ];
        
        $result = $this->service->setMultiple($settings, 'phpunit');
        
        $this->assertEquals(2, $result['success']);
        $this->assertCount(1, $result['errors']);
        $this->assertArrayHasKey('telegram_bot_token', $result['errors']);
    }
}
