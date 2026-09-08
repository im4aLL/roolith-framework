<?php
namespace App\Core;

/**
 * Session-backed rate limiter bucket.
 *
 * 013a limits (documented, not yet migrated): attempts live in $_SESSION, so
 * an attacker clearing cookies gets a fresh bucket, and buckets do not work
 * across servers unless PHP sessions are externalized. 013b plan: add a
 * file/cache/DB driver using roolith/cache behind RateLimiterInterface and
 * swap call sites to the interface; this class already implements that seam.
 */
class SessionRateLimiter implements RateLimiterInterface
{
    /**
     * Namespaced top-level session key avoiding app collisions.
     *
     * @var string
     */
    private string $rateLimitKey = '_roolith_rate_limit';

    /**
     * Bucket key for the feature (for example login, contact).
     *
     * @var string
     */
    private string $key;

    /**
     * Max attempts per window.
     *
     * @var int
     */
    private int $maxAttempts;

    /**
     * Window length in seconds.
     *
     * @var int
     */
    private int $windowSeconds;

    /**
     * Frozen clock for tests (null means use time()).
     *
     * @var int|null
     */
    private static ?int $nowOverride = null;

    /**
     * Create a rate limiter bucket backed by the session.
     *
     * Session startup goes through the shared Session helper so cookie
     * flags stay consistent across the app.
     *
     * @param string $key Bucket key namespaced per feature (for example login:127.0.0.1).
     * @param int $maxAttempts Max attempts per window.
     * @param int $windowSeconds Window length in seconds.
     * @return void
     */
    public function __construct(string $key, int $maxAttempts = 3, int $windowSeconds = 300)
    {
        Session::start();

        $this->key = $key;
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;

        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }

        if (!isset($_SESSION[$this->rateLimitKey]) || !is_array($_SESSION[$this->rateLimitKey])) {
            $_SESSION[$this->rateLimitKey] = [];
        }

        if (!isset($_SESSION[$this->rateLimitKey][$this->key]) || !is_array($_SESSION[$this->rateLimitKey][$this->key])) {
            $_SESSION[$this->rateLimitKey][$this->key] = [];
        }
    }

    /**
     * Record one attempt and report whether the bucket is now blocked.
     *
     * Prunes expired entries first, appends now, persists unconditionally,
     * then reports count >= max. Call this on each state-changing attempt;
     * use tooManyAttempts() for pure pre-checks.
     *
     * @return bool True when the bucket reached the max after recording.
     */
    public function hit(): bool
    {
        $now = self::now();
        $attempts = $this->pruned($now);
        $attempts[] = $now;
        $this->persist($attempts);

        return count($attempts) >= $this->maxAttempts;
    }

    /**
     * Check whether the bucket is blocked without recording an attempt.
     *
     * Prunes expired entries and always persists the pruned list, including
     * on the blocked path, so blocked buckets shrink after the window
     * instead of growing forever.
     *
     * @return bool True when stored attempts reach the max.
     */
    public function tooManyAttempts(): bool
    {
        $attempts = $this->pruned(self::now());
        $this->persist($attempts);

        return count($attempts) >= $this->maxAttempts;
    }

    /**
     * Count unexpired attempts (test seam plus remaining() support).
     *
     * Prunes expired entries and persists the pruned list (L6) so read-only
     * checks also shrink storage instead of leaving stale timestamps.
     *
     * @return int Number of attempts inside the window.
     */
    public function count(): int
    {
        $attempts = $this->pruned(self::now());
        $this->persist($attempts);

        return count($attempts);
    }

    /**
     * Clear rate limit
     *
     * @return void
     */
    public function clear(): void
    {
        if (isset($_SESSION[$this->rateLimitKey][$this->key])) {
            unset($_SESSION[$this->rateLimitKey][$this->key]);
        }
    }

    /**
     * Freeze the clock for tests.
     *
     * @param int|null $now Unix timestamp or null to use time().
     * @return void
     */
    public static function setNowForTests(?int $now): void
    {
        self::$nowOverride = $now;
    }

    /**
     * Clear test state (clock override).
     *
     * @return void
     */
    public static function resetForTests(): void
    {
        self::$nowOverride = null;
    }

    /**
     * Get the current time honoring the test override.
     *
     * @return int Unix timestamp.
     */
    private static function now(): int
    {
        return self::$nowOverride ?? time();
    }

    /**
     * Load attempts pruned to the current window.
     *
     * @param int $now Current unix timestamp.
     * @return array<int, int> Unexpired timestamps.
     */
    private function pruned(int $now): array
    {
        $stored = $_SESSION[$this->rateLimitKey][$this->key] ?? [];

        if (!is_array($stored)) {
            return [];
        }

        $cutoff = $now - $this->windowSeconds;
        $kept = array_filter($stored, static fn ($timestamp): bool => is_int($timestamp) && $timestamp > $cutoff);

        return array_values($kept);
    }

    /**
     * Persist the attempt list unconditionally.
     *
     * @param array<int, int> $attempts Timestamps to store.
     * @return void
     */
    private function persist(array $attempts): void
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }

        if (!isset($_SESSION[$this->rateLimitKey]) || !is_array($_SESSION[$this->rateLimitKey])) {
            $_SESSION[$this->rateLimitKey] = [];
        }

        $_SESSION[$this->rateLimitKey][$this->key] = array_values($attempts);
    }
}
