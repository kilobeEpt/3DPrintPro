<?php
// ========================================
// Setup Admin Credentials
// CLI script to bootstrap or reset admin credentials
// ========================================
//
// USAGE:
//   Interactive mode:
//     php scripts/setup-admin-credentials.php
//
//   Non-interactive mode:
//     php scripts/setup-admin-credentials.php <login> <password>
//
//   Example:
//     php scripts/setup-admin-credentials.php admin MySecurePassword123
//
// ========================================

// Ensure this is run from CLI only
if (php_sapi_name() !== 'cli') {
    die("⛔ This script can only be run from the command line.\n");
}

// Change to project root directory
chdir(__DIR__ . '/..');

// Require database class
require_once __DIR__ . '/../api/db.php';

echo "========================================\n";
echo "  3D Print Pro - Admin Credentials Setup\n";
echo "========================================\n\n";

// ========================================
// Get credentials from arguments or prompt
// ========================================

$login = null;
$password = null;

// Check if credentials provided as arguments
if ($argc >= 3) {
    $login = trim($argv[1]);
    $password = $argv[2];
    echo "📋 Using credentials from command line arguments\n\n";
} else {
    // Interactive mode
    echo "Enter admin credentials:\n";
    echo "(Leave blank to use defaults: admin / admin123)\n\n";
    
    // Get login
    echo "Admin login [admin]: ";
    $loginInput = trim(fgets(STDIN));
    $login = !empty($loginInput) ? $loginInput : 'admin';
    
    // Get password (note: will be visible in terminal)
    echo "Admin password [admin123]: ";
    $passwordInput = trim(fgets(STDIN));
    $password = !empty($passwordInput) ? $passwordInput : 'admin123';
    
    echo "\n";
}

// ========================================
// Validate credentials
// ========================================

if (empty($login) || empty($password)) {
    die("❌ Error: Login and password cannot be empty.\n");
}

if (strlen($login) < 3) {
    die("❌ Error: Login must be at least 3 characters long.\n");
}

if (strlen($password) < 6) {
    die("❌ Error: Password must be at least 6 characters long.\n");
}

// ========================================
// Hash password and save to database
// ========================================

try {
    echo "🔐 Hashing password...\n";
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    echo "💾 Connecting to database...\n";
    $db = new Database();
    
    // Check if credentials already exist
    $existingLogin = $db->getSetting('admin_login');
    
    if ($existingLogin) {
        echo "⚠️  Admin credentials already exist in database.\n";
        echo "   Current login: {$existingLogin}\n\n";
        echo "Do you want to overwrite them? (yes/no) [no]: ";
        
        // Only prompt if not using command line arguments
        if ($argc < 3) {
            $confirm = trim(fgets(STDIN));
            if (strtolower($confirm) !== 'yes') {
                die("❌ Operation cancelled. Credentials not changed.\n");
            }
        } else {
            echo "yes (auto-confirmed in non-interactive mode)\n";
        }
        
        echo "\n";
    }
    
    echo "💾 Saving credentials to database...\n";
    
    // Save login
    $db->saveSetting('admin_login', $login);
    
    // Save password hash
    $db->saveSetting('admin_password_hash', $passwordHash);
    
    echo "\n✅ SUCCESS! Admin credentials have been saved.\n\n";
    echo "========================================\n";
    echo "  Credentials Summary\n";
    echo "========================================\n";
    echo "Login:    {$login}\n";
    echo "Password: " . str_repeat('•', strlen($password)) . " (hidden)\n";
    echo "========================================\n\n";
    echo "🔗 Login URL: " . (defined('SITE_URL') ? SITE_URL : 'http://localhost') . "/admin/login.php\n\n";
    echo "⚠️  IMPORTANT SECURITY NOTES:\n";
    echo "   1. Store these credentials in a secure location\n";
    echo "   2. Do not commit passwords to version control\n";
    echo "   3. Use a strong, unique password for production\n";
    echo "   4. Change the default password immediately\n";
    echo "   5. Consider using a password manager\n\n";
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}
