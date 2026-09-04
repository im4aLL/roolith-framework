# Configuration

All application configuration is stored in `config/config.php`.
Under the hood the framework uses [roolith/config](https://github.com/im4aLL/roolith-config) `^2.0` (PHP `>=8.0`).

The framework already requires `roolith/config: ^2.0` in `composer.json`, and `composer.lock` resolves to `2.0.0`.
No code change was needed for the upgrade: `Config::get()`, `Config::setEnv()`, and `Config::env()` keep the same signatures.
What changed in 2.0 is stricter validation, documented merge semantics, and a breaking change to environment detection (see Upgrade note below).

## Setup

Two constants wire the library up. Both live in `constant.php`, which `App\Core\System::__construct()` loads before anything touches `Config`.

```php
// constant.php
// const ROOLITH_ENV = 'production'; // optional, see Environment precedence
const ROOLITH_CONFIG_ROOT = APP_ROOT . '/config';
```

- `ROOLITH_CONFIG_ROOT` (required): directory holding `config.php` plus optional `<env>.config.php` files. Must exist and be readable, otherwise `Config::get()` throws `Roolith\Configuration\Exception\Exception`.
- `ROOLITH_ENV` (optional): default environment name. Read once on first `Config` init. `Config::setEnv()` or process env `ROOLITH_ENVIRONMENT` overrides it.

## The Config File

```php
<?php
return [
    /**
     * Base URL for app
     */
    "baseUrl" => "http://localhost:8080/",

    /**
     * Database configuration
     */
    "database" => [
        "host" => "localhost",
        "name" => "roolith_cms",
        "user" => "root",
        "pass" => "",
    ],

    /**
     * For domain to have www or not www in domain
     */
    "forceNonWww" => true,

    /**
     * Current app version
     */
    "version" => time(),
];
```

Set `database` to `null` if your application does not need a database (`System::bootstrap()` skips connecting when it is falsy).

## Reading Values

Use dot notation to read nested values.

```php
use Roolith\Configuration\Config;

Config::get('baseUrl');
Config::get('database.host');
Config::get('a.b'); // nested values
Config::get('mail.host'); // nested values from .env wiring, see Using Dot Env
```

Behavior:

- Missing keys return silent `null` (stored `null` values are also returned as `null`, so use `Config::get('database') !== null` to test presence, not truthiness alone).
- Invalid keys throw `Roolith\Configuration\Exception\InvalidArgumentException`. Valid keys match `[A-Za-z0-9_.-]+` with no empty segments, so `""`, `".a"`, `"a."`, `"a..b"`, and `"a b"` throw, while `"0"` and `"missing-key-xyz"` are valid and return `null` when absent.
- `config.php` returning a non-array, or an unreadable file, throws `Roolith\Configuration\Exception\Exception`.

To skip auto environment loading for a specific key, pass `true` as the second argument.

```php
Config::get('staging.database', true); // resolves staging.config.php literally, ignores active env
```

With an active env and without the flag, dotted keys first try `env.key`, then fall back to a literal lookup of the full dotted path. So `Config::get('staging.database')` under `production` still resolves the literal `staging.database` when `production.staging.database` is missing.

## Environment Specific Config

The framework picks up environment specific files when an environment is set.
Place them next to `config.php`.

```text
config/config.php
config/development.config.php
config/production.config.php
```

Example `config/production.config.php`:

```php
<?php
return [
    'database' => 'productionDatabase',
    'log' => [
        'path' => '/var/log/app.log',
    ],
    'a' => [
        'b' => 'c',
    ],
];
```

Set the environment either via the constant in `constant.php`, via process env, or at runtime.

```php
// constant.php
const ROOLITH_ENV = 'development';

// or via process env before Config boots
putenv('ROOLITH_ENVIRONMENT=production');

// or at runtime (takes effect immediately, no reload needed)
Config::setEnv('production');
```

Values from the environment file override the base `config.php` values.

```php
var_dump(Config::get('database')); // value from production.config.php
var_dump(Config::env()); // production
```

### Environment precedence

Highest first:

1. `Config::setEnv('production')` (writes process env `ROOLITH_ENVIRONMENT`, takes effect immediately because all env files are preloaded at init).
2. `ROOLITH_ENV` constant, read once on first init when no process env value exists yet.
3. `local` default, which uses only `config.php`.

`Config::env()` reads the namespaced process env key `ROOLITH_ENVIRONMENT` and returns the name, or `false` when nothing has been seeded yet (i.e. before the first `Config::get()` / `getInstance()` call). After boot without explicit env it returns `'local'`.

Notes:

- `local.config.php` is ignored: `local` always uses only `config.php`.
- `default.config.php` is reserved and ignored, since `config.php` is already loaded under the `default` key.
- Env names must be non-empty strings without dots (`[A-Za-z0-9_-]+`), otherwise `setEnv()` / init throws `InvalidArgumentException`.
- A different `ROOLITH_CONFIG_ROOT` requires a fresh process because PHP constants cannot be redefined in-process.
- `isDevEnvironment()` / `isProductionEnvironment()` in `app/Utils/functions.php` check the `ROOLITH_ENV` constant only, not `Config::env()`. If you switch env at runtime with `Config::setEnv()`, those helpers do not follow. Use `Config::env()` when you need the effective environment.

### Merge semantics (per-key shadowing, no deep merge)

Each `get()` resolves one full dot-notation path only:

- `Config::get('database')` under `production` returns the production value when present, else the `config.php` value. The env file is checked before `config.php`, so a top-level `production` key in `config.php` never shadows the env file.
- `Config::get('log.path')` falls back to `config.php` when the env file has no `log` key.
- Fetching a parent array that exists in the env file returns that env array as-is; child keys from `config.php` are NOT deep-merged into it.
- A bare env name returns its file array: `Config::get('staging', true)` resolves `staging.config.php`.
- When the first dotted segment names an env file, that file wins over `config.php` (e.g. `staging.database` resolves from `staging.config.php` first).

### Reset (tests and reload)

`Config::reset()` is a test and reload utility on the concrete class only, not part of the `ConfigInterface` consumer contract.

```php
Config::reset(); // clear singleton, loaded data, and env state
Config::reset(false); // force re-init from the current root while preserving env
```

## Upgrade Note (2.0 Breaking Change)

The generic `environment` process env var is no longer read. If you ever set env via `putenv('environment=...')`, switch to one of these:

```php
Config::setEnv('production');
// or
define('ROOLITH_ENV', 'production');
// or
putenv('ROOLITH_ENVIRONMENT=production');
```

The framework itself never used the generic `environment` key, so no framework code needed changing. Just make sure deployment scripts and Docker / server configs export `ROOLITH_ENVIRONMENT` (or define `ROOLITH_ENV`), not `environment`.
