<?php

/**
 * DefaultUserSeeder
 * 
 * Creates default admin user
 */
class DefaultUserSeeder extends Seeder
{
    public function run()
    {
        // Only create if no users exist
        if ($this->db()->table('users')->count() > 0) {
            echo "  Users already exist, skipping default user creation\n";
            return;
        }

        // Default password: admin123 (CHANGE IN PRODUCTION!)
        $defaultPassword = password_hash('admin123', PASSWORD_BCRYPT);

        $user = [
            'username' => 'admin',
            'password_hash' => $defaultPassword,
            'email' => 'admin@3dprintpro.ru',
            'full_name' => 'System Administrator',
            'role' => 'super_admin',
            'active' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->insert('users', $user);
        
        echo "  ⚠ Default admin user created:\n";
        echo "     Username: admin\n";
        echo "     Password: admin123\n";
        echo "     ⚠ CHANGE PASSWORD IMMEDIATELY IN PRODUCTION!\n";
    }
}
