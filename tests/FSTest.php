<?php
namespace Tests;

use App\Utils\FS;
use PHPUnit\Framework\TestCase;

/**
 * Covers FS root guards, dotfiles, and symlinks on a temp dir.
 */
class FSTest extends TestCase
{
    /**
     * Temp dir for this test.
     *
     * @var string
     */
    private string $tmp = '';

    /**
     * Define APP_ROOT for guard comparisons.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', dirname(__DIR__));
        }

        $this->tmp = sys_get_temp_dir() . '/roolith-fs-' . uniqid();
        mkdir($this->tmp, 0777, true);
    }

    /**
     * Clean up temp dir after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->tmp !== '' && is_dir($this->tmp) && !is_link($this->tmp)) {
            try {
                FS::removeDirectory($this->tmp);
            } catch (\Throwable) {
                $this->removeRecursive($this->tmp);
            }
        }
    }

    /**
     * Fallback recursive delete without guards.
     *
     * @param string $path Directory to delete.
     * @return void
     */
    private function removeRecursive(string $path): void
    {
        clearstatcache(true, $path);

        if (is_link($path) || is_file($path)) {
            unlink($path);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);

        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $full = $path . '/' . $entry;
            clearstatcache(true, $full);

            if (is_link($full)) {
                unlink($full);
            } elseif (is_dir($full)) {
                $this->removeRecursive($full);
            } elseif (is_file($full)) {
                unlink($full);
            }
        }

        clearstatcache(true, $path);
        if (is_dir($path) && !is_link($path)) {
            rmdir($path);
        }
    }

    /**
     * Root guards must refuse empty, /, and APP_ROOT.
     *
     * @return void
     */
    public function testRootGuardsRefuseDangerousPaths(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FS::removeDirectory('');
    }

    /**
     * Filesystem root must be refused.
     *
     * @return void
     */
    public function testFilesystemRootRefused(): void
    {
        try {
            FS::removeDirectory('/');
            $this->fail('Expected InvalidArgumentException for /.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('root', strtolower($e->getMessage()));
        }

        try {
            FS::removeFilesInDirectory('/');
            $this->fail('Expected InvalidArgumentException for / files.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('root', strtolower($e->getMessage()));
        }
    }

    /**
     * APP_ROOT itself must be refused.
     *
     * @return void
     */
    public function testAppRootRefused(): void
    {
        try {
            FS::removeDirectory((string) APP_ROOT);
            $this->fail('Expected InvalidArgumentException for APP_ROOT.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('APP_ROOT', $e->getMessage());
        }
    }

    /**
     * removeDirectory must delete dotfiles and nested dirs on a temp dir.
     *
     * @return void
     */
    public function testRemoveDirectoryHandlesDotfiles(): void
    {
        file_put_contents($this->tmp . '/a.txt', 'a');
        file_put_contents($this->tmp . '/.hidden', 'h');
        mkdir($this->tmp . '/sub');
        file_put_contents($this->tmp . '/sub/b.txt', 'b');
        file_put_contents($this->tmp . '/sub/.hidden2', 'h2');

        $this->assertTrue(FS::removeDirectory($this->tmp));
        $this->assertDirectoryDoesNotExist($this->tmp);

        $this->tmp = '';
    }

    /**
     * removeFilesInDirectory must delete dotfiles but keep subdirs.
     *
     * @return void
     */
    public function testRemoveFilesInDirectoryHandlesDotfilesKeepsSubdirs(): void
    {
        file_put_contents($this->tmp . '/a.txt', 'a');
        file_put_contents($this->tmp . '/.hidden', 'h');
        mkdir($this->tmp . '/sub');
        file_put_contents($this->tmp . '/sub/keep.txt', 'keep');

        $this->assertTrue(FS::removeFilesInDirectory($this->tmp));
        $this->assertFileDoesNotExist($this->tmp . '/a.txt');
        $this->assertFileDoesNotExist($this->tmp . '/.hidden');
        $this->assertFileExists($this->tmp . '/sub/keep.txt');
    }

    /**
     * removeFile must refuse dangerous paths and delete a real temp file.
     *
     * @return void
     */
    public function testRemoveFileGuardsAndDeletes(): void
    {
        try {
            FS::removeFile('');
            $this->fail('Expected InvalidArgumentException for empty path.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('empty', strtolower($e->getMessage()));
        }

        try {
            FS::removeFile('/');
            $this->fail('Expected InvalidArgumentException for /.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('root', strtolower($e->getMessage()));
        }

        try {
            FS::removeFile((string) APP_ROOT);
            $this->fail('Expected InvalidArgumentException for APP_ROOT.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('APP_ROOT', $e->getMessage());
        }

        $file = $this->tmp . '/to-delete.txt';
        file_put_contents($file, 'x');

        $this->assertTrue(FS::removeFile($file));
        $this->assertFileDoesNotExist($file);
        $this->assertFalse(FS::removeFile($file . '.missing'));
    }

    /**
     * Symlinks must be unlinked, not followed.
     *
     * @return void
     */
    public function testSymlinksAreUnlinkedNotFollowed(): void
    {
        $outside = sys_get_temp_dir() . '/roolith-outside-' . uniqid();
        mkdir($outside, 0777, true);
        file_put_contents($outside . '/secret.txt', 'secret');

        $link = $this->tmp . '/link-to-outside';
        symlink($outside, $link);

        $this->assertTrue(FS::removeDirectory($this->tmp));
        $this->assertDirectoryDoesNotExist($this->tmp);
        $this->assertFileExists($outside . '/secret.txt');

        $this->tmp = '';
        $this->removeRecursive($outside);
    }
}
