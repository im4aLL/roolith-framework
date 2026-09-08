# Cache

File-based cache for expensive reads such as DB queries or config snapshots. Only the file driver is supported.

Use this for data that is slow to build and shared between requests. For per-visitor state use [Storage](/storage) sessions instead.

## Setup

Pick a writable directory once, before the first cache call.

```php
<?php
define('ROOLITH_CACHE_DIR', APP_ROOT . '/cache');

require APP_ROOT . '/vendor/autoload.php';
```

The directory is created automatically. Without this, cache falls back to the system temp dir.

To override per call instead:

```php
use Roolith\Caching\Cache\CacheFactory;

CacheFactory::driver('file', ['dir' => APP_ROOT . '/cache']);
```

## Basic usage

Always pass TTL in seconds. `get()` returns `false` on miss or expiry, so check `has()` first.

```php
<?php
use Roolith\Caching\Cache\CacheFactory;

CacheFactory::put('user_1', $user, 3600); // save for 1 hour
$exists = CacheFactory::has('user_1'); // bool
$user = CacheFactory::get('user_1'); // value, or false on miss / expiry
CacheFactory::remove('user_1'); // delete one key
CacheFactory::flush(); // delete everything
```

Common TTLs: `60` for a minute, `3600` for an hour.

## Remember pattern

Check the cache, fall back to the loader, then store. Give each query its own key so entries never collide, and delete the key when the source data changes.

```php
<?php
use Roolith\Caching\Cache\CacheFactory;

function activeUsers(callable $loader): mixed
{
    if (CacheFactory::has('users:active')) {
        $cached = CacheFactory::get('users:active');

        if ($cached !== false) {
            return $cached;
        }
    }

    $fresh = $loader();
    CacheFactory::put('users:active', $fresh, 3600);

    return $fresh;
}

$users = activeUsers(fn () => $db->query('SELECT * FROM users'));
CacheFactory::remove('users:active'); // invalidate when users change
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

## Worked example

See `app/Examples/CacheAndEventExamples.php` (`cachedModelQuery`, `cachedConfig`) for the read-through shape. Use it only for expensive, rarely-changing reads - slow queries or config snapshots - never by default on every request.
