<?php
namespace App\Support;

use App\Core\PreProcessor;
use App\Core\Response;

/**
 * Redirect response helpers.
 *
 * Backs the global redirect() and redirectToRoute() functions. All
 * helpers are no-exit by design: they return an immutable Response so
 * System::complete() always runs. Callers must return the response
 * from the controller.
 */
final class Redirect
{
    /**
     * Build a redirect response for a URL.
     *
     * Only single-slash relative URLs or absolute URLs allowlisted
     * against the base URL are sent; anything else falls back to /
     * to block open redirects. Defaults to 303 (See Other) for safe
     * Post/Redirect/Get; pass 302 for legacy temporary or 301/308
     * for permanent semantics.
     *
     * @param string $url Redirect target.
     * @param int $statusCode HTTP redirect code, 303 by default.
     * @return Response Redirect response with a Location header.
     */
    public static function to(string $url, int $statusCode = 303): Response
    {
        $target = PreProcessor::resolveSafeRedirectTarget($url);

        return Response::redirect($target, $statusCode);
    }

    /**
     * Build a redirect response for a route name.
     *
     * Resolves the named route URL first, then delegates to to() so
     * the same allowlist applies. Uses 303 by default for the same
     * Post/Redirect/Get reason.
     *
     * @param string $routeName Route name.
     * @param array<string, mixed> $settings Route params.
     * @param int $statusCode HTTP redirect code, 303 by default.
     * @return Response Redirect response with a Location header.
     */
    public static function toRoute(string $routeName, array $settings = [], int $statusCode = 303): Response
    {
        $url = Url::route($routeName, $settings);

        return self::to($url, $statusCode);
    }
}
