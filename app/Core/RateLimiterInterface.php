<?php
namespace App\Core;

/**
 * Rate limiter driver contract for future backends.
 *
 * SessionRateLimiter is the current session-backed implementation. The
 * 013b plan migrates to a roolith/cache (file/cache/DB) driver implementing
 * this same interface so call sites swap backends without logic changes.
 */
interface RateLimiterInterface
{
    /**
     * Record one attempt and report whether the bucket is now blocked.
     *
     * @return bool True when attempts reach the max after recording.
     */
    public function hit(): bool;

    /**
     * Check whether the bucket is blocked without recording an attempt.
     *
     * @return bool True when attempts reach the max.
     */
    public function tooManyAttempts(): bool;

    /**
     * Clear the bucket for the configured key.
     *
     * @return void
     */
    public function clear(): void;
}
