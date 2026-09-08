<?php
namespace Tests;

use App\Core\Request;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Covers Request stream caching, JSON bodies, and falsy has() handling.
 */
class RequestTest extends TestCase
{
    /**
     * Back up superglobals around each test.
     *
     * @var array<string, mixed>
     */
    private array $postBackup = [];

    /**
     * Back up GET around each test.
     *
     * @var array<string, mixed>
     */
    private array $getBackup = [];

    /**
     * Back up server vars around each test.
     *
     * @var array<string, mixed>
     */
    private array $serverBackup = [];

    /**
     * Snapshot superglobals and clear Request cache before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->postBackup = $_POST;
        $this->getBackup = $_GET;
        $this->serverBackup = $_SERVER;
        $_POST = [];
        $_GET = [];
        unset($_SERVER['CONTENT_TYPE'], $_SERVER['HTTP_CONTENT_TYPE']);
        Request::resetForTests();
    }

    /**
     * Restore superglobals and clear Request cache after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_POST = $this->postBackup;
        $_GET = $this->getBackup;
        $_SERVER = $this->serverBackup;
        Request::resetForTests();
    }

    /**
     * has() must treat falsy POST values as present via array_key_exists.
     *
     * @return void
     */
    public function testHasTreatsFalsyPostValuesAsPresent(): void
    {
        $_POST = ['a' => '0', 'b' => 0, 'c' => false, 'd' => null, 'e' => []];

        $this->assertTrue(Request::has('a'));
        $this->assertTrue(Request::has('b'));
        $this->assertTrue(Request::has('c'));
        $this->assertTrue(Request::has('d'));
        $this->assertTrue(Request::has('e'));
        $this->assertFalse(Request::has('missing'));
    }

    /**
     * has() must treat falsy GET values as present.
     *
     * @return void
     */
    public function testHasTreatsFalsyGetValuesAsPresent(): void
    {
        $_GET = ['a' => '0'];

        $this->assertTrue(Request::has('a'));
        $this->assertFalse(Request::has('missing'));
    }

    /**
     * JSON body {"a":0} must parse with has() true and input 0.
     *
     * @return void
     */
    public function testJsonBodyWithZeroParses(): void
    {
        Request::setRawInputForTests('{"a":0}', 'application/json');

        $this->assertTrue(Request::has('a'));
        $this->assertSame(0, Request::input('a'));
        $this->assertSame(0, Request::unsafeInput('a'));
    }

    /**
     * JSON body with string zero must stay present.
     *
     * @return void
     */
    public function testJsonBodyWithStringZeroParses(): void
    {
        Request::setRawInputForTests('{"a":"0"}', 'application/json; charset=utf-8');

        $this->assertTrue(Request::has('a'));
        $this->assertSame('0', Request::input('a'));
    }

    /**
     * Urlencoded stream bodies must still parse.
     *
     * @return void
     */
    public function testUrlencodedStreamBodyParses(): void
    {
        Request::setRawInputForTests('a=0&b=hello', 'application/x-www-form-urlencoded');

        $this->assertTrue(Request::has('a'));
        $this->assertSame('0', Request::input('a'));
        $this->assertSame('hello', Request::input('b'));
    }

    /**
     * Stream inputs must be cached per request (php://input read once).
     *
     * After the first parse, mutating the raw override behind the cache
     * must not change reads until resetForTests() clears the cache.
     *
     * @return void
     */
    public function testStreamInputsAreCachedPerRequest(): void
    {
        Request::setRawInputForTests('{"a":1}', 'application/json');

        $this->assertSame(1, Request::input('a'));

        $ref = new ReflectionClass(Request::class);
        $rawProp = $ref->getProperty('rawInputOverride');
        $rawProp->setAccessible(true);
        $rawProp->setValue(null, '{"a":2}');

        $this->assertSame(1, Request::input('a'), 'Second read must use the cached parse, not re-read the body.');

        Request::resetForTests();
        Request::setRawInputForTests('{"a":2}', 'application/json');

        $this->assertSame(2, Request::input('a'));
    }

    /**
     * Invalid JSON must not fatal and must read as missing.
     *
     * @return void
     */
    public function testInvalidJsonReadsAsMissing(): void
    {
        Request::setRawInputForTests('{invalid', 'application/json');

        $this->assertFalse(Request::has('a'));
        $this->assertSame('fallback', Request::input('a', 'fallback'));
    }

    /**
     * JSON string values via input() stay raw for render-time escaping.
     *
     * Output-at-render (014-A14): a JSON body carrying `<script>` returns
     * raw markup from input() so stored data keeps its exact form;
     * views escape with escape(). Int scalars keep their type.
     * unsafeInput() is a BC alias of input().
     *
     * @return void
     */
    public function testJsonStringInputIsSanitized(): void
    {
        Request::setRawInputForTests('{"comment":"<script>alert(1)</script>hello"}', 'application/json');

        $raw = Request::input('comment');

        $this->assertIsString($raw);
        $this->assertSame('<script>alert(1)</script>hello', $raw);
        $this->assertSame('<script>alert(1)</script>hello', Request::unsafeInput('comment'));
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;hello', \App\Support\Html::escape($raw));
    }

    /**
     * all() returns raw by default, sanitized only on opt-in.
     *
     * @return void
     */
    public function testAllSanitizesStreamUnlessSkipped(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        Request::setRawInputForTests('{"comment":"<script>alert(1)</script>hello"}', 'application/json');

        try {
            $raw = Request::all();
            $legacy = Request::all(['sanitize' => true]);
            $skipped = Request::all(['skipSanitization' => true]);
        } finally {
            unset($_SERVER['REQUEST_METHOD']);
        }

        $this->assertIsArray($raw);
        $this->assertSame('<script>alert(1)</script>hello', $raw['comment'] ?? null);
        $this->assertSame('<script>alert(1)</script>hello', $skipped['comment'] ?? null);
        $this->assertStringNotContainsString('<script>', (string) ($legacy['comment'] ?? ''));
        $this->assertSame('<script>alert(1)</script>hello', Request::unsafeInput('comment'));
    }

    /**
     * Stream int scalars must keep their type through input().
     *
     * @return void
     */
    public function testJsonIntInputPreservesType(): void
    {
        Request::setRawInputForTests('{"count":0}', 'application/json');

        $this->assertSame(0, Request::input('count'));
    }
}
