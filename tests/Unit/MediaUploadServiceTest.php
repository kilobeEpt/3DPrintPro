<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\MediaUploadService;

/**
 * Media Upload Service Tests
 * 
 * Tests file upload validation, secure filename generation,
 * MIME type validation, size limits, and error handling.
 */
class MediaUploadServiceTest extends TestCase
{
    private $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MediaUploadService();
    }
    
    public function testServiceConstants()
    {
        $this->assertIsArray(MediaUploadService::ALLOWED_MIME_TYPES);
        $this->assertContains('image/jpeg', MediaUploadService::ALLOWED_MIME_TYPES);
        $this->assertContains('image/png', MediaUploadService::ALLOWED_MIME_TYPES);
        $this->assertEquals(5242880, MediaUploadService::MAX_FILE_SIZE); // 5MB
    }
    
    public function testGetUrlGeneratesCorrectUrl()
    {
        $path = 'portfolio/test.jpg';
        $url = $this->service->getUrl($path);
        
        $this->assertEquals('https://3dprint-omsk.ru/storage/uploads/portfolio/test.jpg', $url);
    }
    
    public function testGetUrlReturnsNullForEmptyPath()
    {
        $url = $this->service->getUrl('');
        $this->assertNull($url);
        
        $url = $this->service->getUrl(null);
        $this->assertNull($url);
    }
    
    public function testExistsReturnsFalseForNonExistentFile()
    {
        $exists = $this->service->exists('portfolio/nonexistent.jpg');
        $this->assertFalse($exists);
    }
    
    public function testExistsReturnsFalseForEmptyPath()
    {
        $exists = $this->service->exists('');
        $this->assertFalse($exists);
        
        $exists = $this->service->exists(null);
        $this->assertFalse($exists);
    }
    
    public function testDeleteReturnsFalseForNonExistentFile()
    {
        $deleted = $this->service->delete('portfolio/nonexistent.jpg');
        $this->assertFalse($deleted);
    }
    
    public function testDeleteReturnsFalseForEmptyPath()
    {
        $deleted = $this->service->delete('');
        $this->assertFalse($deleted);
        
        $deleted = $this->service->delete(null);
        $this->assertFalse($deleted);
    }
    
    public function testUploadFailsWithoutFile()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No file was uploaded');
        
        $this->service->upload(['tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE], MediaUploadService::TYPE_PORTFOLIO);
    }
    
    public function testUploadFailsWithInvalidType()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid upload type');
        
        // Create a mock file array that passes initial checks
        $mockFile = [
            'tmp_name' => tempnam(sys_get_temp_dir(), 'test'),
            'error' => UPLOAD_ERR_OK,
            'size' => 1024,
            'name' => 'test.jpg'
        ];
        
        // Write some content
        file_put_contents($mockFile['tmp_name'], 'test content');
        
        try {
            $this->service->upload($mockFile, 'invalid_type');
        } finally {
            // Cleanup
            if (file_exists($mockFile['tmp_name'])) {
                unlink($mockFile['tmp_name']);
            }
        }
    }
    
    public function testUploadFailsWithOversizedFile()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File size exceeds maximum allowed size');
        
        // Create a mock file that's too large
        $mockFile = [
            'tmp_name' => tempnam(sys_get_temp_dir(), 'test'),
            'error' => UPLOAD_ERR_OK,
            'size' => MediaUploadService::MAX_FILE_SIZE + 1, // Over limit
            'name' => 'test.jpg'
        ];
        
        // Write some content
        file_put_contents($mockFile['tmp_name'], 'test content');
        
        try {
            $this->service->upload($mockFile, MediaUploadService::TYPE_PORTFOLIO);
        } finally {
            // Cleanup
            if (file_exists($mockFile['tmp_name'])) {
                unlink($mockFile['tmp_name']);
            }
        }
    }
}
