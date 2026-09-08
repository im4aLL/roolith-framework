# Configuration

All application settings live in `config/config.php`, and every value comes from `.env`. Copy `.env.example` to `.env` and edit that file; you rarely need to touch `config.php` itself.

```bash
cp .env.example .env
```

## Settings you will actually change

| What | `.env` key | Default |
| --- | --- | --- |
| Site URL | `APP_URL` | `http://localhost:8080/` |
| Environment | `APP_ENV` | `production` (only exact `development` enables verbose errors) |
| Database host / name / user / pass | `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` | empty (runs without a database) |
| Force non-www (`1`) or www (`0`) | `FORCE_NON_WWW` | `1` |
| Asset version | `APP_VERSION` | `time()` in development, `1.0.0` otherwise |
| Vite dev server | `VITE_DEV_SERVER` | empty (uses built assets) |

Leave `DB_HOST` empty to run without a database. Set `APP_VERSION=1.2.3` in production so browsers cache assets; development uses `time()` automatically for no-cache reloads.

## Reading values

Use `Config::get()` with dot notation anywhere after boot. Missing keys return `null`.

```php
use Roolith\Configuration\Config;

Config::get('baseUrl'); // e.g. https://example.com/
Config::get('database.host'); // null when no database is configured
Config::get('database') !== null; // testing presence: compare to null, stored null also reads as null
```

## Environments

Set the environment in `.env` and optionally add an override file next to `config.php`. Values in the environment file win over `config.php`.

```text
config/config.php
config/development.config.php
config/production.config.php
```

```bash
# .env
APP_ENV=development
```

```php
<?php
// config/production.config.php
return [
    'database' => [
        'host' => 'localhost',
        'name' => 'roolith_cms',
        'user' => 'root',
        'pass' => '',
    ],
];
```

```php
Config::get('database'); // value from production.config.php when APP_ENV=production
Config::env(); // production
```

## Common tasks

Change the site URL by setting `APP_URL=https://example.com/` in `.env`. Disable the database by emptying `DB_HOST` or `DB_NAME`. Force `www` with `FORCE_NON_WWW=0`. Point the frontend at the dev server with `VITE_DEV_SERVER=http://localhost:5173` (see [Frontend Workflow](/frontend-workflow)).

## Going further

Advanced keys (`logPath`, `logEnabled`, `cookiePath`, `cookieDomain`, `cookieSecure`, `cookieSameSite`, `sessionLifetime`, `trustedProxies`, `securityHeaders`) default in code and can be set via `.env` alone without editing `config.php`; see `config/config.md` for names and shapes. For `.env` file format and loading rules, see [Using Dot Env](/using-dot-env).
