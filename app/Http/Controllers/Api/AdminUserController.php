<?php

namespace App\Http\Controllers\Api;

use App\Models\AdminUser;
use App\Models\AdminSession;
use App\Models\AdminActionLog;
use App\Services\AdminAuthService;
use Illuminate\Support\Carbon;

/**
 * Admin User API Controller
 * 
 * Handles CRUD operations for admin users with RBAC enforcement.
 * Only super_admin can manage users.
 */
class AdminUserController extends BaseApiController
{
    private $authService;
    
    public function __construct()
    {
        parent::__construct();
        $this->authService = new AdminAuthService();
    }
    
    /**
     * Handle GET request - retrieve admin users
     * 
     * @return void
     */
    protected function handleGet()
    {
        // Require super_admin authentication
        $this->requireSuperAdmin();
        
        // Special endpoint: check if any users exist (for onboarding)
        if ($this->query('action') === 'check_users_exist') {
            $count = AdminUser::count();
            $this->success([
                'exists' => $count > 0,
                'count' => $count
            ]);
        }
        
        // Special endpoint: get audit history for a user
        if ($this->query('action') === 'audit_history' && $this->query('user_id')) {
            $userId = $this->validateId($this->query('user_id'), 'user');
            $logs = AdminActionLog::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();
            
            $this->success([
                'audit_logs' => $logs->toArray()
            ]);
        }
        
        // Get single user by ID
        if ($this->query('id')) {
            $id = $this->validateId($this->query('id'), 'user');
            $user = AdminUser::findOrFail($id);
            
            // Include related data
            $userData = $user->toArray();
            $userData['active_sessions_count'] = AdminSession::byUser($user->id)
                ->where('expires_at', '>', Carbon::now())
                ->count();
            
            $this->success(['user' => $userData]);
        }
        
        // Get all users with filters
        $query = AdminUser::query();
        
        // Apply filters
        if ($this->query('role')) {
            $query->byRole($this->query('role'));
        }
        
        if ($this->query('status')) {
            $query->where('status', $this->query('status'));
        }
        
        if ($this->query('search')) {
            $search = '%' . $this->query('search') . '%';
            $query->where(function($q) use ($search) {
                $q->where('email', 'like', $search)
                  ->orWhere('name', 'like', $search);
            });
        }
        
        // Order by created_at desc by default
        $query->orderBy('created_at', 'desc');
        
        // Apply pagination
        $result = $this->paginate($query, $this->query);
        
        // Add active session counts
        $users = array_map(function($user) {
            $user['active_sessions_count'] = AdminSession::byUser($user['id'])
                ->where('expires_at', '>', Carbon::now())
                ->count();
            return $user;
        }, $result['data']);
        
        $this->success(
            ['users' => $users],
            $result['meta']
        );
    }
    
    /**
     * Handle POST request - create user
     * 
     * @return void
     */
    protected function handlePost()
    {
        // Check if this is onboarding (no users exist)
        $isOnboarding = AdminUser::count() === 0;
        
        if (!$isOnboarding) {
            // Require super_admin authentication for normal user creation
            $this->requireSuperAdmin(true);
        }
        
        // Apply rate limiting
        $this->rateLimit('admin_users_create');
        
        // Validate required fields
        $errors = $this->validate($this->input, [
            'email' => 'required|email|max:255',
            'name' => 'required|string|min:1|max:255',
            'password' => 'required|string|min:8|max:255',
            'role' => 'required|in:super_admin,admin,editor'
        ]);
        
        if (!empty($errors)) {
            \ApiLogger::validationError('POST /api/admin/users.php', $errors);
            $this->validationError('Validation failed', $errors);
        }
        
        // Validate password complexity
        $passwordError = $this->validatePasswordComplexity($this->input['password']);
        if ($passwordError) {
            $this->validationError($passwordError);
        }
        
        // Check email uniqueness
        $email = strtolower(trim($this->input['email']));
        if (AdminUser::byEmail($email)->exists()) {
            $this->validationError('Email already exists');
        }
        
        // Create user
        $user = new AdminUser();
        $user->email = $email;
        $user->name = trim($this->input['name']);
        $user->setPassword($this->input['password']);
        $user->role = $this->input['role'];
        $user->status = $this->input['status'] ?? AdminUser::STATUS_ACTIVE;
        $user->save();
        
        // Log action (skip if onboarding)
        if (!$isOnboarding) {
            $currentUser = $this->getCurrentUser();
            $this->authService->logAction(
                $currentUser->id,
                AdminActionLog::ACTION_CREATE,
                'admin_user',
                $user->id,
                [
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role,
                    'status' => $user->status
                ]
            );
        }
        
        \ApiLogger::info("Admin user created successfully", [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'onboarding' => $isOnboarding
        ]);
        
        $this->created([
            'id' => $user->id,
            'message' => 'User created successfully',
            'onboarding' => $isOnboarding
        ]);
    }
    
    /**
     * Handle PUT request - update user
     * 
     * @return void
     */
    protected function handlePut()
    {
        // Require super_admin authentication and CSRF
        $this->requireSuperAdmin(true);
        
        // Apply rate limiting
        $this->rateLimit('admin_users_update');
        
        // Validate ID
        if (empty($this->input['id'])) {
            $this->validationError('User ID is required');
        }
        
        $id = $this->validateId($this->input['id'], 'user');
        
        // Find user
        $user = AdminUser::findOrFail($id);
        $currentUser = $this->getCurrentUser();
        
        // Prevent users from demoting themselves or deactivating themselves
        if ($user->id === $currentUser->id) {
            if (isset($this->input['role']) && $this->input['role'] !== $user->role) {
                $this->error('Cannot change your own role', 403);
            }
            if (isset($this->input['status']) && $this->input['status'] !== AdminUser::STATUS_ACTIVE) {
                $this->error('Cannot deactivate your own account', 403);
            }
        }
        
        $changedFields = [];
        $forceLogout = false;
        
        // Update email
        if (isset($this->input['email'])) {
            $email = strtolower(trim($this->input['email']));
            if ($email !== $user->email) {
                // Check uniqueness
                if (AdminUser::byEmail($email)->where('id', '!=', $user->id)->exists()) {
                    $this->validationError('Email already exists');
                }
                $user->email = $email;
                $changedFields['email'] = $email;
            }
        }
        
        // Update name
        if (isset($this->input['name'])) {
            $name = trim($this->input['name']);
            if ($name !== $user->name) {
                $user->name = $name;
                $changedFields['name'] = $name;
            }
        }
        
        // Update password
        if (!empty($this->input['password'])) {
            $passwordError = $this->validatePasswordComplexity($this->input['password']);
            if ($passwordError) {
                $this->validationError($passwordError);
            }
            
            $user->setPassword($this->input['password']);
            $changedFields['password'] = '***';
            $forceLogout = true;
        }
        
        // Update role
        if (isset($this->input['role'])) {
            if (!in_array($this->input['role'], [
                AdminUser::ROLE_SUPER_ADMIN,
                AdminUser::ROLE_ADMIN,
                AdminUser::ROLE_EDITOR
            ])) {
                $this->validationError('Invalid role');
            }
            
            if ($this->input['role'] !== $user->role) {
                $user->role = $this->input['role'];
                $changedFields['role'] = $this->input['role'];
                $forceLogout = true;
            }
        }
        
        // Update status
        if (isset($this->input['status'])) {
            if (!in_array($this->input['status'], [
                AdminUser::STATUS_ACTIVE,
                AdminUser::STATUS_INACTIVE,
                AdminUser::STATUS_LOCKED
            ])) {
                $this->validationError('Invalid status');
            }
            
            if ($this->input['status'] !== $user->status) {
                $user->status = $this->input['status'];
                $changedFields['status'] = $this->input['status'];
                
                // Force logout if deactivated or locked
                if ($this->input['status'] !== AdminUser::STATUS_ACTIVE) {
                    $forceLogout = true;
                }
            }
        }
        
        $user->save();
        
        // Force logout if needed
        if ($forceLogout) {
            $this->authService->destroyAllUserSessions($user->id);
        }
        
        // Log action
        $this->authService->logAction(
            $currentUser->id,
            AdminActionLog::ACTION_UPDATE,
            'admin_user',
            $user->id,
            [
                'changed_fields' => $changedFields,
                'force_logout' => $forceLogout
            ]
        );
        
        \ApiLogger::info("Admin user updated successfully", [
            'user_id' => $id,
            'changed_fields' => array_keys($changedFields),
            'force_logout' => $forceLogout
        ]);
        
        $this->success([
            'message' => 'User updated successfully',
            'user_id' => $id,
            'force_logout' => $forceLogout
        ]);
    }
    
    /**
     * Handle DELETE request - delete user
     * 
     * @return void
     */
    protected function handleDelete()
    {
        // Require super_admin authentication and CSRF
        $this->requireSuperAdmin(true);
        
        // Apply rate limiting
        $this->rateLimit('admin_users_delete');
        
        // Validate ID
        if (empty($this->query('id'))) {
            $this->validationError('User ID is required');
        }
        
        $id = $this->validateId($this->query('id'), 'user');
        $currentUser = $this->getCurrentUser();
        
        // Prevent self-deletion
        if ($id === $currentUser->id) {
            $this->error('Cannot delete your own account', 403);
        }
        
        // Find user
        $user = AdminUser::findOrFail($id);
        
        // Store user info for logging
        $userInfo = [
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role
        ];
        
        // Destroy all user sessions
        $this->authService->destroyAllUserSessions($user->id);
        
        // Delete user
        $user->delete();
        
        // Log action
        $this->authService->logAction(
            $currentUser->id,
            AdminActionLog::ACTION_DELETE,
            'admin_user',
            $id,
            $userInfo
        );
        
        \ApiLogger::info("Admin user deleted successfully", [
            'user_id' => $id,
            'email' => $userInfo['email']
        ]);
        
        $this->success([
            'message' => 'User deleted successfully',
            'user_id' => $id
        ]);
    }
    
    /**
     * Require super admin authentication
     * 
     * @param bool $requireCsrf
     * @return void
     */
    private function requireSuperAdmin($requireCsrf = false)
    {
        $this->requireAuth($requireCsrf);
        
        $user = $this->getCurrentUser();
        if (!$user || !$user->isSuperAdmin()) {
            \ApiLogger::warning('Unauthorized access attempt to admin users API', [
                'user_id' => $user ? $user->id : null,
                'role' => $user ? $user->role : null
            ]);
            $this->error('Only super administrators can manage users', 403);
        }
    }
    
    /**
     * Get currently authenticated user
     * 
     * @return AdminUser|null
     */
    private function getCurrentUser()
    {
        $sessionId = session_id();
        if (empty($sessionId)) {
            return null;
        }
        
        $validation = $this->authService->validateSession($sessionId);
        return $validation['valid'] ? $validation['user'] : null;
    }
    
    /**
     * Validate password complexity
     * 
     * @param string $password
     * @return string|null Error message or null if valid
     */
    private function validatePasswordComplexity($password)
    {
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters long';
        }
        
        if (strlen($password) > 255) {
            return 'Password must be less than 255 characters';
        }
        
        // Require at least one letter and one number
        if (!preg_match('/[a-zA-Z]/', $password)) {
            return 'Password must contain at least one letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            return 'Password must contain at least one number';
        }
        
        return null;
    }
    
    /**
     * Get resource name for logging
     * 
     * @return string
     */
    protected function getResourceName()
    {
        return 'admin_users';
    }
}
