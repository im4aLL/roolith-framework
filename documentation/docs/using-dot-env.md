# Using Dot Env

Anything that changes between environments (URLs, credentials, passwords) lives in a `.env` file, not in code. It loads automatically for web requests, and `.env` is already gitignored so secrets never get committed.

## Setup

Copy `.env.example` to `.env` and fill in real values:

```bash
cp .env.example .env
```

```ini
APP_URL=http://localhost:8080/
APP_ENV=development

DB_HOST=localhost
DB_NAME=roolith_cms
DB_USER=root
DB_PASS=
```

Commit `.env.example` with dummy values so collaborators know what is required. Never commit `.env`.

Supported syntax is `KEY=VALUE` lines with `#` comments, an optional `export` prefix, and optional single or double quotes. There is no variable expansion or multiline support; if you need those, replace the loader with [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) instead of growing the custom parser.

## Reading variables

Read through `Env::get()` so trimming and empty-handling stay consistent. Empty or whitespace-only counts as missing (the default is returned); `"0"` is kept.

```php
use App\Core\Env;

$host = Env::get('DB_HOST', 'localhost');
$pass = Env::get('DB_PASS', '');
```

Check which environment is active. Unset `APP_ENV` defaults to `production`, and only exactly `development` boots with verbose errors:

```php
use App\Core\Env;

$env = Env::appEnv(); // e.g. development, staging, production
$dev = Env::isDevelopment(); // true only for development
$prod = Env::isProduction(); // true for everything else
$stage = Env::is('staging', 'uat'); // true when active env matches any given name
```

## Wiring into config

Translate env variables once in `config/config.php`, then read them elsewhere via `Config::get()`. Everything from `.env` is a string, so cast numbers and booleans:

```php
<?php
use App\Core\Env;

return [
    'baseUrl' => Env::get('APP_URL', 'http://localhost:8080/'),
    'mail' => [
        'host' => Env::get('MAIL_HOST', 'smtp.example.com'),
        'port' => (int) Env::get('MAIL_PORT', '587'),
    ],
    'debug' => filter_var(Env::get('APP_DEBUG', '0'), FILTER_VALIDATE_BOOLEAN),
];
```

```php
use Roolith\Configuration\Config;

Config::get('baseUrl');
Config::get('mail.host');
```

Prefer `Config::get()` over reading `Env::get()` directly in controllers, so values stay centralized and mockable in tests. Do not add a custom `env()` helper reading `$_ENV`; it skips the trimming and empty-handling above.

Set `APP_ENV` to pick non-secret overrides from `config/development.config.php` or `config/production.config.php`, while `.env` keeps holding the secrets:

```ini
# .env
APP_ENV=development
```

```php
// config/development.config.php
<?php
return [
    'viteDevServer' => 'http://localhost:5173',
];
```

## Quoting and comments

```ini
# comment
export APP_ENV=development

QUOTED="foo#bar"
SINGLE='foo#bar'
WITH_COMMENT=foo # becomes foo
```

Prefer quotes when a value contains a `#` you want to keep literally. Anything after an unquoted ` #` is treated as a comment.

## Scripts and production

Web requests need no extra code. A standalone script that does not boot `System` loads the file first:

```php
<?php
\App\Core\Env::load(APP_ROOT);
```

In Docker or on a PaaS, inject variables via the environment instead of mounting a `.env` file. A missing `.env` is a safe no-op, and values already set in the environment always win over the file:

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
```

## Validation

The built-in loader does not validate. Bad values fail fast at bootstrap with a clear error, so a missing `APP_URL` or malformed `database` shows up immediately.

Only when you replaced the loader with phpdotenv, assert right after loading:

```php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
$dotenv->required(['APP_URL', 'DB_HOST', 'DB_NAME'])->notEmpty();
```

## Notes

- Never commit `.env`; commit `.env.example` with every required key.
- Keep non-secret config in `config/config.php`, not in `.env`.
- Keep `.env` at the project root (next to `index.php`) so it is found without extra paths.
- The file is parsed on every request; there is no cache to configure.
