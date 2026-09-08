<?php
namespace App\Core;

/**
 * Session-backed rate limiter bucket.
 */
class SessionRateLimiter {
    private string $key;
    private int $maxAttempts;
    private int $windowSeconds;
    private string $rateLimitKey = 'rate_limit';

    /**
     * Create a rate limiter bucket backed by the session.
     *
     * Session startup goes through the shared Session helper so cookie
     * flags stay consistent across the app.
     *
     * @param string $key Bucket key.
     * @param int $maxAttempts Max attempts per window.
     * @param int $windowSeconds Window length in seconds.
     * @return void
     */
    public function __construct(string $key, int $maxAttempts = 3, int $windowSeconds = 300) {
        Session::start();

        $this->key = $key;
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;

        if (!isset($_SESSION[$this->rateLimitKey][$this->key])) {
            $_SESSION[$this->rateLimitKey][$this->key] = [];
        }
    }

    /**
     * Check whether too many attempts or not
     *
     * @return bool
     */
    public function tooManyAttempts(): bool {
        $now = time();
        $attempts = $_SESSION[$this->rateLimitKey][$this->key];

        // Remove expired attempts
        $attempts = array_filter($attempts, fn($timestamp) => $timestamp > ($now - $this->windowSeconds));

        if (count($attempts) >= $this->maxAttempts) {
            return true;
        }

        // Add new attempt and update session
        $attempts[] = $now;
        $_SESSION[$this->rateLimitKey][$this->key] = $attempts;

        return false;
    }

    /**
     * Clear rate limit
     *
     * @return void
     */
    public function clear(): void {
        unset($_SESSION[$this->rateLimitKey][$this->key]);
    }
}
