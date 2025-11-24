<?php

namespace App\Http\Controllers\Api;

use App\Models\Testimonial;
use App\Services\MediaUploadService;

/**
 * Testimonial API Controller
 * 
 * Handles CRUD operations for testimonials using Eloquent ORM.
 * Supports media uploads for customer avatars.
 */
class TestimonialController extends BaseApiController
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
     * Handle GET request - retrieve testimonials
     * 
     * @return void
     */
    protected function handleGet()
    {
        // Get single testimonial by ID or slug
        if ($this->query('id')) {
            $id = $this->validateId($this->query('id'), 'testimonial');
            $testimonial = Testimonial::findOrFail($id);
            
            // Set cache headers
            $this->cacheService->setCacheHeaders($testimonial->updated_at);
            
            $this->success(['testimonial' => $this->formatTestimonial($testimonial)]);
        }
        
        if ($this->query('slug')) {
            $testimonial = Testimonial::where('slug', $this->query('slug'))->firstOrFail();
            
            // Set cache headers
            $this->cacheService->setCacheHeaders($testimonial->updated_at);
            
            $this->success(['testimonial' => $this->formatTestimonial($testimonial)]);
        }
        
        // Get all testimonials with filters
        $query = Testimonial::query();
        
        // Apply filters
        if ($this->query('active') !== null) {
            $active = $this->query('active') === 'true' || $this->query('active') === '1';
            $query->where('active', $active);
        }
        
        if ($this->query('approved') !== null) {
            $approved = $this->query('approved') === 'true' || $this->query('approved') === '1';
            $query->where('approved', $approved);
        }
        
        if ($this->query('featured') !== null) {
            $featured = $this->query('featured') === 'true' || $this->query('featured') === '1';
            $query->where('featured', $featured);
        }
        
        if ($this->query('min_rating')) {
            $minRating = (int)$this->query('min_rating');
            $query->minRating($minRating);
        }
        
        // Order by sort_order
        $query->ordered();
        
        // Apply pagination
        $result = $this->paginate($query, $this->query);
        
        // Format testimonials with avatar URLs
        $formattedTestimonials = array_map(function($item) {
            return $this->formatTestimonial(Testimonial::make((array)$item));
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
            ['testimonials' => $formattedTestimonials],
            $result['meta']
        );
    }
    
    /**
     * Handle POST request - create testimonial
     * 
     * @return void
     */
    protected function handlePost()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('testimonials_create');
        
        // Check if this is multipart/form-data (file upload)
        $hasFile = !empty($_FILES['avatar']);
        
        if ($hasFile) {
            // Handle multipart form data
            $data = $_POST;
            
            // Validate required fields
            $errors = $this->validate($data, [
                'name' => 'required|string|min:1|max:255',
                'text' => 'required|string|min:1'
            ]);
            
            if (!empty($errors)) {
                \ApiLogger::validationError('POST /api/testimonials.php', $errors);
                $this->validationError('Validation failed', $errors);
            }
            
            // Upload avatar
            try {
                $uploadResult = $this->mediaService->upload($_FILES['avatar'], MediaUploadService::TYPE_TESTIMONIAL);
                $data['avatar_path'] = $uploadResult['path'];
                $data['avatar'] = $uploadResult['url'];
                $data['avatar_size'] = $uploadResult['size'];
                $data['avatar_mime'] = $uploadResult['mime'];
            } catch (\Exception $e) {
                \ApiLogger::error('Testimonial avatar upload failed', [
                    'error' => $e->getMessage()
                ]);
                $this->validationError('Avatar upload failed: ' . $e->getMessage());
            }
        } else {
            // Handle JSON data
            $data = $this->input;
            
            // Validate required fields
            $errors = $this->validate($data, [
                'name' => 'required|string|min:1|max:255',
                'text' => 'required|string|min:1'
            ]);
            
            if (!empty($errors)) {
                \ApiLogger::validationError('POST /api/testimonials.php', $errors);
                $this->validationError('Validation failed', $errors);
            }
        }
        
        // Generate unique slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], Testimonial::class);
        } else {
            $data['slug'] = $this->generateUniqueSlug($data['slug'], Testimonial::class);
        }
        
        // Create testimonial
        $testimonial = Testimonial::create($data);
        
        // Invalidate cache
        $this->invalidateResourceCache();
        
        \ApiLogger::info("Testimonial created successfully", [
            'testimonial_id' => $testimonial->id,
            'name' => $testimonial->name
        ]);
        
        $this->created([
            'id' => $testimonial->id,
            'message' => 'Testimonial created successfully'
        ]);
    }
    
    /**
     * Handle PUT request - update testimonial
     * 
     * @return void
     */
    protected function handlePut()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('testimonials_update');
        
        // Validate ID
        if (empty($this->input['id'])) {
            $this->validationError('Testimonial ID is required');
        }
        
        $id = $this->validateId($this->input['id'], 'testimonial');
        
        // Find testimonial
        $testimonial = Testimonial::findOrFail($id);
        
        // Remove ID from update data
        $data = $this->input;
        unset($data['id']);
        
        // Handle slug update if provided
        if (isset($data['slug']) && $data['slug'] !== $testimonial->slug) {
            $data['slug'] = $this->generateUniqueSlug($data['slug'], Testimonial::class, $id);
        }
        
        // Update testimonial
        $testimonial->update($data);
        
        // Invalidate cache
        $this->invalidateResourceCache();
        
        \ApiLogger::info("Testimonial updated successfully", [
            'testimonial_id' => $id,
            'updated_fields' => array_keys($data)
        ]);
        
        $this->success([
            'message' => 'Testimonial updated successfully',
            'testimonial_id' => $id
        ]);
    }
    
    /**
     * Handle DELETE request - delete testimonial
     * 
     * @return void
     */
    protected function handleDelete()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('testimonials_delete');
        
        // Validate ID
        if (empty($this->query('id'))) {
            $this->validationError('Testimonial ID is required');
        }
        
        $id = $this->validateId($this->query('id'), 'testimonial');
        
        // Find testimonial
        $testimonial = Testimonial::findOrFail($id);
        
        // Delete associated avatar file if exists
        if (!empty($testimonial->avatar_path)) {
            $this->mediaService->delete($testimonial->avatar_path);
        }
        
        // Delete testimonial
        $testimonial->delete();
        
        // Invalidate cache
        $this->invalidateResourceCache();
        
        \ApiLogger::info("Testimonial deleted successfully", ['testimonial_id' => $id]);
        
        $this->success([
            'message' => 'Testimonial deleted successfully',
            'testimonial_id' => $id
        ]);
    }
    
    /**
     * Format testimonial with full avatar URLs
     * 
     * @param Testimonial $testimonial
     * @return array
     */
    private function formatTestimonial($testimonial)
    {
        $data = $testimonial->toArray();
        
        // Add full avatar URL if path exists
        if (!empty($testimonial->avatar_path)) {
            $data['avatar'] = $this->mediaService->getUrl($testimonial->avatar_path);
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
        return 'testimonials';
    }
}
