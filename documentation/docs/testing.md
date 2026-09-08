# Testing

Run the full check locally before pushing. All commands run from the project root unless noted.

```bash
composer install
composer test
composer lint
composer analyse
composer audit
php roolith route:list
```

## Running tests

`composer test` runs PHPUnit with colors (`phpunit --colors=always`). Suites are defined in `phpunit.xml`: one `unit` suite pointing at `tests/`.

```bash
composer test
vendor/bin/phpunit --filter RateLimiterTest
vendor/bin/phpunit tests/RateLimiterTest.php
```

`composer lint` lints PHP sources (`find app config -name "*.php" -exec php -l {} \;`). CI additionally lints entry points:

```bash
composer lint
php -l index.php
php -l constant.php
php -l config/config.php
find app -name "*.php" -exec php -l {} \;
```

`php roolith route:list` prints the route table and validates handler references with `class_exists` plus `method_exists`. It exits `1` when any handler is invalid, so CI gates on it (see [CLI](/cli)).

```bash
php roolith route:list
```

## Tests layout

| Path | What lives there |
| --- | --- |
| `phpunit.xml` | `unit` suite, bootstrap `vendor/autoload.php`, directory `tests` |
| `tests/` | one `*Test.php` class per area, plus `.gitkeep` |
| `tests/EnvTest.php` | env loading, defaults, `development` detection |
| `tests/ConfigValidatorTest.php`, `tests/MinimalConfigTest.php` | config shape, optional-key skipping |
| `tests/RequestTest.php`, `tests/ResponsePipelineTest.php` | request parsing, response pipeline |
| `tests/CsrfTest.php`, `tests/CookieTest.php` | CSRF token, cookie flags |
| `tests/RateLimiterTest.php` | frozen-clock rate limit windows |
| `tests/FacadeResetTest.php` | `resetForTests()` isolation seams |
| `tests/ErrorHandlerTest.php`, `tests/ErrorPagesTest.php` | generic 500 with trace id, 404 page |
| `tests/SystemTest.php`, `tests/TrustedProxyTest.php`, `tests/SecurityHeadersTest.php` | boot chain, proxy trust, headers |

List the suite:

```bash
ls tests
```

## Isolation seams

Static facades cache singletons, so each test resets them in `setUp()` and `tearDown()`. Every facade exposes `resetForTests()` (vendor `Config` uses its existing `reset()`).

```php
use App\Core\DatabaseFactory;
use App\Core\Request;
use App\Core\RouterFactory;
use Roolith\Configuration\Config;

protected function setUp(): void
{
    if (!defined('APP_ROOT')) {
        define('APP_ROOT', dirname(__DIR__));
    }

    if (!defined('APP_VIEW_ROOT')) {
        define('APP_VIEW_ROOT', APP_ROOT . '/views');
    }

    if (!defined('ROOLITH_CONFIG_ROOT')) {
        define('ROOLITH_CONFIG_ROOT', APP_ROOT . '/config');
    }

    Request::resetForTests();
    RouterFactory::resetForTests();
    DatabaseFactory::resetForTests();
}

protected function tearDown(): void
{
    Request::resetForTests();
    RouterFactory::resetForTests();
    DatabaseFactory::resetForTests();
    Config::reset(false);
}
```

Time uses a frozen clock seam instead of sleeping. `SessionRateLimiter::setNowForTests()` freezes `time()`, `resetForTests()` clears the override. Always clear the override in `tearDown()`.

```php
use App\Core\SessionRateLimiter;

SessionRateLimiter::setNowForTests(1000);
$limiter = new SessionRateLimiter('login:127.0.0.1', 2, 10);
$limiter->hit();
$limiter->hit();

// Window passed - bucket prunes without waiting.
SessionRateLimiter::setNowForTests(1012);
$this->assertFalse($limiter->tooManyAttempts());
```

```php
protected function tearDown(): void
{
    SessionRateLimiter::resetForTests();
    SessionRateLimiter::setNowForTests(null);
}
```

Snapshot and restore `$_ENV`, `$_SERVER`, `getenv()`, `$_SESSION`, and `$_POST` around each test when your test touches them (see `EnvTest`, `CsrfTest`, and `ErrorPagesTest` for the copy-paste pattern). Define `APP_ROOT`, `APP_VIEW_ROOT`, `ROOLITH_CONFIG_ROOT`, and `APP_ENABLE_CMS` once per test class before loading routes or helpers.

## Static analysis

PHPStan runs at level 6 over `app`, `config`, and `roolith` (see `phpstan.neon`). Known legacy findings live in `phpstan-baseline.neon`; do not add new ignores without reason.

```bash
composer analyse
vendor/bin/phpstan analyse --memory-limit=512M
```

```text
# phpstan.neon
parameters:
    level: 6
    paths:
        - app
        - config
        - roolith
```

If analysis reports a new error, fix the code first. Regenerate the baseline only for pre-existing legacy noise, and keep the diff minimal.

## CI

`.github/workflows/ci.yml` has two jobs. The `php` job runs on PHP `8.2`, `8.3`, and `8.4` (`fail-fast: false`); the `frontend` job builds assets.

PHP job, per version:

```bash
composer install --no-progress --prefer-dist
composer check-platform-reqs
composer test
composer lint
composer analyse
composer audit
php -l index.php
php -l constant.php
php -l config/config.php
find app -name "*.php" -exec php -l {} \;
php roolith route:list
```

Frontend job:

```bash
npm ci
npm run build
```

`composer audit` checks known vulnerabilities in dependencies. The `route:list` gate catches bad `Controller@method` strings before they 500 at request time.

## Adding a test

1. Create `tests/YourAreaTest.php` extending `PHPUnit\Framework\TestCase`.
2. Define framework constants and reset facades in `setUp()`; restore everything in `tearDown()`.
3. Freeze time with `setNowForTests()` instead of `sleep()` when testing windows or expiry.
4. Run the single file, then the full suite plus analysis.

```bash
vendor/bin/phpunit tests/YourAreaTest.php
composer test
composer analyse
```

```php
<?php
namespace Tests;

use App\Core\Request;
use PHPUnit\Framework\TestCase;

class YourAreaTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', dirname(__DIR__));
        }

        Request::resetForTests();
    }

    protected function tearDown(): void
    {
        Request::resetForTests();
    }

    public function testSomething(): void
    {
        $this->assertFalse(Request::has('missing-key'));
    }
}
```
