<?php
namespace App\Core;

class SessionRateLimiter {
    private string $key;
    private int $maxAttempts;
    private int $windowSeconds;
    private string $rateLimitKey = 'rate_limit';

    public function __construct(string $key, int $maxAttempts = 3, int $windowSeconds = 300) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

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
