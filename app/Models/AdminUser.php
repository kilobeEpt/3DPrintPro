<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class AdminUser extends Model
{
    protected $table = 'admin_users';
    
    public $timestamps = true;
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    
    protected $fillable = [
        'email',
        'name',
        'password_hash',
        'role',
        'status',
        'last_login_at',
        'last_login_ip',
        'failed_login_attempts',
        'locked_until',
        'remember_token',
    ];
    
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'failed_login_attempts' => 'integer',
    ];
    
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_EDITOR = 'editor';
    
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_LOCKED = 'locked';
    
    public function sessions()
    {
        return $this->hasMany(AdminSession::class, 'user_id');
    }
    
    public function loginAttempts()
    {
        return $this->hasMany(AdminLoginAttempt::class, 'email', 'email');
    }
    
    public function actionLogs()
    {
        return $this->hasMany(AdminActionLog::class, 'user_id');
    }
    
    public function setPassword($password)
    {
        $this->password_hash = password_hash($password, PASSWORD_BCRYPT);
    }
    
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password_hash);
    }
    
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }
    
    public function isLocked()
    {
        if ($this->status === self::STATUS_LOCKED) {
            return true;
        }
        
        if ($this->locked_until && $this->locked_until->isFuture()) {
            return true;
        }
        
        return false;
    }
    
    public function isSuperAdmin()
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }
    
    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN || $this->isSuperAdmin();
    }
    
    public function hasRole($role)
    {
        return $this->role === $role;
    }
    
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
    
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }
    
    public function scopeByEmail($query, $email)
    {
        return $query->where('email', $email);
    }
    
    public function incrementFailedAttempts()
    {
        $this->failed_login_attempts++;
        $this->save();
    }
    
    public function resetFailedAttempts()
    {
        $this->failed_login_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }
    
    public function lockAccount($minutes = 15)
    {
        $this->locked_until = Carbon::now()->addMinutes($minutes);
        $this->save();
    }
    
    public function updateLastLogin($ipAddress)
    {
        $this->last_login_at = Carbon::now();
        $this->last_login_ip = $ipAddress;
        $this->save();
    }
}
