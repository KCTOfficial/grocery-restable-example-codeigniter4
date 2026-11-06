# Grocery Restable example: CodeIgniter 4

This is an example project. It shows how to use the
[Grocery Restable](https://github.com/KCTOfficial/grocery-restable) library inside a
[CodeIgniter 4](https://codeigniter.com) application. Feel free to run it, read it and change it. It is set up for
learning, so read [Before going to production](#12-before-going-to-production) before you reuse any of it on a real
server.

Looking for the version without a framework? See the [`grocery-restable-native`](https://github.com/KCTOfficial/grocery-restable-example-native) project.

## Contents

1. [What is Grocery Restable?](#1-what-is-grocery-restable)
2. [What is in this example?](#2-what-is-in-this-example)
3. [Requirements](#3-requirements)
4. [Installation](#4-installation)
5. [Setting up Grocery Restable](#5-setting-up-grocery-restable)
6. [User login demo (migration and seeder)](#6-user-login-demo-migration-and-seeder)
7. [The configuration file explained](#7-the-configuration-file-explained)
8. [Trying the REST API](#8-trying-the-rest-api)
9. [Trying the JavaScript SDK](#9-trying-the-javascript-sdk)
10. [Spark commands](#10-spark-commands)
11. [Using Grocery Restable in your own code](#11-using-grocery-restable-in-your-own-code)
12. [Before going to production](#12-before-going-to-production)
13. [Troubleshooting](#13-troubleshooting)
14. [Further reading](#14-further-reading)
15. [About the CodeIgniter 4 app starter](#15-about-the-codeigniter-4-app-starter)

## 1. What is Grocery Restable?

Grocery Restable turns a database (MySQL / MariaDB, PostgreSQL or SQLite) into a REST API, and gives you an admin
interface in the style of phpMyAdmin to manage it.

For example, if your database has an `employees` table, once Grocery Restable is installed you can call:

```
GET  /api/employees            list employees
GET  /api/employees/1002       read one employee
POST /api/employees            add an employee
```

You do not write controllers, models or SQL queries for each table. In CodeIgniter 4 the library registers itself:
its routes, service and spark commands are discovered automatically.

## 2. What is in this example?

The project is a regular CodeIgniter 4.7 app starter with these additions:

| File | Purpose |
|---|---|
| `composer.json` | Requires `kctofficial/grocery-restable` |
| `.env` | Site URL, database connection and the Grocery Restable encryption key |
| `app/Config/GroceryRestable.php` | Grocery Restable settings and the endpoints defined in code |
| `app/Config/Routes.php` | Redirects `/` to the admin GUI at `/grocery-restable` |
| `app/Database/Migrations/2026-11-05-000001_CreateUsers.php` | Creates the `users` table for the login demo |
| `app/Database/Seeds/DemoUsersSeeder.php` | Adds 3 demo users and turns on user login |
| `public/.htaccess` | Rewrite rule adjusted for Apache with PHP FastCGI (Laragon) |
| `public/sdk-demo.html` | A sample page that uses the JavaScript SDK (sign in, browse, search, paging) |

Once it is running you get:

| URL | What it is |
|---|---|
| `/grocery-restable` | The admin GUI: database, endpoints, API keys, users and settings |
| `/api` | The REST API for your tables |
| `/api/openapi.json` | An OpenAPI 3.1 description of the API (works with Swagger UI or Postman) |
| `/api/sdk.js` | The JavaScript SDK, ready to `import` in a browser |
| `/sdk-demo.html` | The SDK sample page |
| `/` | Redirects to `/grocery-restable` |

Settings made in the admin GUI are stored in the SQLite file
`writable/grocery-restable/grocery-restable.sqlite`, not in your own database. Your database (for example
`classicmodels`) only holds your data.

## 3. Requirements

- PHP 8.2 or newer with:
  - `intl` and `mbstring` for CodeIgniter
  - `pdo`, `pdo_sqlite`, `sodium`, `fileinfo` and `json` for Grocery Restable
  - `mysqli` for MySQL / MariaDB
- [Composer](https://getcomposer.org)
- MySQL / MariaDB with the `classicmodels` sample database (see step 4.3)
- A web server. On Windows, [Laragon](https://laragon.org) is the easiest option. Apache, nginx or
  `php spark serve` also work

Check your PHP version and extensions:

```bash
php -v
php -m
```

`intl`, `mbstring`, `mysqli`, `pdo_sqlite`, `sodium` and `fileinfo` must appear in the `php -m` output. If one is
missing, enable it in `php.ini` (in Laragon: right click the tray icon, then PHP > Extensions).

## 4. Installation

The steps assume Laragon on Windows. Notes for other setups are included where they differ.

### 4.1 Put the project in place

Copy the folder to `C:\laragon\www\grocery-restable-ci4`, or to the web root of your own server.

### 4.2 Install the dependencies

Open a terminal in the project folder and run:

```bash
cd C:\laragon\www\grocery-restable-ci4
composer install
```

`composer.json` contains a `"repositories"` block that points to a local copy of the library on the developer's
machine (`C:/Users/.../grocery-restable`). If that folder does not exist on your machine, `composer install` fails.
In that case, delete the whole `"repositories": [ ... ]` block from `composer.json` and run:

```bash
composer update
```

Composer then downloads the library from [Packagist](https://packagist.org/packages/kctofficial/grocery-restable).

In your own CodeIgniter 4 project, `composer require kctofficial/grocery-restable` is all you need.

### 4.3 Create the sample database

The examples use `classicmodels`, a free sample database of a scale model car shop (`offices`, `employees`,
`customers`, `orders`, `orderdetails`, `products` and more).

1. Download it from
   [mysqltutorial.org](https://www.mysqltutorial.org/getting-started-with-mysql/mysql-sample-database/) and unzip
   it. You get `mysqlsampledatabase.sql`.
2. Start MySQL (in Laragon: Start All).
3. Import the file, either:
   - with HeidiSQL (comes with Laragon): click Database, then File > Run SQL file... and pick the file, or
   - from a terminal:

     ```bash
     mysql -u root < mysqlsampledatabase.sql
     ```

4. Add a `photo` column to `employees`. The upload demo uses it, and the original `classicmodels` does not have it:

   ```sql
   ALTER TABLE classicmodels.employees ADD COLUMN photo VARCHAR(255) NULL;
   ```

   Run it in HeidiSQL (Query tab) or through `mysql -u root`.

### 4.4 Configure `.env`

If there is no `.env` file yet, copy CodeIgniter's `env` template:

```bash
copy env .env        # Windows cmd
cp env .env          # Git Bash, Linux, macOS
```

Then set these values:

```ini
CI_ENVIRONMENT = development

app.baseURL = 'http://grocery-restable-ci4.test/'
app.indexPage = ''

database.default.hostname = 127.0.0.1
database.default.database = classicmodels
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306
database.default.charset = utf8mb4
database.default.DBCollat = utf8mb4_general_ci

groceryrestable.encryptionKey = base64:YOUR_KEY_HERE
```

| Setting | Meaning |
|---|---|
| `CI_ENVIRONMENT` | `development` shows error details. Use `production` on a real server |
| `app.baseURL` | The site URL. It must match the address you open in the browser, with a trailing `/` |
| `app.indexPage` | Empty, so URLs do not contain `index.php` |
| `database.default.*` | Connection to `classicmodels`. `root` with no password is the Laragon default |
| `groceryrestable.encryptionKey` | Key used to encrypt secrets that Grocery Restable stores, such as the JWT secret |

Generate an encryption key:

```bash
php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"
```

Paste the output into `groceryrestable.encryptionKey`. Do not change the key after the setup: values encrypted with
the old key can no longer be read. Keep a copy of it somewhere safe.

Every property of `app/Config/GroceryRestable.php` can be overridden in `.env` with the `groceryrestable.` prefix,
for example `groceryrestable.adminEnabled = false`.

### 4.5 Start the web server

Option A, Laragon (recommended):

1. Click Start All.
2. Laragon creates `http://grocery-restable-ci4.test` automatically and points it at the `public/` folder. If the
   address does not work yet, reload Apache from the Laragon menu or restart Laragon.
3. Open `http://grocery-restable-ci4.test` in your browser.

Option B, CodeIgniter's development server:

```bash
php spark serve
```

Then open `http://localhost:8080`, and set `app.baseURL = 'http://localhost:8080/'` in `.env`. This server handles
one request at a time, so the admin GUI is slower.

Option C, your own Apache or nginx: set the document root to `public/`, as for any CodeIgniter 4 app.

The rest of this README uses `http://grocery-restable-ci4.test`. With option B, use `http://localhost:8080` instead.

## 5. Setting up Grocery Restable

The setup runs once. Use either the browser wizard or the command line.

### Option A: setup wizard

Open `http://grocery-restable-ci4.test`. You are sent to the setup wizard, which has five steps:

| Step | What to enter |
|---|---|
| 1. Framework | CodeIgniter 4 |
| 2. Database | Use the CodeIgniter database group `default`. The connection comes from `.env`, nothing to retype |
| 3. Admin account | A username (3 characters or more) and a password (8 characters or more) for the admin GUI |
| 4. API options | Application name, authentication mode (choose `any` so both API keys and user logins work) and auto expose. For this demo, turn auto expose on so every table becomes an endpoint |
| 5. Done | Two API keys are shown: `anon` and `service_role` |

### Option B: command line

```bash
php spark grocery-restable:setup --connection default --username admin --password secret123 --auth any --auto-expose --name "Demo API"
```

This does the same as the wizard and prints both API keys. It is the better choice on a remote server.

### Keep the API keys

Copy both keys. They are shown only once. If you lose them, create new ones in the admin GUI under
API > API keys, or with `php spark grocery-restable:key "Key name" service_role`.

| Key | Meant for | Access |
|---|---|---|
| `anon` | Public clients (websites, mobile apps) | Only what the permissions allow |
| `service_role` | Your own trusted servers | Everything. Never put it in browser or mobile app code |

About the wizard:

- It only opens from the local machine (`127.0.0.1`). On a remote server, set
  `groceryrestable.setupToken = some-secret` in `.env` and open `/grocery-restable/setup?token=some-secret`, or use
  option B.
- Once the setup is done, the wizard is closed for good. Everything else is changed in the admin GUI.

### The admin GUI at a glance

Sign in at `http://grocery-restable-ci4.test/grocery-restable`. The left rail has these areas:

| Area | Purpose |
|---|---|
| Overview | Summary, a getting started checklist, configuration warnings and code samples |
| Data | Works like phpMyAdmin: browse, search, add, edit and delete rows |
| API | Which tables are endpoints and how: fields, relations, permissions, uploads, API keys, docs and a console to try requests |
| Storage | Buckets and files, organized like S3 |
| Logs | API request and admin action logs |
| Settings | Account, authentication, user login, rate limiting, CORS, uploads, database connections, other admins |

`employees`, `orders` and `users` are marked as code endpoints (purple dot) because they are defined in
`app/Config/GroceryRestable.php`. The GUI shows them but cannot change them.

## 6. User login demo (migration and seeder)

Grocery Restable can sign users in against a users table in your own database. After the setup, run:

```bash
php spark migrate
php spark db:seed DemoUsersSeeder
```

`migrate` creates the `users` table (email, username, password, name, avatar, role, active, google_id,
created_at). `DemoUsersSeeder` adds three demo users, with hashed passwords:

| Email | Username | Password | Role | Result |
|---|---|---|---|---|
| `demo@example.com` | `demo` | `demo12345` | `authenticated` | Can read data |
| `editor@example.com` | `editor` | `editor12345` | `editor` | Can read and write (on `employees`: create and update only) |
| `disabled@example.com` | `disabled` | `disabled12345` | | Inactive, login is refused |

The seeder also turns on user login (the same as filling in Settings > User login in the GUI), switches the
authentication mode to `any` and sets the default permissions: `anon` gets nothing, `authenticated` can read,
`editor` can do everything. Running it again is safe: existing users are skipped.

To try it in the browser, open `http://grocery-restable-ci4.test/sdk-demo.html`, enter `demo@example.com` /
`demo12345` and click Sign in. Try `editor` and `disabled` too and compare.

Sign in with Google is supported as well. Add your OAuth web client id in Settings > User login > Google and enable
it; the Google button then shows up on `/sdk-demo.html`.

## 7. The configuration file explained

### 7.1 Properties

`app/Config/GroceryRestable.php`:

| Property | Value in this demo | Meaning |
|---|---|---|
| `apiRoute` | `api` | API URL: `http://your-site/api/...` |
| `adminRoute` | `grocery-restable` | Admin GUI URL |
| `autoRoutes` | `true` | Routes are registered automatically. With `false`, call `\GroceryRestable\CodeIgniter4\Routes::register($routes);` in `app/Config/Routes.php` |
| `adminEnabled` | `true` | `false` turns the admin GUI off completely (useful on API only servers) |
| `apiFilters` | `[]` | CodeIgniter filters for the API routes, for example `['throttle']` |
| `adminFilters` | `[]` | CodeIgniter filters for the admin GUI, for example an IP allow list |
| `databaseGroups` | `['default' => 'default']` | CodeIgniter database groups Grocery Restable may use |

### 7.2 Endpoints defined in code (`define()`)

A table can become an endpoint in three ways. When more than one applies to the same table, the first one in this
list wins:

1. Code, in the `define()` method
2. The GUI (the API area), stored in SQLite
3. Auto expose, generated from the database schema

This demo defines three endpoints in code. Every other table comes from auto expose.

`employees` shows relations, validation, uploads and permissions:

```php
$api->resource('employees')
    ->setSubject('Employee')
    ->setRelation('officeCode', 'offices', '{city} ({country})')        // each employee has one office
    ->setRelationHasMany('customers', 'customers', 'salesRepEmployeeNumber', 'customerName')  // and many customers
    ->displayAs('officeCode', 'Office')                                  // field label
    ->requiredFields('lastName', 'email')
    ->setRules('email', 'required|valid_email')                          // CodeIgniter validation rules
    ->setFieldUpload('photo', 'employees', 'jpg|png|webp')               // photo stores an uploaded file
    ->setPermissions(['anon' => ['select'], 'authenticated' => ['select'], 'editor' => ['select', 'insert', 'update']]);
```

Anyone can read it, even without a key. The `editor` role can also create and update. Nobody except `service_role`
can delete.

`orders` shows a many to many relation and a rate limit:

```php
$api->resource('orders')
    ->setRelation('customerNumber', 'customers', '{customerName} ({country})', null, null, 'customer')
    ->setRelationNtoN('products', 'orderdetails', 'products', 'orderNumber', 'productCode', 'productName', 'orderLineNumber')
    ->unsetDelete()            // orders cannot be deleted through the API
    ->setRateLimit(120, 60);   // at most 120 requests per 60 seconds on this endpoint
```

`orderdetails` is the junction table between `orders` and `products`. The customer relation is named `customer`, so
it can be used as `select=customer(customerName)`.

`users` is the login table, locked down:

- A user can only read and update their own row (`scope()` filters on the user id).
- `password` is hashed on write and never returned.
- `role`, `active`, `google_id` and `created_at` are read only.
- Users cannot delete their account through the API.

Because it is defined in code, auto expose can never publish the `users` table with open permissions.

Login hooks add the user name to the token and write each login to the CodeIgniter log (`writable/logs/`):

```php
$api->auth()
    ->claims(static fn (array $user) => ['name' => (string) $user['name']])
    ->afterLogin(static fn (array $user, string $provider) => log_message('info', 'User {id} signed in with {provider}', [...]));
```

The GUI is the easiest place to experiment. When an endpoint looks right, the PHP code tab of its editor shows the
equivalent PHP, which you can move into `define()`.

## 8. Trying the REST API

The examples use `curl`. On Windows, run them in Git Bash or the Laragon terminal (Cmder) so the quoting works as
written.

Store the base URL and your `service_role` key in variables first:

```bash
BASE=http://grocery-restable-ci4.test/api
KEY=gr_xxxxxxxxxxxxxxxx      # your service_role key
```

### 8.1 Public data (no key)

`employees` is readable by anyone:

```bash
# First 5 employees
curl "$BASE/employees?limit=5"

# Employees with the city of their office and the names of their customers
curl "$BASE/employees?select=lastName,offices(city),customers(customerName)&limit=3"

# One employee
curl "$BASE/employees/1002"
```

### 8.2 Data that needs a key or a login

`orders` uses the default permissions, so it needs a key or a signed in user:

```bash
# One order with its customer and products
curl "$BASE/orders/10100?select=orderNumber,status,customer(customerName),products(productName)" -H "apikey: $KEY"

# Orders of French customers
curl "$BASE/orders?select=orderNumber,customer(customerName)&customer.country=eq.France&limit=5" -H "apikey: $KEY"

# Without a key the request is rejected
curl -i "$BASE/orders?limit=1"
```

### 8.3 Filtering, sorting, paging and search

Filters use the form `column=operator.value`.

```bash
# Employees of office 1
curl "$BASE/employees?officeCode=eq.1"

# Products with a buy price of 50 or more, most expensive first
curl "$BASE/products?buyPrice=gte.50&order=buyPrice.desc&limit=10" -H "apikey: $KEY"

# Page 2, 10 rows per page
curl "$BASE/customers?page=2&per_page=10" -H "apikey: $KEY"

# Text search across text columns
curl "$BASE/customers?q=mini" -H "apikey: $KEY"

# Customers from several countries
curl "$BASE/customers?country=in.(France,Germany)" -H "apikey: $KEY"

# Download as CSV
curl "$BASE/customers?format=csv" -H "apikey: $KEY" -o customers.csv
```

Operators:

| Operator | Meaning | Example |
|---|---|---|
| `eq` | equal | `status=eq.Shipped` |
| `neq` | not equal | `status=neq.Cancelled` |
| `gt`, `gte` | greater than, greater or equal | `price=gte.10` |
| `lt`, `lte` | less than, less or equal | `price=lt.100` |
| `like`, `ilike` | pattern match (`*` is a wildcard), `ilike` ignores case | `name=ilike.*ford*` |
| `in` | one of a list | `id=in.(1,2,3)` |
| `is` | null, true or false | `deleted_at=is.null` |
| `not.` | negates the operator after it | `status=not.in.(Cancelled,Disputed)` |

### 8.4 Creating, updating, uploading and deleting

```bash
# Add an employee (employeeNumber is not auto increment in classicmodels, so it is sent explicitly)
curl -X POST "$BASE/employees" -H "apikey: $KEY" -H "Content-Type: application/json" \
     -d '{"employeeNumber":1800,"lastName":"Doe","firstName":"Jane","email":"jane@example.com","extension":"x1","officeCode":"1","jobTitle":"Sales Rep"}'

# Validation in action: an invalid email returns 422 with a message per field
curl -X POST "$BASE/employees" -H "apikey: $KEY" -H "Content-Type: application/json" \
     -d '{"employeeNumber":1801,"lastName":"Doe","email":"not-an-email"}'

# Change some columns
curl -X PATCH "$BASE/employees/1800" -H "apikey: $KEY" -H "Content-Type: application/json" \
     -d '{"jobTitle":"Sales Manager"}'

# Upload a photo (jpg, png or webp) into the photo field
curl -X POST "$BASE/employees/1800/upload/photo" -H "apikey: $KEY" -F "file=@photo.jpg"

# Download it
curl "$BASE/employees/1800/file/photo" -H "apikey: $KEY" -o photo-1800.jpg

# Delete (only service_role may, per the permissions in define())
curl -X DELETE "$BASE/employees/1800" -H "apikey: $KEY"

# Orders cannot be deleted because of unsetDelete(): 405
curl -i -X DELETE "$BASE/orders/10100" -H "apikey: $KEY"
```

In PowerShell, JSON quoting tends to break. Put the JSON in a file such as `body.json` and use `curl.exe` (not the
`curl` alias) with `-d "@body.json"`, or use Postman or API > Console in the admin GUI.

### 8.5 Using a login token

```bash
# Sign in as the editor
curl -X POST "$BASE/auth/login" -H "Content-Type: application/json" \
     -d '{"identity":"editor@example.com","password":"editor12345"}'
```

The response contains an `access_token` (valid for 15 minutes by default) and a `refresh_token`, which gets a new
access token through `POST /api/auth/refresh`. The SDK refreshes automatically. Send the access token with later
requests:

```bash
TOKEN=eyJhbGciOi...      # the access_token from the login response

curl "$BASE/auth/me" -H "Authorization: Bearer $TOKEN"     # the signed in user
curl "$BASE/users" -H "Authorization: Bearer $TOKEN"       # only this user's own row

# The editor may update employees. Signed in as demo, the same request returns 403
curl -X PATCH "$BASE/employees/1002" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
     -d '{"extension":"x5800"}'
```

### 8.6 Common status codes

| Code | Meaning | What to check |
|---|---|---|
| `200`, `201` | Success | |
| `400` | Bad request (unknown column, malformed filter) | Column names and filter syntax |
| `401` | Invalid or expired key or token | The key, or refresh the token |
| `403` | Not allowed | The role's permissions in API > (table) > Permissions or in `define()` |
| `404` | Unknown endpoint or row | The table is exposed and the id is correct |
| `405` | Operation disabled | For example `unsetDelete()` |
| `409` | Database constraint (duplicate, foreign key) | The data you sent |
| `422` | Validation failed | The response lists the invalid fields |
| `429` | Rate limit reached | Wait, or change Settings > Rate limiting |

The full list of endpoints available to you is at `http://grocery-restable-ci4.test/api/openapi.json`.

## 9. Trying the JavaScript SDK

Open `http://grocery-restable-ci4.test/sdk-demo.html`. The page loads the SDK straight from `/api/sdk.js`, with no
build step.

1. Sign in with a demo user (see [section 6](#6-user-login-demo-migration-and-seeder)), or enter an API key and
   click Connect. Do not use the `service_role` key in a browser.
2. Pick a table in Endpoint and try Search, Prev and Next.
3. The panel on the right shows the SDK code for what you are doing.

Using the SDK in your own page:

```html
<script type="module">
    import GroceryRestable, { LocalAuthStore } from 'http://grocery-restable-ci4.test/api/sdk.js';

    const gr = new GroceryRestable('http://grocery-restable-ci4.test/api', { authStore: new LocalAuthStore() });

    // The token is kept in localStorage and refreshed automatically
    await gr.auth.login('editor@example.com', 'editor12345');

    // Page 1, 10 rows, employees of office 1, sorted by last name descending, with the office city
    const { items, totalItems } = await gr.collection('employees').getList(1, 10, {
        filter: { officeCode: '1' },
        sort: '-lastName',
        select: 'lastName,offices(city)',
    });
    console.log(totalItems, items);

    // await gr.collection('employees').create({ ... });
    // await gr.collection('employees').update(1002, { extension: 'x5800' });

    await gr.auth.logout();
</script>
```

If your page runs on a different domain, allow that domain in Settings > CORS.

## 10. Spark commands

| Command | What it does |
|---|---|
| `php spark grocery-restable:setup ...` | Setup without a browser (see [section 5](#5-setting-up-grocery-restable)) |
| `php spark grocery-restable:admin <username>` | Create an admin user. Add `--reset` to reset the password of an existing one |
| `php spark grocery-restable:key <name> [role]` | Create an API key (printed once) |
| `php spark grocery-restable:cache` | Clear the schema cache (rarely needed, changes are detected automatically) |
| `php spark grocery-restable:cleanup` | Report unused uploaded files without removing anything |
| `php spark grocery-restable:cleanup --run` | Clean them up (files go to a trash folder first) |
| `php spark grocery-restable:publish` | Create `app/Config/GroceryRestable.php` in a new project (this demo already has it) |

To list the registered commands and routes:

```bash
php spark list | grep grocery-restable
php spark routes | grep -i grocery
```

To run the cleanup daily:

- Linux cron: `0 3 * * * cd /var/www/grocery-restable-ci4 && php spark grocery-restable:cleanup --run`
- Windows Task Scheduler: a daily task that runs `php spark grocery-restable:cleanup --run` with
  `C:\laragon\www\grocery-restable-ci4` as the working folder

The same cleanup is available in the admin GUI under Settings > Storage cleanup, where you can also turn on a daily
cleanup without cron.

## 11. Using Grocery Restable in your own code

The library is available as a CodeIgniter service:

```php
/** @var \GroceryRestable\GroceryRestable $api */
$api = service('groceryRestable');

$key  = $api->store()->createApiKey('Mobile app', 'authenticated');   // create an API key
$hash = $api->auth()->hashPassword('secret123');                      // hash a password the way user login does
```

You can also serve one table from your own controller, Grocery CRUD style, for example behind your own login filter:

```php
// app/Config/Routes.php
\GroceryRestable\CodeIgniter4\Routes::resource($routes, 'shop/employees', 'Employees::index');
```

```php
// app/Controllers/Employees.php
namespace App\Controllers;

use GroceryRestable\CodeIgniter4\Libraries\GroceryRestable;

class Employees extends BaseController
{
    public function index(string ...$segments)
    {
        $crud = new GroceryRestable();
        $crud->setTable('employees')
             ->set_subject('Employee')                       // snake_case names work too
             ->set_relation('officeCode', 'offices', 'city')
             ->required_fields('lastName');

        return $crud->render($segments);
    }
}
```

If you enable CodeIgniter's global `csrf` filter in `app/Config/Filters.php`, exclude the Grocery Restable routes.
The API uses tokens, and the admin GUI has its own CSRF protection:

```php
public array $globals = [
    'before' => [
        'csrf' => ['except' => ['api/*', 'grocery-restable/*']],
    ],
];
```

## 12. Before going to production

This example is deliberately permissive. Before using it for real:

1. Set `CI_ENVIRONMENT = production` in `.env`, so API errors no longer include details.
2. Use your own `groceryrestable.encryptionKey`, keep `.env` out of version control and back the key up.
3. Make sure the document root is `public/` and that `writable/` cannot be reached from a browser.
4. Turn the admin GUI off on public API servers (`groceryrestable.adminEnabled = false`) or protect it with
   `adminFilters`, for example an IP allow list.
5. Remove the demo users (`demo`, `editor`, `disabled`).
6. Never ship the `service_role` key in frontend or mobile code.
7. Restrict allowed origins in Settings > CORS.
8. Turn on Settings > Rate limiting.
9. Serve the site over HTTPS.
10. Back up `writable/grocery-restable/grocery-restable.sqlite`.

## 13. Troubleshooting

### Every page except the home page shows "No input file specified."

This happens with Apache and PHP FastCGI (Laragon, XAMPP). `public/.htaccess` in this project is already fixed:
CodeIgniter's default rule `RewriteRule ^([\s\S]*)$ index.php/$1 [L,NC,QSA]` was replaced with:

```apache
RewriteRule ^ index.php [L,QSA]
```

Make the same change when you add Grocery Restable to another CodeIgniter project on the same kind of server.

### `/api` or `/grocery-restable` returns 404

- Check that the library is installed (`composer install`) and its routes are registered:
  `php spark routes | grep -i grocery`.
- Check that the document root points to `public/`.
- With `autoRoutes = false`, the routes must be registered by hand (see [section 7.1](#71-properties)).

### The admin GUI opens but links or assets are broken, or it redirects elsewhere

`app.baseURL` in `.env` does not match the address in the browser. Make them identical, including `http` or `https`
and the port.

### The browser console shows "Refused to execute inline script"

That is CodeIgniter's Debug Toolbar in `development` mode, blocked by the admin GUI's Content-Security-Policy. It is
harmless and does not happen in `production`.

### `composer install` fails because the path repository is missing

See step [4.2](#42-install-the-dependencies).

### Errors about `intl`, `pdo_sqlite` or `sodium`

The extension is not enabled. Enable it in `php.ini` (Laragon: PHP > Extensions) and restart the web server.

### Photo upload fails or `photo` is an unknown field

The `photo` column was not added to `employees`. See step [4.3](#43-create-the-sample-database), item 4.

### `php spark db:seed DemoUsersSeeder` fails because `users` does not exist

Run `php spark migrate` first.

### The setup wizard returns 403 from another machine

The wizard only accepts local requests. Use `groceryrestable.setupToken` or `php spark grocery-restable:setup`.

### The setup wizard returns 404

That is expected once the setup is complete. Sign in at `/grocery-restable`.

### Forgotten admin password

```bash
php spark grocery-restable:admin admin --reset      # replace "admin" with your username
```

### Starting over

Delete `writable/grocery-restable/grocery-restable.sqlite` and the wizard comes back. All GUI settings and API keys
are lost. Your own database (`classicmodels`) is not touched.

### A new table or column (after a migration) does not show up in the API

Schema changes are picked up automatically on the next request. To force it, run
`php spark grocery-restable:cache` or click the refresh icon in the Data area. If auto expose is off, click Expose
next to the table.

### Table prefixes (`DBPrefix`)

Grocery Restable does not apply CodeIgniter's `DBPrefix`. Use the full table name, for example
`$api->resource('app_users')`.

## 14. Further reading

Documentation: <https://kctofficial.github.io/grocery-restable/>

- Getting started: concepts and a 10 minute tutorial, also based on `classicmodels`
- CodeIgniter 4: the integration in more depth (the topic of this project)
- Admin GUI: every screen explained
- Resources: every method for defining endpoints in code
- Relations: relations between tables and embedding them in one request
- REST API reference: endpoints, filters and response formats
- JavaScript SDK: browser and Node.js usage
- Authentication: API keys, JWT, user login, roles and row level security
- Troubleshooting: frequently asked questions

Source code: <https://github.com/KCTOfficial/grocery-restable>

## 15. About the CodeIgniter 4 app starter

This project was created with `composer create-project codeigniter4/appstarter`. The main points from the original
CodeIgniter README:

- `index.php` lives in `public/`, not in the project root. Point the web server at `public/` so the application and
  framework files are not reachable from outside.
- To update the framework, run `composer update` and read the release notes. Changed files in `app` can be compared
  with `vendor/codeigniter4/framework/app`.
- Server requirements: PHP 8.2+ with `intl`, `mbstring` and `json`; `mysqlnd` for MySQL; `libcurl` if you use
  `HTTP\CURLRequest`.
- CodeIgniter user guide: <https://codeigniter.com/user_guide/>

The CodeIgniter 4 app starter is released under the [MIT license](LICENSE).
