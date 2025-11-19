<?php

namespace App\Services;

/**
 * Media Upload Service
 * 
 * Handles file uploads with validation, secure storage, and metadata tracking.
 * Supports portfolio images and testimonial avatars.
 */
class MediaUploadService
{
    const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];
    
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    const MAX_FILE_SIZE = 5242880; // 5MB in bytes
    
    const STORAGE_BASE_PATH = __DIR__ . '/../../storage/uploads';
    const STORAGE_BASE_URL = 'https://3dprint-omsk.ru/storage/uploads';
    
    const TYPE_PORTFOLIO = 'portfolio';
    const TYPE_TESTIMONIAL = 'testimonials';
    
    /**
     * Upload a file and return metadata
     * 
     * @param array $file $_FILES array element
     * @param string $type Upload type (portfolio or testimonials)
     * @return array ['path' => string, 'url' => string, 'size' => int, 'mime' => string]
     * @throws \Exception On validation or upload failure
     */
    public function upload($file, $type = self::TYPE_PORTFOLIO)
    {
        // Validate file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \Exception('No file was uploaded or invalid upload');
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception($this->getUploadErrorMessage($file['error']));
        }
        
        // Validate file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            throw new \Exception('File size exceeds maximum allowed size of ' . $this->formatBytes(self::MAX_FILE_SIZE));
        }
        
        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw new \Exception('Invalid file type. Allowed types: ' . implode(', ', self::ALLOWED_MIME_TYPES));
        }
        
        // Validate extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new \Exception('Invalid file extension. Allowed extensions: ' . implode(', ', self::ALLOWED_EXTENSIONS));
        }
        
        // Validate type
        if (!in_array($type, [self::TYPE_PORTFOLIO, self::TYPE_TESTIMONIAL])) {
            throw new \Exception('Invalid upload type');
        }
        
        // Generate secure filename
        $filename = $this->generateSecureFilename($extension);
        
        // Determine storage path
        $storagePath = self::STORAGE_BASE_PATH . '/' . $type;
        $relativePath = $type . '/' . $filename;
        $absolutePath = $storagePath . '/' . $filename;
        
        // Ensure directory exists
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            throw new \Exception('Failed to move uploaded file to destination');
        }
        
        // Generate public URL
        $url = self::STORAGE_BASE_URL . '/' . $relativePath;
        
        return [
            'path' => $relativePath,
            'url' => $url,
            'size' => $file['size'],
            'mime' => $mimeType,
        ];
    }
    
    /**
     * Delete a file from storage
     * 
     * @param string $relativePath Relative path from storage root
     * @return bool Success status
     */
    public function delete($relativePath)
    {
        if (empty($relativePath)) {
            return false;
        }
        
        $absolutePath = self::STORAGE_BASE_PATH . '/' . $relativePath;
        
        if (file_exists($absolutePath) && is_file($absolutePath)) {
            return unlink($absolutePath);
        }
        
        return false;
    }
    
    /**
     * Check if a file exists in storage
     * 
     * @param string $relativePath Relative path from storage root
     * @return bool
     */
    public function exists($relativePath)
    {
        if (empty($relativePath)) {
            return false;
        }
        
        $absolutePath = self::STORAGE_BASE_PATH . '/' . $relativePath;
        return file_exists($absolutePath) && is_file($absolutePath);
    }
    
    /**
     * Get full URL for a relative path
     * 
     * @param string $relativePath Relative path from storage root
     * @return string Full URL
     */
    public function getUrl($relativePath)
    {
        if (empty($relativePath)) {
            return null;
        }
        
        return self::STORAGE_BASE_URL . '/' . $relativePath;
    }
    
    /**
     * Generate a secure filename using hash and timestamp
     * 
     * @param string $extension File extension
     * @return string Secure filename
     */
    private function generateSecureFilename($extension)
    {
        $hash = bin2hex(random_bytes(16));
        $timestamp = time();
        return $timestamp . '_' . $hash . '.' . $extension;
    }
    
    /**
     * Get human-readable upload error message
     * 
     * @param int $errorCode PHP upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive in HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by PHP extension';
            default:
                return 'Unknown upload error';
        }
    }
    
    /**
     * Format bytes to human-readable size
     * 
     * @param int $bytes Size in bytes
     * @return string Formatted size
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
