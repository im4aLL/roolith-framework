# Configuration

All application configuration is stored in `config/config.php`.
Under the hood the framework uses [roolith/config](https://github.com/im4aLL/roolith-config).

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

Set `database` to `null` if your application does not need a database.

## Reading Values

Use dot notation to read nested values.

```php
use Roolith\Configuration\Config;

Config::get('baseUrl');
Config::get('database.host');
Config::get('a.b'); // nested values
```

To skip auto environment loading for a specific key, pass `true` as the second argument.

```php
Config::get('staging.database', true);
```

## Environment Specific Config

The framework picks up environment specific files when an environment is set.
Place them next to `config.php`.

```text
config/development.config.php
config/production.config.php
```

Set the environment either via the constant in `constant.php` or at runtime.

```php
// constant.php
const ROOLITH_ENV = 'development';

// or at runtime
Config::setEnv('production');
```

Values from the environment file override the base `config.php` values.

```php
var_dump(Config::get('database')); // value from production.config.php
var_dump(Config::env()); // production
```
