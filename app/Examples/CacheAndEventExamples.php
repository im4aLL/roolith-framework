<?php
namespace App\Examples;

use Roolith\Caching\Cache\CacheFactory;
use Roolith\Event\Event;

/**
 * When-to-use examples for shipped cache and event packages.
 *
 * Cache (roolith/cache, file driver) is for expensive reads: config
 * snapshots or model queries that change rarely. Event
 * (roolith/event) is for decoupled side effects: user created,
 * order placed, and similar domain moments.
 *
 * Both are on-demand: the default request never touches them. Call
 * these helpers only where the win is measured.
 */
final class CacheAndEventExamples
{
    /**
     * Cache a model query result for one hour.
     *
     * Read-through pattern: return the cached rows when present,
     * otherwise run the loader, store it for $ttl seconds, and return
     * it. Always check has() before trusting get() because get()
     * returns false on miss, expiry, or corrupt entries. Each query
     * needs its own $key so different model queries never collide.
     *
     * @param string $key Cache key unique to this query.
     * @param callable $loader Expensive loader returning cacheable data.
     * @param int $ttl Time to live in seconds.
     * @return mixed Cached or freshly loaded data.
     */
    public static function cachedModelQuery(string $key, callable $loader, int $ttl = 3600): mixed
    {
        if (CacheFactory::has($key)) {
            $cached = CacheFactory::get($key);

            if ($cached !== false) {
                return $cached;
            }
        }

        $fresh = $loader();
        CacheFactory::put($key, $fresh, $ttl);

        return $fresh;
    }

    /**
     * Cache an expensive config snapshot.
     *
     * Same read-through shape for config-like data that rarely
     * changes. Flush with CacheFactory::remove($key) when the source
     * changes.
     *
     * @param string $key Cache key.
     * @param callable $loader Loader returning the snapshot.
     * @param int $ttl Time to live in seconds.
     * @return mixed Cached snapshot.
     */
    public static function cachedConfig(string $key, callable $loader, int $ttl = 3600): mixed
    {
        if (CacheFactory::has($key)) {
            $cached = CacheFactory::get($key);

            if ($cached !== false) {
                return $cached;
            }
        }

        $fresh = $loader();
        CacheFactory::put($key, $fresh, $ttl);

        return $fresh;
    }

    /**
     * Fire a user-created event with decoupled listeners.
     *
     * Register listeners once at boot (for example in routes.php or a
     * service provider), then trigger with the new user payload.
     * trigger() returns ordered listener results, [] when nothing
     * matched; returning false from a listener stops propagation.
     *
     * @param array<string, mixed> $user New user payload.
     * @return array<int, mixed> Ordered listener results.
     */
    public static function userCreated(array $user): array
    {
        return Event::trigger('user.created', [$user]);
    }

    /**
     * Register the example user-created listeners.
     *
     * Call once at boot. Kept separate from userCreated() so tests
     * can register doubles without touching global state.
     *
     * @return void
     */
    public static function registerUserCreatedListeners(): void
    {
        Event::listen('user.created', static function (array $user): string {
            return 'welcome:' . (string) ($user['email'] ?? '');
        });
    }
}
