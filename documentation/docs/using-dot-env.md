# Using Dot ENV

Roolith does not ship with `.env` support out of the box.
This recipe shows how to add it with [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) so secrets stay out of `config/config.php` and version control.
It follows the [twelve-factor app](https://12factor.net/config) principle: anything that changes between environments should live in the environment, not in code.

## Installation

Install the package with Composer.

```bash
composer require vlucas/phpdotenv
```

It requires PHP `>=7.2` (`^5.6` for phpdotenv 5).
No other setup is needed.

## Create the Env Files

Create a `.env` file in the project root (next to `index.php` and `composer.json`).
This file holds real secrets and must never be committed.

```ini
APP_URL=http://localhost:8080
APP_ENV=development

DB_HOST=localhost
DB_NAME=roolith_cms
DB_USER=root
DB_PASS=

MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=user@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM=no-reply@example.com
```

Create a `.env.example` file with the same keys but with dummy or empty values.
Check this file into git so collaborators know what is required.

```ini
APP_URL=
APP_ENV=development

DB_HOST=localhost
DB_NAME=
DB_USER=
DB_PASS=

MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM=
```

Add `.env` to `.gitignore` (keep `.env.example` tracked).

```gitignore
.env
```

Roolith's default `.gitignore` does not include `.env`, so add it manually.
The example in the repository only ignores `vendor/`, `node_modules/`, `assets/` and similar build artifacts.

## Loading the File

Variables must be loaded before any code reads them.
The earliest place in Roolith is `index.php` and `app/Core/System.php`.

### Option 1 - Load in index.php (Recommended)

This covers every request (web, CLI and Vite proxy).

```php
<?php

use App\Core\System;

const APP_ROOT = __DIR__;
date_default_timezone_set('America/Edmonton');

session_start();

require_once __DIR__ . '/vendor/autoload.php';

// Load .env before the framework boots
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$app = new System();
try {
    $app->bootstrap()
        ->processRequest()
        ->complete();
} catch (\App\Core\Exceptions\Exception|\Roolith\Configuration\Exception\InvalidArgumentException $e) {
    print $e->getMessage();
}
```

`createImmutable()` will not overwrite an already set environment variable.
That makes it safe when the server already injects variables (Docker, Nginx, Apache).
`safeLoad()` does not throw if `.env` is missing, which is useful in production where variables come from the real environment.
Use `load()` instead if you want a missing `.env` to throw an exception in development.

### Option 2 - Load Inside System.php

If you prefer to keep `index.php` untouched, load it inside `app/Core/System.php` in `__construct()` before `Config` is used.

```php
public function __construct()
{
    require_once APP_ROOT . "/constant.php";

    $cmsConstantPath = APP_ROOT . "/cms-constant.php";
    if (file_exists($cmsConstantPath)) {
        require_once $cmsConstantPath;
    }

    require_once APP_ROOT . "/app/Utils/functions.php";

    // Load .env before anything else touches Config or $_ENV
    if (class_exists(\Dotenv\Dotenv::class)) {
        \Dotenv\Dotenv::createImmutable(APP_ROOT)->safeLoad();
    }

    $this->db = null;
    $this->registerCustomError();
}
```

Either option works.
Pick one and be consistent.
Option 1 is easier to reason about because inside `index.php` is where `APP_ROOT` is defined and autoload is already required.

### CLI and Background Scripts

If you run custom scripts or the `roolith` generator that boots the framework, make sure they also load `.env` before `new System()`.
For a standalone script:

```php
<?php
const APP_ROOT = __DIR__ . "/..";
require_once APP_ROOT . "/vendor/autoload.php";

Dotenv\Dotenv::createImmutable(APP_ROOT)->safeLoad();

$app = new App\Core\System();
$app->bootstrap();
// your logic
$app->complete();
```

For a console runner at `bin/console`, reuse the same snippet as in `index.php`.

### Custom Path or Filename

By default `createImmutable(__DIR__)` looks for `__DIR__ . '/.env'`.
Pass a second argument to use a different file, or pass arrays to try multiple files.

```php
// single custom file
Dotenv\Dotenv::createImmutable(__DIR__, '.env.local')->safeLoad();

// try .env then .env.local, load first readable one
Dotenv\Dotenv::createImmutable(__DIR__, ['.env', '.env.local'])->safeLoad();

// merge all readable files, later files override earlier ones
Dotenv\Dotenv::createImmutable(__DIR__, ['.env', '.env.local'], false)->safeLoad();
```

See [vlucas/phpdotenv Usage](https://github.com/vlucas/phpdotenv#usage) for all signatures.

## Reading Variables

After `load()` or `safeLoad()`, variables are available in `$_ENV` and `$_SERVER`.
If you use `createUnsafeImmutable()` they are also available via `getenv()` and `putenv()`.

```php
// preferred, works with createImmutable()
$host = $_ENV['DB_HOST'];
$host = $_SERVER['DB_HOST'];

// only if you used createUnsafeImmutable()
$host = getenv('DB_HOST');
```

Prefer `$_ENV` or `$_SERVER` in application code.
`getenv()` / `putenv()` are not thread safe when PHP runs as `mod_php`.

Provide defaults with null coalescing so the app still boots when a key is missing in development.

```php
$host = $_ENV['DB_HOST'] ?? 'localhost';
$pass = $_ENV['DB_PASS'] ?? '';
```

## Wiring Env Into Roolith Config

The idiomatic place to translate env variables into Roolith config is `config/config.php`.
That way the rest of the app keeps using `Roolith\Configuration\Config::get()` and does not need to know about `$_ENV`.

```php
<?php
return [
    "baseUrl" => $_ENV['APP_URL'] ?? "http://localhost:8080/",
    "viteDevServer" => $_ENV['VITE_DEV_SERVER'] ?? "",

    "database" => [
        "host" => $_ENV['DB_HOST'] ?? "localhost",
        "name" => $_ENV['DB_NAME'] ?? "roolith_cms",
        "user" => $_ENV['DB_USER'] ?? "root",
        "pass" => $_ENV['DB_PASS'] ?? "",
    ],

    "mail" => [
        "host" => $_ENV['MAIL_HOST'] ?? "smtp.example.com",
        "port" => (int) ($_ENV['MAIL_PORT'] ?? 587),
        "username" => $_ENV['MAIL_USERNAME'] ?? "",
        "password" => $_ENV['MAIL_PASSWORD'] ?? "",
        "security" => $_ENV['MAIL_ENCRYPTION'] ?? "tls",
        "from" => $_ENV['MAIL_FROM'] ?? "no-reply@example.com",
    ],

    "forceNonWww" => true,
    "version" => time(),
];
```

Then read as usual elsewhere.

```php
use Roolith\Configuration\Config;

Config::get('baseUrl');
Config::get('database.host');
Config::get('mail.host');
```

### Optional env() Helper

Add a small helper in `app/Utils/functions.php` if you want Laravel-style access.

```php
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}
```

### Type Casting

Everything from `.env` is a string.
Cast when needed: `(int)`, `(bool)` or `(float)`.

```php
"mail" => [
    "port" => (int) ($_ENV['MAIL_PORT'] ?? 587),
],
"debug" => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
```

### Environment Specific Overrides

Roolith already supports `config/development.config.php` and `config/production.config.php` via `ROOLITH_ENV` inside `constant.php`.
You can keep using that mechanism together with `.env`.
A common pattern is to let `.env` hold secrets and let `ROOLITH_ENV` pick non-secret overrides.

```php
// constant.php
const ROOLITH_ENV = 'development';
```

```php
// config/development.config.php
<?php
return [
    "baseUrl" => $_ENV['APP_URL'] ?? "http://localhost:8080/",
    "viteDevServer" => "http://localhost:5173",
];
```

Alternatively derive `ROOLITH_ENV` from `.env` itself by setting it before Config boots.

```php
// index.php, after safeLoad()
if (isset($_ENV['APP_ENV']) && !defined('ROOLITH_ENV')) {
    define('ROOLITH_ENV', $_ENV['APP_ENV']);
}
```

But `constant.php` is already required inside `System::__construct()`.
So either define `ROOLITH_ENV` inside `constant.php` from `$_ENV` after loading dotenv in `System.php`, or load dotenv earlier in `index.php` and define the constant there before `new System()`.

## Validation

Phpdotenv can assert that required variables are present and well formed.
Call it right after `load()`.

```php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// fail fast if any required key is missing
$dotenv->required(['APP_URL', 'DB_HOST', 'DB_NAME', 'DB_USER'])->notEmpty();
$dotenv->required('MAIL_PORT')->isInteger();
$dotenv->required('APP_ENV')->allowedValues(['development', 'production', 'testing']);

// only validate if present
$dotenv->ifPresent('REDIS_PORT')->isInteger();
```

Available assertions: `notEmpty()`, `isInteger()`, `isBoolean()`, `allowedValues([...])`, `allowedRegexValues('...')`.
See [Requiring Variables to be Set](https://github.com/vlucas/phpdotenv#requiring-variables-to-be-set) for the full list.
A missing or invalid variable throws `Dotenv\Exception\ValidationException`.

## Nesting, Quoting and Comments

These follow the phpdotenv spec, not Roolith.

```ini
# comment
BASE_DIR=/var/www/roolith
CACHE_DIR="${BASE_DIR}/cache"
TMP_DIR="${BASE_DIR}/tmp"

# interpolation uses ${VAR}, bare $VAR is not replaced
# single quotes are literal, double quotes allow \n \t \" \\ etc
WIN1='C:\Users\vlucas'
WIN2="C:\\Users\\vlucas"
MESSAGE="Hello
World"

# # inside quotes is kept, outside it starts a comment
VAR="foo#bar" # kept
VAR=foo#bar   # becomes "foo"
```

Prefer double quotes when a value contains spaces, `#`, or needs interpolation.

## Using Variables Outside Config

You can read env anywhere after bootstrap, but prefer going through `Config` so values are centralized and mockable in tests.

```php
<?php
namespace App\Controllers;

use Roolith\Configuration\Config;

class HomeController extends Controller
{
    public function index()
    {
        $appUrl = Config::get('baseUrl');
        $mailHost = Config::get('mail.host');

        // or directly, if you did not map it into config
        $raw = $_ENV['SOME_KEY'] ?? null;

        return $this->view('home', ['appUrl' => $appUrl]);
    }
}
```

## Docker and Production

In Docker or on a PaaS, inject variables via the environment instead of mounting a `.env` file.
`safeLoad()` will simply do nothing when `.env` is absent, and `$_ENV` will already contain the injected values.

```yaml
# docker-compose.yml
services:
  app:
    build: .
    environment:
      APP_ENV: production
      APP_URL: https://example.com
      DB_HOST: db
      DB_NAME: roolith_cms
      DB_USER: root
      DB_PASS: secret
```

Because `createImmutable()` never overwrites existing variables, values set by Docker, Nginx `fastcgi_param`, or Apache `SetEnv` always win over the `.env` file.
That is usually what you want in production.

## Notes

- Never commit `.env`.
- Commit `.env.example` with every required key and safe dummy values.
- Add `.env` to `.gitignore` and ensure `storage/`, `vendor/` and `node_modules/` stay ignored as they are today.
- Use `safeLoad()` on servers where env is injected, `load()` in local development if you want a loud failure when `.env` is missing.
- Use `createUnsafeImmutable()` only if you must read via `getenv()`.
- Keep `.env` at `APP_ROOT` (`__DIR__` next to `index.php`) so `createImmutable(APP_ROOT)` finds it without extra paths.
- Do not store config that is not secret in `.env` (for example `forceNonWww` or `version`), keep it in `config/config.php`.
- phpdotenv parses `.env` on every request.
- There is no caching to configure, so no extra step is needed for opcache.
- If you add `env()` or change `config/config.php` to read `$_ENV`, restart `php -S` or `npm run dev` so the new file is picked up.
