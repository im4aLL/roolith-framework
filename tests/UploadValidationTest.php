<?php
namespace Tests;

use App\Core\File;
use PHPUnit\Framework\TestCase;

/**
 * Upload validation: MIME plus extension allowlist, double-extension deny,
 * random stored names, traversal-safe custom names, deny-execution
 * htaccess, and validate-before-mkdir.
 */
class UploadValidationTest extends TestCase
{
    /**
     * Uploads must reject shell.php.jpg and accept valid jpg with random name.
     *
     * @return void
     */
    public function testUploadRejectsShellAndAcceptsValidJpg(): void
    {
        $dest = sys_get_temp_dir() . '/roolith-uploads-' . uniqid('', true);
        mkdir($dest, 0775, true);

        try {
            $shellTmp = tempnam(sys_get_temp_dir(), 'shell');
            $this->assertIsString($shellTmp);
            file_put_contents((string) $shellTmp, "<?php echo 'pwned'; ?>");

            $shell = new File();
            $shell->setFile([
                'name' => 'shell.php.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => $shellTmp,
                'error' => 0,
                'size' => filesize((string) $shellTmp),
            ]);

            $this->assertFalse($shell->isValid());
            $this->assertFalse($shell->upload($dest));

            $textTmp = tempnam(sys_get_temp_dir(), 'textjpg');
            $this->assertIsString($textTmp);
            file_put_contents((string) $textTmp, 'plain text, not a jpeg');

            $mismatch = new File();
            $mismatch->setFile([
                'name' => 'photo.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => $textTmp,
                'error' => 0,
                'size' => filesize((string) $textTmp),
            ]);

            $this->assertFalse($mismatch->isValid());

            $jpegBytes = $this->validJpegBytes();

            $validTmp = tempnam(sys_get_temp_dir(), 'validjpg');
            $this->assertIsString($validTmp);
            file_put_contents((string) $validTmp, $jpegBytes);

            $valid = new File();
            $valid->setFile([
                'name' => 'photo.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => $validTmp,
                'error' => 0,
                'size' => filesize((string) $validTmp),
            ]);

            $this->assertTrue($valid->isValid());

            // Fresh tmp for the move because validation does not move files.
            $moveTmp = tempnam(sys_get_temp_dir(), 'movejpg');
            $this->assertIsString($moveTmp);
            file_put_contents((string) $moveTmp, $jpegBytes);

            $mover = new File();
            $mover->setFile([
                'name' => 'photo.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => $moveTmp,
                'error' => 0,
                'size' => filesize((string) $moveTmp),
            ]);

            $stored = $mover->upload($dest);

            $this->assertIsString($stored);
            $this->assertMatchesRegularExpression('/\A[0-9a-f]{16}\.jpg\z/', (string) $stored);
            $this->assertFileExists($dest . '/' . $stored);
            $this->assertFileExists($dest . '/.htaccess');

            $htaccess = (string) file_get_contents($dest . '/.htaccess');

            $this->assertStringContainsString('Require all denied', $htaccess);

            foreach ([$shellTmp, $textTmp, $validTmp] as $leftover) {
                if (is_string($leftover) && is_file($leftover)) {
                    unlink($leftover);
                }
            }
        } finally {
            $this->removeDir($dest);
        }
    }

    /**
     * Traversal custom name falls back to a random name inside dest.
     *
     * @return void
     */
    public function testUploadSanitizesTraversalCustomName(): void
    {
        $dest = sys_get_temp_dir() . '/roolith-traversal-' . uniqid('', true);
        $jpeg = $this->validJpegBytes();

        $tmp = tempnam(sys_get_temp_dir(), 'trav');
        $this->assertIsString($tmp);
        file_put_contents((string) $tmp, $jpeg);

        try {
            $file = new File();
            $file->setFile([
                'name' => 'photo.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => $tmp,
                'error' => 0,
                'size' => filesize((string) $tmp),
            ]);

            $stored = $file->upload($dest, '../../evil.jpg');

            $this->assertIsString($stored);
            $this->assertMatchesRegularExpression('/\A[0-9a-f]{16}\.jpg\z/', (string) $stored);
            $this->assertFileExists($dest . '/' . $stored);
            $this->assertStringNotContainsString('..', (string) $stored);
            $this->assertStringNotContainsString('/', (string) $stored);
        } finally {
            $this->removeDir($dest);

            if (is_file((string) $tmp)) {
                unlink((string) $tmp);
            }
        }
    }

    /**
     * Absolute custom name falls back to a random name inside dest.
     *
     * @return void
     */
    public function testUploadSanitizesAbsoluteCustomName(): void
    {
        $dest = sys_get_temp_dir() . '/roolith-abs-' . uniqid('', true);
        $jpeg = $this->validJpegBytes();

        $tmp = tempnam(sys_get_temp_dir(), 'abs');
        $this->assertIsString($tmp);
        file_put_contents((string) $tmp, $jpeg);

        try {
            $file = new File();
            $file->setFile([
                'name' => 'photo.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => $tmp,
                'error' => 0,
                'size' => filesize((string) $tmp),
            ]);

            $stored = $file->upload($dest, '/tmp/evil.jpg');

            $this->assertIsString($stored);
            $this->assertMatchesRegularExpression('/\A[0-9a-f]{16}\.jpg\z/', (string) $stored);
            $this->assertFileExists($dest . '/' . $stored);
        } finally {
            $this->removeDir($dest);

            if (is_file((string) $tmp)) {
                unlink((string) $tmp);
            }
        }
    }

    /**
     * shell.php custom name never creates a php file.
     *
     * @return void
     */
    public function testUploadSanitizesShellPhpCustomName(): void
    {
        $dest = sys_get_temp_dir() . '/roolith-shell-' . uniqid('', true);
        $jpeg = $this->validJpegBytes();

        $tmp = tempnam(sys_get_temp_dir(), 'shell');
        $this->assertIsString($tmp);
        file_put_contents((string) $tmp, $jpeg);

        try {
            $file = new File();
            $file->setFile([
                'name' => 'photo.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => $tmp,
                'error' => 0,
                'size' => filesize((string) $tmp),
            ]);

            $stored = $file->upload($dest, 'shell.php');

            $this->assertIsString($stored);
            $this->assertMatchesRegularExpression('/\A[0-9a-f]{16}\.jpg\z/', (string) $stored);
            $this->assertFileDoesNotExist($dest . '/shell.php');
            $this->assertFileExists($dest . '/' . $stored);
        } finally {
            $this->removeDir($dest);

            if (is_file((string) $tmp)) {
                unlink((string) $tmp);
            }
        }
    }

    /**
     * Invalid uploads never create directories.
     *
     * @return void
     */
    public function testInvalidUploadDoesNotCreateDirectory(): void
    {
        $dest = sys_get_temp_dir() . '/roolith-nodir-' . uniqid('', true);

        $this->assertDirectoryDoesNotExist($dest);

        $tmp = tempnam(sys_get_temp_dir(), 'bad');
        $this->assertIsString($tmp);
        file_put_contents((string) $tmp, "<?php echo 'pwned'; ?>");

        try {
            $file = new File();
            $file->setFile([
                'name' => 'shell.php.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => $tmp,
                'error' => 0,
                'size' => filesize((string) $tmp),
            ]);

            $this->assertFalse($file->upload($dest));
            $this->assertDirectoryDoesNotExist($dest);
        } finally {
            $this->removeDir($dest);

            if (is_file((string) $tmp)) {
                unlink((string) $tmp);
            }
        }
    }

    /**
     * Get minimal valid jpeg bytes for upload tests.
     *
     * @return string JPEG binary.
     */
    private function validJpegBytes(): string
    {
        $bytes = base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////wgARCAABAAEDASIAAhEBAxEB/8QAFAABAAAAAAAAAAAAAAAAAAAAAP/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AK//Z'
        );

        $this->assertIsString($bytes);

        return (string) $bytes;
    }

    /**
     * Remove a directory with files plus dotfiles.
     *
     * @param string $dir Directory to remove.
     * @return void
     */
    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = glob($dir . '/*') ?: [];
        $dotfiles = glob($dir . '/.*') ?: [];

        foreach (array_merge($files, $dotfiles) as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        rmdir($dir);
    }
}
