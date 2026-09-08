# Config

`config/config.php` is intentionally minimal: a return array using only `App\Core\Env`, no intermediate variables or parsing logic. Copy `.env.example` to `.env` for local overrides. See `.env.example` for the full env list; advanced keys below default automatically in code.

## Minimal keys (in `config.php`)

### baseUrl

- Env: `APP_URL`, default `http://localhost:8080/`
- Allowed: valid `http` or `https` URL, non-empty
- Example: `APP_URL=https://example.com/`

### viteDevServer

- Env: `VITE_DEV_SERVER`, default `''` (empty uses built assets)
- Allowed: empty string or dev server URL
- Example: `VITE_DEV_SERVER=http://localhost:5173`

### database

- Env: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- Default: `null` when `DB_HOST` or `DB_NAME` is empty (runs without a database); otherwise `['host', 'name', 'user', 'pass']`
- Allowed: `null` or array with host/name/user/pass
- Example: `DB_HOST=127.0.0.1`, `DB_NAME=app`, `DB_USER=root`, `DB_PASS=secret`

### forceNonWww

- Env: `FORCE_NON_WWW`, default `1` (true)
- Allowed: boolean (`1`/`0`, `true`/`false` via `FILTER_VALIDATE_BOOLEAN`)
- Example: `FORCE_NON_WWW=0` forces `www`

### version

- Env: `APP_VERSION`, default `time()` in development else `1.0.0` in prod (set a fixed version in prod)
- Allowed: non-empty string or number
- Example: `APP_VERSION=1.2.3`

## Advanced keys (optional, default in code)

Omitted keys return `null` from `Config::get()` and fall back in code directly from `Env` - setting `.env` alone takes effect without re-adding the key. `ConfigValidator` skips `null` and validates present values only. Add the key back into `config/config.php` with an inline `Env::get()` call only for an explicit `config.php` override.

### logPath

- Env: `LOG_PATH`, default `APP_ROOT/storage/logs/app.log` via `App\Core\Logger::defaultLogPath()`
- Allowed: non-empty path, no null bytes, no `..` segments; `\` normalized to `/`; relative paths resolve against `APP_ROOT`; invalid override falls back to default in Logger
- Override: `'logPath' => Env::get('LOG_PATH', APP_ROOT . '/storage/logs/app.log'),`

### logEnabled

- Env: `LOG_ENABLED`, default `false` (`'0'`) via `App\Core\Logger::defaultLogEnabled()`
- Allowed: boolean; `false` drops debug/info/notice, warning and above always written
- Override: `'logEnabled' => filter_var(Env::get('LOG_ENABLED', '0'), FILTER_VALIDATE_BOOLEAN),`

### cookiePath

- Env: `COOKIE_PATH`, default `'/'` via `App\Core\Session::cookieParams()` and `App\Core\Storage::cookieOptions()`
- Allowed: non-empty string
- Override: `'cookiePath' => Env::get('COOKIE_PATH', '/'),`

### cookieDomain

- Env: `COOKIE_DOMAIN`, default `''` (host-only) via `Session` and `Storage`
- Allowed: string (empty means host-only)
- Override: `'cookieDomain' => Env::get('COOKIE_DOMAIN', ''),`

### cookieSecure

- Env: `COOKIE_SECURE`, default derived from `baseUrl` scheme via `App\Core\Session::defaultSecure()` (true on `https`, false on plain `http` so local dev works, true when baseUrl missing)
- Allowed: boolean; explicit `1`/`0` always wins over derivation
- Override: `'cookieSecure' => Env::get('COOKIE_SECURE') !== null ? filter_var(Env::get('COOKIE_SECURE'), FILTER_VALIDATE_BOOLEAN) : \App\Core\Session::defaultSecure(),`
- Note: `.env` alone works without re-adding the key (code falls back to `Env` then `Session::defaultSecure()`); re-add the key only for an explicit `config.php` override, and always derive the default from `Session::defaultSecure()` instead of hardcoding `false` so `https` keeps Secure on by default

### cookieSameSite

- Env: `COOKIE_SAMESITE`, default `'Lax'` via `Session` and `Storage`
- Allowed: `Lax`, `Strict`, `None`; `None` requires `cookieSecure=true` (browsers reject `None` without `Secure`)
- Override: `'cookieSameSite' => Env::get('COOKIE_SAMESITE', 'Lax'),`

### sessionLifetime

- Env: `SESSION_LIFETIME`, default `0` via `Session::cookieParams()`
- Allowed: int `>= 0`; `0` means until the browser closes
- Override: `'sessionLifetime' => (int) Env::get('SESSION_LIFETIME', '0'),`

### trustedProxies

- Env: `TRUSTED_PROXIES`, default `[]` (trust none, fail-closed) via `trustedProxies()` in `app/Utils/functions.php` falling back to `Env::get('TRUSTED_PROXIES')`
- Allowed: array of non-empty IP strings; comma-separated env list, exact IP match only, CIDR not supported, invalid IPs filtered out
- Override: parse `Env::get('TRUSTED_PROXIES', '')` in code or add `'trustedProxies' => [],` manually; prefer the `trustedProxies()` fallback over parsing in `config.php`

### securityHeaders

- Env: `SECURITY_HEADERS`, default `true` via `App\Core\System::sendSecurityHeaders()` (`is_bool` check falls back to true)
- Allowed: boolean; `false` disables baseline CSP/nosniff/frame/referrer/HSTS headers
- Override: `'securityHeaders' => filter_var(Env::get('SECURITY_HEADERS', '1'), FILTER_VALIDATE_BOOLEAN),`

## Example: adding an advanced key back

```php
<?php
use App\Core\Env;

return [
    "baseUrl" => Env::get('APP_URL', 'http://localhost:8080/'),
    "viteDevServer" => Env::get('VITE_DEV_SERVER', ''),
    "database" => Env::get('DB_HOST') !== null && Env::get('DB_NAME') !== null ? [
        "host" => (string) Env::get('DB_HOST'),
        "name" => (string) Env::get('DB_NAME'),
        "user" => Env::get('DB_USER', ''),
        "pass" => Env::get('DB_PASS', ''),
    ] : null,
    "forceNonWww" => filter_var(Env::get('FORCE_NON_WWW', '1'), FILTER_VALIDATE_BOOLEAN),
    "version" => Env::get('APP_VERSION', Env::isDevelopment() ? (string) time() : '1.0.0'),
    // Advanced override example:
    "logPath" => Env::get('LOG_PATH', APP_ROOT . '/storage/logs/app.log'),
];
```

Keep overrides inline (`Env::get()` only); do not add intermediate variables or parsing logic to `config.php`.
