<?php
namespace Tests;

use App\Core\SessionRateLimiter;
use PHPUnit\Framework\TestCase;

/**
 * Session rate limiter: prune-on-block persistence, per-feature namespacing,
 * hit versus check separation, and pruned persistence on count().
 */
class RateLimiterTest extends TestCase
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $sessionBackup = null;

    /**
     * @var bool
     */
    private bool $hadSession = false;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->hadSession = isset($_SESSION);
        $this->sessionBackup = $this->hadSession ? $_SESSION : null;
        $_SESSION = [];

        SessionRateLimiter::resetForTests();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        SessionRateLimiter::resetForTests();
        SessionRateLimiter::setNowForTests(null);

        if ($this->hadSession) {
            $_SESSION = $this->sessionBackup ?? [];
        } else {
            unset($_SESSION);
        }
    }

    /**
     * Rate limiter must prune on the blocked path and persist.
     *
     * @return void
     */
    public function testRateLimiterPruneOnBlock(): void
    {
        SessionRateLimiter::setNowForTests(1000);

        $limiter = new SessionRateLimiter('phase3-login', 2, 10);

        $this->assertFalse($limiter->tooManyAttempts());
        $this->assertFalse($limiter->hit());
        $this->assertTrue($limiter->hit());
        $this->assertTrue($limiter->tooManyAttempts());

        // Pure check must not increment: still two stored attempts.
        $this->assertSame(2, $limiter->count());

        // Blocked path persists the pruned list.
        $stored = $_SESSION['_roolith_rate_limit']['phase3-login'] ?? null;

        $this->assertIsArray($stored);
        $this->assertCount(2, $stored);

        // After the window the bucket prunes and persists the shrink.
        SessionRateLimiter::setNowForTests(1012);

        $this->assertFalse($limiter->tooManyAttempts());
        $this->assertSame(0, $limiter->count());

        $pruned = $_SESSION['_roolith_rate_limit']['phase3-login'] ?? null;

        $this->assertIsArray($pruned);
        $this->assertCount(0, $pruned);

        // Namespaced key must not collide with app session data.
        $_SESSION['rate_limit'] = 'app-value';

        $other = new SessionRateLimiter('phase3-other', 1, 10);
        $other->hit();

        $this->assertSame('app-value', $_SESSION['rate_limit']);

        $limiter->clear();
        $other->clear();
    }

    /**
     * count() persists the pruned list.
     *
     * @return void
     */
    public function testRateLimiterCountPersistsPrunedList(): void
    {
        SessionRateLimiter::setNowForTests(2000);

        $limiter = new SessionRateLimiter('review-l6', 5, 10);
        $limiter->hit();
        $limiter->hit();

        $this->assertSame(2, $limiter->count());

        SessionRateLimiter::setNowForTests(2015);

        $this->assertSame(0, $limiter->count());

        $stored = $_SESSION['_roolith_rate_limit']['review-l6'] ?? null;

        $this->assertIsArray($stored);
        $this->assertCount(0, $stored);

        $limiter->clear();
    }
}
