<?php
namespace Tests;

use App\Core\RouteValidator;
use App\Database\MigrationFactory;
use App\Models\Model;
use App\Support\Debug;
use App\Support\Html;
use App\Support\IdGenerator;
use App\Support\Redirect;
use App\Support\Translator;
use App\Support\Url;
use PHPUnit\Framework\TestCase;
use Roolith\Caching\Cache\CacheFactory;
use Roolith\Event\Event;
use Tests\Support\InMemorySeederDatabase;

/**
 * Covers hardening helpers.
 *
 * Asserts crypto IDs, support aliases, route validation, model
 * fillable plus casts plus validation, and cache plus event packages.
 */
class HardeningTest extends TestCase
{
    /**
     * Ensure framework constants exist.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', dirname(__DIR__));
        }

        if (!defined('ROOLITH_CONFIG_ROOT')) {
            define('ROOLITH_CONFIG_ROOT', APP_ROOT . '/config');
        }

        if (!defined('APP_VIEW_ROOT')) {
            define('APP_VIEW_ROOT', APP_ROOT . '/views');
        }

        if (!defined('APP_ENABLE_CMS')) {
            define('APP_ENABLE_CMS', false);
        }

        if (!function_exists('trans')) {
            require_once APP_ROOT . '/app/Utils/functions.php';
        }

        Event::reset();
    }

    /**
     * Reset event state after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Event::reset();
    }

    /**
     * IDs must be crypto random hex with documented shapes.
     *
     * @return void
     */
    public function testIdGeneratorIsCryptoRandom(): void
    {
        $alpha = IdGenerator::alphaNumeric();
        $number = IdGenerator::uniqueNumber();
        $hex = IdGenerator::randomHex(8);

        $this->assertMatchesRegularExpression('/^[0-9A-F]{4}-[0-9a-f]{16}$/', $alpha);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}-[0-9a-f]{8}$/', $number);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $hex);
        $this->assertNotSame($hex, IdGenerator::randomHex(8));

        $this->assertMatchesRegularExpression('/^[0-9A-F]{4}-[0-9a-f]{16}$/', generateUniqueAlphaNumericNumber());
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}-[0-9a-f]{8}$/', generateUniqueNumber());
    }

    /**
     * Support aliases must stay thin and consistent.
     *
     * @return void
     */
    public function testSupportHelpers(): void
    {
        $this->assertSame('&lt;b&gt;', Html::escape('<b>'));
        $this->assertSame('&lt;b&gt;', escape('<b>'));
        $this->assertTrue(Debug::isCli());

        $formatted = Debug::format('hello-hardening');

        $this->assertStringContainsString('hello-hardening', $formatted);

        $redirect = Redirect::to('/dashboard');

        $this->assertSame('/dashboard', $redirect->header('Location'));
        $this->assertSame(303, $redirect->status());
    }

    /**
     * trans() must alias __() for message lookup.
     *
     * @return void
     */
    public function testTransAliasesGetMessage(): void
    {
        $this->assertSame(Translator::trans('errors.required'), trans('errors.required'));
        $this->assertSame(trans('errors.required'), __('errors.required'));
    }

    /**
     * RouteValidator must accept good handlers and reject bad ones.
     *
     * @return void
     */
    public function testRouteValidator(): void
    {
        $this->assertNull(RouteValidator::validateStringHandler('App\\Controllers\\WelcomeController@index'));
        $this->assertNotNull(RouteValidator::validateStringHandler('Missing\\Nope@index'));
        $this->assertNotNull(RouteValidator::validateStringHandler('App\\Controllers\\WelcomeController@missingXyz'));
        $this->assertNotNull(RouteValidator::validateStringHandler('no-at-sign'));

        $this->assertNull(RouteValidator::validateHandler(['App\\Controllers\\WelcomeController', 'index']));
        $this->assertNull(RouteValidator::validateHandler(static function (): string {
            return 'ok';
        }));
        $this->assertNotNull(RouteValidator::validateHandler('no-at-sign'));

        $errors = RouteValidator::validateRouteList([
            ['path' => '/ok', 'execute' => 'App\\Controllers\\WelcomeController@index'],
            ['path' => '/bad', 'execute' => 'Missing\\Nope@index'],
        ]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('/bad', $errors[0]);
    }

    /**
     * Model fillable plus casts plus validation must behave.
     *
     * @return void
     */
    public function testModelFillableCastsValidation(): void
    {
        $model = new class extends Model {
            /**
             * Table for the test double.
             *
             * @var string
             */
            protected string $table = 'users';

            /**
             * Fillable for the test double.
             *
             * @var array<int, string>
             */
            protected array $fillable = ['name', 'email'];

            /**
             * Casts for the test double.
             *
             * @var array<string, string>
             */
            protected array $casts = ['id' => 'int', 'active' => 'bool'];
        };

        $filtered = $model->filterFillable(['name' => 'Hadi', 'email' => 'a@b.c', 'is_admin' => 1]);

        $this->assertSame(['name' => 'Hadi', 'email' => 'a@b.c'], $filtered);

        $casted = $model->castRow(['id' => '42', 'active' => '1', 'name' => 'Hadi']);

        $this->assertSame(42, $casted['id']);
        $this->assertTrue($casted['active']);

        $this->assertSame([], $model->validate(['name' => 'x']));
    }

    /**
     * Cache and event packages must run without a database.
     *
     * Mirrors the read-through and register-once-at-boot shapes in
     * documentation/docs/cache.md and documentation/docs/events.md.
     *
     * @return void
     */
    public function testCacheAndEvents(): void
    {
        if (!defined('ROOLITH_CACHE_DIR')) {
            define('ROOLITH_CACHE_DIR', sys_get_temp_dir() . '/roolith-hardening-cache');
        }

        $key = 'hardening_test_model_query';
        CacheFactory::remove($key);

        $calls = 0;
        $loader = static function () use (&$calls): array {
            $calls++;

            return ['rows' => [1, 2]];
        };

        if (CacheFactory::has($key)) {
            $cached = CacheFactory::get($key);
            $first = $cached !== false ? $cached : $loader();
        } else {
            $first = $loader();
            CacheFactory::put($key, $first, 3600);
        }

        $this->assertSame(['rows' => [1, 2]], $first);

        Event::listen('user.created', static function (array $user): string {
            return 'welcome:' . (string) ($user['email'] ?? '');
        });
        $results = Event::trigger('user.created', [['email' => 'a@b.c']]);

        $this->assertNotSame([], $results);
        $this->assertStringContainsString('a@b.c', (string) $results[0]);
    }

    /**
     * Package migration create must write a timestamped file plus row.
     *
     * Uses an in-memory Database double so no MySQL is needed.
     *
     * @return void
     */
    public function testMigratorCreateAndFiles(): void
    {
        $dir = sys_get_temp_dir() . '/roolith-migrator-' . uniqid('', true);
        mkdir($dir, 0775, true);

        try {
            $db = new InMemorySeederDatabase();
            $migration = MigrationFactory::create($dir, $db);

            $this->assertSame(0, $migration->run(['roolith', 'migration:create', 'create_users_table']));

            $files = glob($dir . '/*.migration.php') ?: [];
            $this->assertCount(1, $files);
            $this->assertStringContainsString('create_users_table', basename($files[0]));

            $rows = $db->table(MigrationFactory::TABLE)->where('file_type', 'migration')->get();
            $this->assertCount(1, $rows);
        } finally {
            $files = glob($dir . '/*') ?: [];

            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            if (is_dir($dir)) {
                rmdir($dir);
            }
        }
    }

    /**
     * Url support must expose route helpers without throwing.
     *
     * @return void
     */
    public function testUrlSupportHelpers(): void
    {
        $active = Url::activeRoute();

        $this->assertIsArray($active);
    }
}
