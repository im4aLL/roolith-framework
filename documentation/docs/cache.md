# Cache

The framework ships with [roolith/cache](https://github.com/im4aLL/roolith-cache).
It only supports the file driver.

## Setup

Define the cache directory before `vendor/autoload.php` is loaded.
The directory is created automatically.

```php
<?php
define('ROOLITH_CACHE_DIR', APP_ROOT . '/cache');

require APP_ROOT . '/vendor/autoload.php';
```

If you cannot define the constant early, use one of these instead.
Explicit config wins over everything else.

```php
CacheFactory::driver('file', ['dir' => APP_ROOT . '/cache']);
CacheFactory::$fileDriverCacheDir = APP_ROOT . '/cache';
```

## Basic usage

This covers most use cases.
TTL is in seconds and defaults to 3600 (1 hour).

```php
<?php
use Roolith\Caching\Cache\CacheFactory;

CacheFactory::put('user_1', $user, 3600); // save
$user = CacheFactory::get('user_1'); // value, or false on miss / expiry
$exists = CacheFactory::has('user_1'); // bool
CacheFactory::remove('user_1'); // delete one key
CacheFactory::flush(); // delete everything
```

Always check `has()` before trusting `get()`, since `get()` returns `false` for missing, expired, or corrupt entries.

## TTL examples

```php
CacheFactory::put('short', $value, 60); // 1 minute
CacheFactory::put('hour', $value, 3600); // 1 hour (default)
```

## Other ways to use it

Use `Cache` directly when you want an instance instead of statics.

```php
<?php
use Roolith\Caching\Cache\Cache;

$cache = new Cache();
$cache->driver('file', ['dir' => APP_ROOT . '/cache']);
$cache->put('foo', 'bar', 3600);
echo $cache->get('foo');
```

Use PSR-6 or PSR-16 only when a library requires that interface.

```php
<?php
use Roolith\Caching\Cache\Pool;
use Roolith\Caching\Cache\SimpleCache;
use Roolith\Caching\Driver\FileDriver;

$driver = new FileDriver(['dir' => APP_ROOT . '/cache']);

// PSR-6
$pool = new Pool($driver);
$item = $pool->getItem('foo');
if (!$item->isHit()) {
    $item->set([1, 2, 3])->expiresAfter(3600);
    $pool->save($item);
}

// PSR-16
$simple = new SimpleCache($driver);
$simple->set('foo', 'bar', 3600);
echo $simple->get('foo');
```
