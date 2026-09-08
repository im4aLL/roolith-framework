<?php
namespace Tests;

use App\Core\Request;
use App\Support\Html;
use PHPUnit\Framework\TestCase;

/**
 * Covers output-at-render escaping (014-A14).
 *
 * Request::input() stays raw so stored data keeps its exact form;
 * views escape with escape() or Html::escape(). Asserts O'Reilly,
 * unicode, and HTML payload handling plus the narrow Sanitize cases.
 */
class OutputEscapingTest extends TestCase
{
    /**
     * Back up POST around each test.
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
     * Snapshot superglobals and clear Request cache before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->postBackup = $_POST;
        $this->getBackup = $_GET;
        $_POST = [];
        $_GET = [];
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
        Request::resetForTests();
    }

    /**
     * O'Reilly style apostrophes must survive input raw.
     *
     * @return void
     */
    public function testOReillySurvivesRaw(): void
    {
        $_POST = ['author' => "O'Reilly"];

        $this->assertSame("O'Reilly", Request::input('author'));
        $this->assertSame('O&#039;Reilly', Html::escape(Request::input('author')));
    }

    /**
     * Unicode must survive input raw and escape safely.
     *
     * @return void
     */
    public function testUnicodeSurvivesRaw(): void
    {
        $_POST = ['greeting' => 'こんにちは世界 🌍'];

        $this->assertSame('こんにちは世界 🌍', Request::input('greeting'));
        $this->assertSame('こんにちは世界 🌍', Html::escape(Request::input('greeting')));
    }

    /**
     * HTML payloads must stay raw until view escaping blocks XSS.
     *
     * @return void
     */
    public function testHtmlPayloadEscapedAtRender(): void
    {
        $_POST = ['comment' => '<script>alert(1)</script>hello'];

        $raw = Request::input('comment');

        $this->assertSame('<script>alert(1)</script>hello', $raw);
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;hello', Html::escape($raw));
    }

    /**
     * Narrow Sanitize cases still constrain slugs and emails.
     *
     * @return void
     */
    public function testNarrowSanitizeCases(): void
    {
        $this->assertSame('hello-world_1.txt', \App\Core\Sanitize::param('hello-world_1.txt'));
        $this->assertSame('script', \App\Core\Sanitize::param('<script>'));
        $this->assertStringContainsString('@', \App\Core\Sanitize::email('user@example.com'));
    }
}
