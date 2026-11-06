<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Demo users and the user login settings (the same as Settings > User login in the admin GUI).
 *
 *   php spark migrate
 *   php spark db:seed DemoUsersSeeder
 *
 * Safe to run again. Demo only: never ship these passwords.
 */
class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        /** @var \GroceryRestable\GroceryRestable $api */
        $api = service('groceryRestable');

        // email, username, password, name, role (null = jwt_default_role "authenticated"), active
        $users = [
            ['demo@example.com', 'demo', 'demo12345', 'Demo User', null, 1],
            ['editor@example.com', 'editor', 'editor12345', 'Eddie Editor', 'editor', 1],
            ['disabled@example.com', 'disabled', 'disabled12345', 'Disabled User', null, 0],
        ];
        $table = $this->db->table('users');
        foreach ($users as [$email, $username, $password, $name, $role, $active]) {
            if ($table->where('email', $email)->countAllResults() > 0) {
                continue;
            }
            $table->insert([
                'email' => $email,
                'username' => $username,
                // hashed with the algorithm configured for the user login (argon2id by default)
                'password' => $api->auth()->hashPassword($password),
                'name' => $name,
                'role' => $role,
                'active' => $active,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            echo "Created {$email} / {$password}\n";
        }

        $store = $api->store();
        $settings = [
            // user tokens must be accepted ("none" would make every request service_role)
            'auth_mode' => 'any',
            'auth_password_enabled' => true,
            // demo roles: "authenticated" (demo user) reads, "editor" (role column) writes
            'default_permissions' => ['anon' => [], 'authenticated' => ['select'], 'editor' => ['select', 'insert', 'update', 'delete']],
            'auth_users_table' => 'users',
            'auth_users_id_column' => 'id',
            'auth_users_identity_columns' => ['email', 'username'],
            'auth_users_password_column' => 'password',
            'auth_users_role_column' => 'role',
            'auth_users_active_column' => 'active',
            'auth_token_strategy' => 'jwt',
            // Google sign-in: add your OAuth web client id in the GUI (Settings > User login > Google) and enable it.
            'auth_google_id_column' => 'google_id',
            'auth_google_email_column' => 'email',
            'auth_google_signup_fields' => ['name' => 'name', 'avatar' => 'picture'],
        ];
        if ((string) $store->setting('jwt_secret', '') === '' && (string) $api->config()->get('jwtSecret', '') === '') {
            $settings['jwt_secret'] = bin2hex(random_bytes(32));
        }
        $store->saveSettings($settings);
        $api->schema()->refresh();

        echo "User login enabled: POST /api/auth/login, try it on /sdk-demo.html\n";
    }
}
