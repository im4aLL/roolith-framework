# Cache

The framework ships with [roolith/cache](https://github.com/im4aLL/roolith-cache), a [PSR-6](http://www.php-fig.org/psr/psr-6/) and [PSR-16](http://www.php-fig.org/psr/psr-16/) compatible cache system.
It currently supports the file based driver.

## Factory

The quickest way to cache values.

```php
<?php
use Roolith\Caching\Cache\CacheFactory;

define('ROOLITH_CACHE_DIR', __DIR__ . '/cache');

// will save cache
CacheFactory::put('a', 'b', 3600);

// will retrieve cache
CacheFactory::get('a');

// you can select driver and store
CacheFactory::driver('file')->put('a', 'b', 3600);

// will return boolean
CacheFactory::has('foo');

// will delete cache item
CacheFactory::remove('foo');

// will delete all cache items
CacheFactory::flush();
```

## Cache Instance

```php
<?php
use Roolith\Caching\Cache\Cache;

$cache = new Cache();
$cache->driver('file', ['dir' => __DIR__ . '/cache']);

print_r($cache->get('foo'));
```

## PSR-6 Pool

```php
<?php
use Roolith\Caching\Driver\FileDriver;
use Roolith\Caching\Cache\Pool;

$fileDriver = new FileDriver(['dir' => __DIR__ . '/cache']);
$pool = new Pool($fileDriver);
$item = $pool->getItem('foo');

if (!$item->isHit()) {
    $item->set([1, 2, 3])->expiresAfter(3600);
    $pool->save($item);
}

print_r($item->get());
```

## PSR-16 Simple Cache

```php
<?php
use Roolith\Caching\Cache\SimpleCache;
use Roolith\Caching\Driver\FileDriver;

$fileDriver = new FileDriver(['dir' => __DIR__ . '/cache']);
$simpleCache = new SimpleCache($fileDriver);

print_r($simpleCache->get('foo'));
```
