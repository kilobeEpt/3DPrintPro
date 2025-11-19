#!/usr/bin/env php
<?php
// ========================================
// Create Admin User
// CLI script to create a new admin user
// ========================================
//
// USAGE:
//   Interactive mode:
//     php scripts/create-admin.php
//
//   Non-interactive mode:
//     php scripts/create-admin.php <email> <name> <password> [role] [status]
//
//   Examples:
//     php scripts/create-admin.php admin@example.com "Admin User" MySecurePassword123
//     php scripts/create-admin.php editor@example.com "Editor" password123 editor active
//
// ROLES: super_admin, admin, editor (default: admin)
// STATUS: active, inactive (default: active)
//
// ========================================

if (php_sapi_name() !== 'cli') {
    die("⛔ This script can only be run from the command line.\n");
}

chdir(__DIR__ . '/..');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\AdminUser;

echo "========================================\n";
echo "  3D Print Pro - Create Admin User\n";
echo "========================================\n\n";

$email = null;
$name = null;
$password = null;
$role = 'admin';
$status = 'active';

if ($argc >= 4) {
    $email = trim($argv[1]);
    $name = trim($argv[2]);
    $password = $argv[3];
    $role = isset($argv[4]) ? trim($argv[4]) : 'admin';
    $status = isset($argv[5]) ? trim($argv[5]) : 'active';
    
    echo "📋 Using credentials from command line arguments\n\n";
} else {
    echo "Enter admin user details:\n\n";
    
    echo "Email: ";
    $email = trim(fgets(STDIN));
    
    echo "Full Name: ";
    $name = trim(fgets(STDIN));
    
    echo "Password (min 8 chars): ";
    $password = trim(fgets(STDIN));
    
    echo "Role [admin] (super_admin/admin/editor): ";
    $roleInput = trim(fgets(STDIN));
    $role = !empty($roleInput) ? $roleInput : 'admin';
    
    echo "Status [active] (active/inactive): ";
    $statusInput = trim(fgets(STDIN));
    $status = !empty($statusInput) ? $statusInput : 'active';
    
    echo "\n";
}

if (empty($email) || empty($name) || empty($password)) {
    die("❌ Error: Email, name, and password are required.\n");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("❌ Error: Invalid email format.\n");
}

if (strlen($password) < 8) {
    die("❌ Error: Password must be at least 8 characters long.\n");
}

$validRoles = ['super_admin', 'admin', 'editor'];
if (!in_array($role, $validRoles)) {
    die("❌ Error: Invalid role. Must be one of: " . implode(', ', $validRoles) . "\n");
}

$validStatuses = ['active', 'inactive'];
if (!in_array($status, $validStatuses)) {
    die("❌ Error: Invalid status. Must be one of: " . implode(', ', $validStatuses) . "\n");
}

try {
    $existingUser = AdminUser::byEmail($email)->first();
    
    if ($existingUser) {
        echo "⚠️  User with email '{$email}' already exists.\n\n";
        echo "Do you want to update their password and details? (yes/no) [no]: ";
        
        if ($argc < 4) {
            $confirm = trim(fgets(STDIN));
            if (strtolower($confirm) !== 'yes') {
                die("❌ Operation cancelled. User not modified.\n");
            }
        } else {
            echo "no (auto-declined in non-interactive mode)\n";
            die("❌ User already exists. Aborting.\n");
        }
        
        echo "\n🔐 Updating user...\n";
        
        $existingUser->name = $name;
        $existingUser->setPassword($password);
        $existingUser->role = $role;
        $existingUser->status = $status;
        $existingUser->save();
        
        echo "\n✅ SUCCESS! User has been updated.\n\n";
    } else {
        echo "🔐 Hashing password...\n";
        echo "💾 Creating user...\n";
        
        $user = new AdminUser();
        $user->email = $email;
        $user->name = $name;
        $user->setPassword($password);
        $user->role = $role;
        $user->status = $status;
        $user->save();
        
        echo "\n✅ SUCCESS! Admin user has been created.\n\n";
    }
    
    echo "========================================\n";
    echo "  User Details\n";
    echo "========================================\n";
    echo "Email:    {$email}\n";
    echo "Name:     {$name}\n";
    echo "Role:     {$role}\n";
    echo "Status:   {$status}\n";
    echo "Password: " . str_repeat('•', strlen($password)) . " (hidden)\n";
    echo "========================================\n\n";
    
    if ($role === 'super_admin') {
        echo "🔑 This user has SUPER ADMIN privileges.\n\n";
    }
    
    echo "🔗 Login URL: http://localhost/admin/login.php\n\n";
    echo "⚠️  IMPORTANT SECURITY NOTES:\n";
    echo "   1. Store these credentials in a secure location\n";
    echo "   2. Use a strong, unique password for production\n";
    echo "   3. Change default passwords immediately\n";
    echo "   4. Consider using a password manager\n";
    echo "   5. Enable 2FA if available in the future\n\n";
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}
