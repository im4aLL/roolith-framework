<?php
namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * Debug helper: p() prints without ever exiting outside dev.
 */
class DebugHelperTest extends TestCase
{
    /**
     * p() never exits, even with the exit flag outside dev.
     *
     * @return void
     */
    public function testPDoesNotExit(): void
    {
        if (!function_exists('p')) {
            require_once dirname(__DIR__) . '/app/Utils/functions.php';
        }

        ob_start();
        p('hello-m1', true);
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('hello-m1', $output);

        ob_start();
        p(['a' => 1]);
        $plain = (string) ob_get_clean();

        $this->assertStringContainsString('a', $plain);
    }
}
