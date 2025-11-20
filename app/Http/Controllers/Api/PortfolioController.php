<?php

namespace App\Http\Controllers\Api;

use App\Models\Portfolio;
use App\Services\MediaUploadService;

/**
 * Portfolio API Controller
 * 
 * Handles CRUD operations for portfolio items using Eloquent ORM.
 * Supports media uploads for portfolio images.
 */
class PortfolioController extends BaseApiController
{
    /**
     * Media upload service
     * 
     * @var MediaUploadService
     */
    private $mediaService;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->mediaService = new MediaUploadService();
    }
    
    /**
     * Handle GET request - retrieve portfolio items
     * 
     * @return void
     */
    protected function handleGet()
    {
        // Get single portfolio item by ID or slug
        if ($this->query('id')) {
            $id = $this->validateId($this->query('id'), 'portfolio item');
            $item = Portfolio::findOrFail($id);
            
            // Set cache headers
            $this->cacheService->setCacheHeaders($item->updated_at);
            
            $this->success(['item' => $this->formatPortfolioItem($item)]);
        }
        
        if ($this->query('slug')) {
            $item = Portfolio::where('slug', $this->query('slug'))->firstOrFail();
            
            // Set cache headers
            $this->cacheService->setCacheHeaders($item->updated_at);
            
            $this->success(['item' => $this->formatPortfolioItem($item)]);
        }
        
        // Get all portfolio items with filters
        $query = Portfolio::query();
        
        // Apply filters
        if ($this->query('active') !== null) {
            $active = $this->query('active') === 'true' || $this->query('active') === '1';
            $query->where('active', $active);
        }
        
        if ($this->query('featured') !== null) {
            $featured = $this->query('featured') === 'true' || $this->query('featured') === '1';
            $query->where('featured', $featured);
        }
        
        if ($this->query('category')) {
            $query->where('category', $this->query('category'));
        }
        
        // Order by sort_order
        $query->ordered();
        
        // Apply pagination
        $result = $this->paginate($query, $this->query);
        
        // Format items with image URLs
        $formattedItems = array_map(function($item) {
            return $this->formatPortfolioItem(Portfolio::make((array)$item));
        }, $result['data']);
        
        // Set cache headers based on latest updated_at
        if (!empty($result['data'])) {
            $collection = collect($result['data']);
            $latestTimestamp = $this->cacheService->getLatestTimestamp($collection);
            if ($latestTimestamp) {
                $this->cacheService->setCacheHeaders($latestTimestamp);
            }
        }
        
        $this->success(
            ['items' => $formattedItems],
            $result['meta']
        );
    }
    
    /**
     * Handle POST request - create portfolio item
     * 
     * @return void
     */
    protected function handlePost()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('portfolio_create');
        
        // Check if this is multipart/form-data (file upload)
        $hasFile = !empty($_FILES['image']);
        
        if ($hasFile) {
            // Handle multipart form data
            $data = $_POST;
            
            // Validate required fields
            $errors = $this->validate($data, [
                'title' => 'required|string|min:1|max:255'
            ]);
            
            if (!empty($errors)) {
                \ApiLogger::validationError('POST /api/portfolio.php', $errors);
                $this->validationError('Validation failed', $errors);
            }
            
            // Upload image
            try {
                $uploadResult = $this->mediaService->upload($_FILES['image'], MediaUploadService::TYPE_PORTFOLIO);
                $data['image_path'] = $uploadResult['path'];
                $data['image_url'] = $uploadResult['url'];
                $data['image_size'] = $uploadResult['size'];
                $data['image_mime'] = $uploadResult['mime'];
            } catch (\Exception $e) {
                \ApiLogger::error('Portfolio image upload failed', [
                    'error' => $e->getMessage()
                ]);
                $this->validationError('Image upload failed: ' . $e->getMessage());
            }
        } else {
            // Handle JSON data
            $data = $this->input;
            
            // Validate required fields
            $errors = $this->validate($data, [
                'title' => 'required|string|min:1|max:255'
            ]);
            
            if (!empty($errors)) {
                \ApiLogger::validationError('POST /api/portfolio.php', $errors);
                $this->validationError('Validation failed', $errors);
            }
        }
        
        // Generate unique slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['title'], Portfolio::class);
        } else {
            $data['slug'] = $this->generateUniqueSlug($data['slug'], Portfolio::class);
        }
        
        // Parse JSON fields if they come as strings
        if (isset($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = json_decode($data['tags'], true);
        }
        
        // Create portfolio item
        $item = Portfolio::create($data);
        
        // Invalidate cache
        $this->invalidateResourceCache();
        
        \ApiLogger::info("Portfolio item created successfully", [
            'item_id' => $item->id,
            'title' => $item->title
        ]);
        
        $this->created([
            'id' => $item->id,
            'message' => 'Portfolio item created successfully'
        ]);
    }
    
    /**
     * Handle PUT request - update portfolio item
     * 
     * @return void
     */
    protected function handlePut()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('portfolio_update');
        
        // Validate ID
        if (empty($this->input['id'])) {
            $this->validationError('Portfolio item ID is required');
        }
        
        $id = $this->validateId($this->input['id'], 'portfolio item');
        
        // Find portfolio item
        $item = Portfolio::findOrFail($id);
        
        // Remove ID from update data
        $data = $this->input;
        unset($data['id']);
        
        // Handle slug update if provided
        if (isset($data['slug']) && $data['slug'] !== $item->slug) {
            $data['slug'] = $this->generateUniqueSlug($data['slug'], Portfolio::class, $id);
        }
        
        // Update portfolio item
        $item->update($data);
        
        // Invalidate cache
        $this->invalidateResourceCache();
        
        \ApiLogger::info("Portfolio item updated successfully", [
            'item_id' => $id,
            'updated_fields' => array_keys($data)
        ]);
        
        $this->success([
            'message' => 'Portfolio item updated successfully',
            'item_id' => $id
        ]);
    }
    
    /**
     * Handle DELETE request - delete portfolio item
     * 
     * @return void
     */
    protected function handleDelete()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('portfolio_delete');
        
        // Validate ID
        if (empty($this->query('id'))) {
            $this->validationError('Portfolio item ID is required');
        }
        
        $id = $this->validateId($this->query('id'), 'portfolio item');
        
        // Find portfolio item
        $item = Portfolio::findOrFail($id);
        
        // Delete associated image file if exists
        if (!empty($item->image_path)) {
            $this->mediaService->delete($item->image_path);
        }
        
        // Delete portfolio item
        $item->delete();
        
        // Invalidate cache
        $this->invalidateResourceCache();
        
        \ApiLogger::info("Portfolio item deleted successfully", ['item_id' => $id]);
        
        $this->success([
            'message' => 'Portfolio item deleted successfully',
            'item_id' => $id
        ]);
    }
    
    /**
     * Format portfolio item with full image URLs
     * 
     * @param Portfolio $item
     * @return array
     */
    private function formatPortfolioItem($item)
    {
        $data = $item->toArray();
        
        // Add full image URL if path exists
        if (!empty($item->image_path)) {
            $data['image_url'] = $this->mediaService->getUrl($item->image_path);
        }
        
        return $data;
    }
    
    /**
     * Get resource name for logging
     * 
     * @return string
     */
    protected function getResourceName()
    {
        return 'portfolio';
    }
}
