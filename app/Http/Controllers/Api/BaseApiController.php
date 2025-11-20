<?php

namespace App\Http\Controllers\Api;

use App\Http\Traits\PaginationTrait;
use App\Http\Traits\ValidatesRequests;
use App\Http\Traits\ManagesSlugs;
use App\Services\ContentCacheService;
use App\Services\SSEBroadcaster;

/**
 * Base API Controller
 * 
 * Provides common functionality for all API controllers:
 * - CORS handling (via SecurityHeaders)
 * - JSON response formatting (via ApiResponse)
 * - Exception handling
 * - Request parsing
 * - Validation
 * - Authentication hooks
 * - Slug management
 * - Cache headers and invalidation
 * - SSE event broadcasting
 */
abstract class BaseApiController
{
    use PaginationTrait, ValidatesRequests, ManagesSlugs;
    
    /**
     * Rate limiter instance
     * 
     * @var \RateLimiter
     */
    protected $rateLimiter;
    
    /**
     * Cache service instance
     * 
     * @var ContentCacheService
     */
    protected $cacheService;
    
    /**
     * SSE broadcaster instance
     * 
     * @var SSEBroadcaster
     */
    protected $sseBroadcaster;
    
    /**
     * Request method
     * 
     * @var string
     */
    protected $method;
    
    /**
     * Request input data
     * 
     * @var array
     */
    protected $input;
    
    /**
     * Query parameters
     * 
     * @var array
     */
    protected $query;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        // Initialize rate limiter with appropriate profile
        $profile = $this->getRateLimitProfile();
        $this->rateLimiter = new \RateLimiter($profile);
        
        $this->cacheService = new ContentCacheService();
        $this->sseBroadcaster = new SSEBroadcaster();
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->query = $_GET;
        $this->input = $this->parseInput();
    }
    
    /**
     * Get rate limit profile for this controller
     * Override in child controllers for custom profiles
     * 
     * @return string
     */
    protected function getRateLimitProfile()
    {
        // Read operations get higher limits
        if ($this->method === 'GET') {
            return \RateLimiter::PROFILE_API_READ;
        }
        
        // Write operations (POST/PUT/PATCH/DELETE) get stricter limits
        return \RateLimiter::PROFILE_API_WRITE;
    }
    
    /**
     * Apply rate limiting to current request
     * Call this in controller actions that need rate limiting
     * 
     * @param string|null $endpoint Optional endpoint identifier
     * @return void
     */
    protected function applyRateLimit($endpoint = null)
    {
        $this->rateLimiter->apply($endpoint);
    }
    
    /**
     * Broadcast SSE event for content changes
     * 
     * @param string $action (created, updated, deleted)
     * @param mixed $entityId
     * @return void
     */
    protected function broadcastContentChange($action, $entityId = null)
    {
        $this->sseBroadcaster->broadcastContentUpdate(
            $this->getResourceName(),
            $entityId,
            $action
        );
    }
    
    /**
     * Invalidate cache and broadcast SSE event
     * 
     * @param string $resourceType Resource type (defaults to controller's resource name)
     * @return void
     */
    protected function invalidateResourceCache($resourceType = null)
    {
        $resource = $resourceType ?? $this->getResourceName();
        
        // Invalidate cache
        $this->cacheService->invalidateCache($resource);
        
        // Broadcast SSE event
        $this->sseBroadcaster->broadcastCacheInvalidation($resource);
        
        \ApiLogger::info("Cache invalidated for resource: {$resource}");
    }
    
    /**
     * Parse request input based on method
     * 
     * @return array
     */
    protected function parseInput()
    {
        if (in_array($this->method, ['POST', 'PUT', 'PATCH'])) {
            $rawInput = file_get_contents('php://input');
            $decoded = json_decode($rawInput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE && !empty($rawInput)) {
                \ApiLogger::warning('Invalid JSON in request', [
                    'method' => $this->method,
                    'raw_input' => substr($rawInput, 0, 200)
                ]);
                \ApiResponse::validationError('Invalid JSON data');
            }
            
            return $decoded ?? [];
        }
        
        return [];
    }
    
    /**
     * Get request input
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    protected function input($key = null, $default = null)
    {
        if ($key === null) {
            return $this->input;
        }
        
        return $this->input[$key] ?? $default;
    }
    
    /**
     * Get query parameter
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    protected function query($key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }
        
        return $this->query[$key] ?? $default;
    }
    
    /**
     * Check if request is for a specific method
     * 
     * @param string $method
     * @return bool
     */
    protected function isMethod($method)
    {
        return strtoupper($this->method) === strtoupper($method);
    }
    
    /**
     * Require admin authentication
     * 
     * @param bool $requireCsrf Whether to require CSRF token
     * @return void
     */
    protected function requireAuth($requireCsrf = false)
    {
        if ($requireCsrf) {
            requireAdminAuthWithCsrf();
        } else {
            requireAdminAuth();
        }
    }
    
    /**
     * Apply rate limiting
     * 
     * @param string $key Rate limit key
     * @return void
     */
    protected function rateLimit($key)
    {
        $this->rateLimiter->apply($key);
    }
    
    /**
     * Handle the request and dispatch to appropriate method
     * 
     * @return void
     */
    public function handle()
    {
        try {
            switch ($this->method) {
                case 'GET':
                    $this->handleGet();
                    break;
                    
                case 'POST':
                    $this->handlePost();
                    break;
                    
                case 'PUT':
                    $this->handlePut();
                    break;
                    
                case 'DELETE':
                    $this->handleDelete();
                    break;
                    
                default:
                    \ApiLogger::warning("Method not allowed", ['method' => $this->method]);
                    \ApiResponse::methodNotAllowed();
                    break;
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \ApiLogger::warning("Resource not found", [
                'model' => $e->getModel(),
                'ids' => $e->getIds()
            ]);
            \ApiResponse::notFound('Resource not found');
        } catch (\PDOException $e) {
            \ApiLogger::dbError('QUERY', $this->getResourceName(), $e);
            \ApiResponse::serverError('Database error occurred. Please try again later.');
        } catch (\Exception $e) {
            \ApiLogger::error("Unexpected error in API endpoint", [
                'exception' => get_class($e),
                'message' => $e->getMessage()
            ]);
            \ApiResponse::serverError('An unexpected error occurred. Please try again later.');
        }
    }
    
    /**
     * Handle GET request
     * 
     * @return void
     */
    abstract protected function handleGet();
    
    /**
     * Handle POST request
     * 
     * @return void
     */
    abstract protected function handlePost();
    
    /**
     * Handle PUT request
     * 
     * @return void
     */
    abstract protected function handlePut();
    
    /**
     * Handle DELETE request
     * 
     * @return void
     */
    abstract protected function handleDelete();
    
    /**
     * Get the resource name for logging
     * 
     * @return string
     */
    abstract protected function getResourceName();
    
    /**
     * Success response with data
     * 
     * @param mixed $data
     * @param array $meta
     * @param int $statusCode
     * @return void
     */
    protected function success($data = null, $meta = [], $statusCode = 200)
    {
        \ApiResponse::success($data, $meta, $statusCode);
    }
    
    /**
     * Created response (201)
     * 
     * @param mixed $data
     * @param array $meta
     * @return void
     */
    protected function created($data = null, $meta = [])
    {
        \ApiResponse::created($data, $meta);
    }
    
    /**
     * Error response
     * 
     * @param string $message
     * @param int $statusCode
     * @return void
     */
    protected function error($message, $statusCode = 400)
    {
        \ApiResponse::error($message, $statusCode);
    }
    
    /**
     * Validation error response
     * 
     * @param string $message
     * @param array $errors
     * @return void
     */
    protected function validationError($message, $errors = [])
    {
        \ApiResponse::validationError($message, $errors);
    }
    
    /**
     * Not found response
     * 
     * @param string $message
     * @return void
     */
    protected function notFound($message = 'Resource not found')
    {
        \ApiResponse::notFound($message);
    }
}
