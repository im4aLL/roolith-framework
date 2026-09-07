<?php
namespace App\Core;

/**
 * HTTP host pre-processing redirects (www handling).
 *
 * Guards skip redirects when no HTTP host is present (CLI, tests) so
 * bootstrap stays runnable outside a web request.
 */
class PreProcessor
{
    /**
     * Redirect www hosts to the bare domain.
     *
     * No-op when HTTP_HOST is unset or already bare.
     *
     * @return void
     */
    public static function forceNonWww(): void
    {
        if (!isset($_SERVER['HTTP_HOST'])) return;
        $host = str_replace(["\r", "\n"], '', $_SERVER['HTTP_HOST']);
        if (substr($host, 0, 4) === 'www.') {
            $uri = str_replace(["\r", "\n"], '', $_SERVER['REQUEST_URI'] ?? '/');
            header('Location: http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 's':'').'://' . substr($host, 4).$uri);
            exit;
        }
    }

    /**
     * Redirect bare hosts to the www domain.
     *
     * No-op when HTTP_HOST is unset or already has www.
     *
     * @return void
     */
    public static function forceWww(): void
    {
        if (!isset($_SERVER['HTTP_HOST'])) return;
        $host = str_replace(["\r", "\n"], '', $_SERVER['HTTP_HOST']);
        if ((strpos($host, 'www.') === false)) {
            $uri = str_replace(["\r", "\n"], '', $_SERVER["REQUEST_URI"] ?? '/');
            header('Location: http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 's':'').'://www.'.$host . $uri);
            exit();
        }
    }
}
