<?php

namespace Config;

use GroceryRestable\Auth\AuthContext;
use GroceryRestable\CodeIgniter4\Config\GroceryRestable as BaseGroceryRestable;
use GroceryRestable\Resource\Conditions;

/**
 * Grocery Restable configuration. See vendor/kctofficial/grocery-restable/docs/codeigniter4.md
 */
class GroceryRestable extends BaseGroceryRestable
{
    public string $apiRoute = 'api';

    public string $adminRoute = 'grocery-restable';

    public bool $autoRoutes = true;

    public bool $adminEnabled = true;

    /** @var list<string> */
    public array $apiFilters = [];

    /** @var list<string> */
    public array $adminFilters = [];

    /** @var array<string, string> connection name => app/Config/Database.php group */
    public array $databaseGroups = ['default' => 'default'];

    /**
     * Resources defined in code (they take precedence over the GUI and auto expose).
     * Every other table is exposed automatically when "auto expose" is enabled in the setup wizard.
     */
    public function define(\GroceryRestable\GroceryRestable $api): void
    {
        $api->resource('employees')
            ->setSubject('Employee')
            ->setRelation('officeCode', 'offices', '{city} ({country})')
            ->setRelationHasMany('customers', 'customers', 'salesRepEmployeeNumber', 'customerName')
            ->displayAs('officeCode', 'Office')
            ->requiredFields('lastName', 'email')
            ->setRules('email', 'required|valid_email')
            ->setFieldUpload('photo', 'employees', 'jpg|png|webp')
            // anon reads (public), signed in users too, the "editor" role of the users table also writes
            ->setPermissions(['anon' => ['select'], 'authenticated' => ['select'], 'editor' => ['select', 'insert', 'update']]);

        // orderdetails is the junction table between orders and products
        $api->resource('orders')
            ->setRelation('customerNumber', 'customers', '{customerName} ({country})', null, null, 'customer')
            ->setRelationNtoN('products', 'orderdetails', 'products', 'orderNumber', 'productCode', 'productName', 'orderLineNumber')
            ->unsetDelete()
            // Own rate limit for this endpoint (the global one is in the GUI: Settings > Rate limiting).
            ->setRateLimit(120, 60);

        // Tables with UUID keys: new rows get a time ordered UUID when the client sends no id.
        // $api->resource('notes')->setIdGenerator('uuid7');

        // User login demo (php spark migrate && php spark db:seed DemoUsersSeeder):
        // POST /api/auth/login with demo@example.com / demo12345. Settings: GUI > Settings > User login.
        //
        // The users table is defined in code so auto expose never publishes it: every signed in user only sees and
        // edits their own row, the password is hashed on write and never returned, role / active cannot be changed.
        $api->resource('users')
            ->setSubject('User')
            ->setPermissions(['authenticated' => ['select', 'update'], 'editor' => ['select', 'update']])
            ->scope(static fn (Conditions $q, AuthContext $auth) => $q->where('id', (int) $auth->userId()))
            ->fieldType('password', 'password')
            ->fieldType('role', 'readonly')
            ->fieldType('active', 'readonly')
            ->fieldType('google_id', 'readonly')
            ->fieldType('created_at', 'readonly')
            ->unsetDelete();

        // Customize the login flow (hooks run for password and Google logins):
        $api->auth()
            ->claims(static fn (array $user) => ['name' => (string) $user['name']])
            ->afterLogin(static fn (array $user, string $provider) => log_message('info', 'User {id} signed in with {provider}', ['id' => $user['id'], 'provider' => $provider]));

        // Try the JavaScript SDK: http://grocery-restable-ci4.test/sdk-demo.html
    }
}
