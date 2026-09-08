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

## Advanced keys

These keys are optional. You do not need them in `config/config.php` - setting `.env` alone takes effect. An omitted key reads as `null` from `Config::get()` and the code falls back to `Env` directly. `ConfigValidator` skips `null` and validates present values only. Add a key back only for an explicit `config.php` override, kept inline with `Env::get()` and no extra parsing logic.

| Config key | `.env` key | Default |
| --- | --- | --- |
| `logPath` | `LOG_PATH` | `APP_ROOT/storage/logs/app.log` |
| `logEnabled` | `LOG_ENABLED` | `false` (`0`) |
| `cookiePath` | `COOKIE_PATH` | `/` |
| `cookieDomain` | `COOKIE_DOMAIN` | empty (host-only) |
| `cookieSecure` | `COOKIE_SECURE` | derived from `baseUrl` scheme |
| `cookieSameSite` | `COOKIE_SAMESITE` | `Lax` |
| `sessionLifetime` | `SESSION_LIFETIME` | `0` (until browser closes) |
| `trustedProxies` | `TRUSTED_PROXIES` | empty (trust none) |
| `securityHeaders` | `SECURITY_HEADERS` | `true` (on) |

### logPath

- Env: `LOG_PATH`, default `APP_ROOT/storage/logs/app.log` via `App\Core\Logger::defaultLogPath()`
- Allowed: non-empty path, no null bytes, no `..` segments; `\` normalized to `/`; relative paths resolve against `APP_ROOT`
- Override:

```php
'logPath' => Env::get('LOG_PATH', APP_ROOT . '/storage/logs/app.log'),
```

### logEnabled

- Env: `LOG_ENABLED`, default `false` (`'0'`) via `App\Core\Logger::defaultLogEnabled()`
- Allowed: boolean (`1`/`0`, `true`/`false`); `false` drops debug/info/notice, warning and above are always written
- Override:

```php
'logEnabled' => filter_var(Env::get('LOG_ENABLED', '0'), FILTER_VALIDATE_BOOLEAN),
```

### cookiePath

- Env: `COOKIE_PATH`, default `'/'` via `App\Core\Session::cookieParams()` and `App\Core\Storage::cookieOptions()`
- Allowed: non-empty string
- Override:

```php
'cookiePath' => Env::get('COOKIE_PATH', '/'),
```

### cookieDomain

- Env: `COOKIE_DOMAIN`, default `''` (host-only) via `Session` and `Storage`
- Allowed: string (empty means host-only)
- Override:

```php
'cookieDomain' => Env::get('COOKIE_DOMAIN', ''),
```

### cookieSecure

- Env: `COOKIE_SECURE`, default derived from `baseUrl` scheme via `App\Core\Session::defaultSecure()` (true on `https`, false on plain `http` so local dev works, true when `baseUrl` is missing)
- Allowed: boolean; explicit `1`/`0` always wins over derivation
- Override:

```php
'cookieSecure' => Env::get('COOKIE_SECURE') !== null ? filter_var(Env::get('COOKIE_SECURE'), FILTER_VALIDATE_BOOLEAN) : \App\Core\Session::defaultSecure(),
```

Set `COOKIE_SECURE` in `.env` alone without re-adding the key; re-add the key only for an explicit `config.php` override, and always derive the default from `Session::defaultSecure()` instead of hardcoding `false` so `https` keeps Secure on by default.

### cookieSameSite

- Env: `COOKIE_SAMESITE`, default `'Lax'` via `Session` and `Storage`
- Allowed: `Lax`, `Strict`, `None`; `None` requires `cookieSecure=true` (browsers reject `None` without `Secure`)
- Override:

```php
'cookieSameSite' => Env::get('COOKIE_SAMESITE', 'Lax'),
```

### sessionLifetime

- Env: `SESSION_LIFETIME`, default `0` via `Session::cookieParams()`
- Allowed: int `>= 0`; `0` means until the browser closes
- Override:

```php
'sessionLifetime' => (int) Env::get('SESSION_LIFETIME', '0'),
```

### trustedProxies

- Env: `TRUSTED_PROXIES`, default `[]` (trust none, fail-closed) via `trustedProxies()` in `app/Utils/functions.php` falling back to `Env::get('TRUSTED_PROXIES')`
- Allowed: array of non-empty IP strings; comma-separated env list, exact IP match only, CIDR not supported, invalid IPs filtered out
- Override: prefer the `trustedProxies()` fallback over parsing in `config.php`; only add the key manually for an explicit override:

```php
'trustedProxies' => [],
```

```bash
# .env
TRUSTED_PROXIES=10.0.0.1,10.0.0.2
```

### securityHeaders

- Env: `SECURITY_HEADERS`, default `true` via `App\Core\System::sendSecurityHeaders()` (`is_bool` check falls back to true)
- Allowed: boolean; `false` disables baseline CSP/nosniff/frame/referrer/HSTS headers
- Override:

```php
'securityHeaders' => filter_var(Env::get('SECURITY_HEADERS', '1'), FILTER_VALIDATE_BOOLEAN),
```

## Going further

`config/config.md` is the source mirror for key names and shapes. For `.env` file format and loading rules, see [Using Dot Env](/using-dot-env).
