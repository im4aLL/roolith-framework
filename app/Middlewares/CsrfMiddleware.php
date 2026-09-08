<?php
namespace App\Middlewares;

use App\Core\Csrf;
use App\Core\Response as AppResponse;
use Roolith\Route\Interfaces\NextMiddlewareInterface;
use Roolith\Route\Request as RouterRequest;
use Roolith\Route\Response as VendorResponse;

/**
 * CSRF middleware for state-changing routes (vendor native next-style).
 *
 * Safe methods (GET, HEAD, OPTIONS) pass through to $next untouched.
 * POST, PUT, PATCH, and DELETE require a per-session token matching
 * Csrf::token() via the _csrf POST field or X-CSRF-TOKEN / X-XSRF-TOKEN
 * header. Failures return a vendor 403 Response instead of calling $next so
 * the controller never runs.
 *
 * Attach directly, no adapter:
 *   $router->post("/form", Ctrl::class . "@submit")
 *       ->middleware(CsrfMiddleware::class);
 */
class CsrfMiddleware implements NextMiddlewareInterface
{
    /**
     * Methods requiring a CSRF token.
     *
     * @var array<int, string>
     */
    private const PROTECTED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Check the token for state-changing methods, else delegate.
     *
     * @param RouterRequest $request Current router request.
     * @param callable $next Next handler receiving the request.
     * @return mixed Next result for safe methods or valid tokens, vendor 403 Response otherwise.
     */
    public function process(RouterRequest $request, callable $next): mixed
    {
        $method = strtoupper((string) $request->getRequestMethod());

        if (!in_array($method, self::PROTECTED_METHODS, true)) {
            return $next($request);
        }

        $candidate = Csrf::tokenFromRequest();

        if (!Csrf::validate($candidate)) {
            $forbidden = new VendorResponse();
            $forbidden->setStatusCode(403);
            $forbidden->renderBody('Invalid CSRF token.');

            return $forbidden;
        }

        $result = $next($request);

        if ($result instanceof VendorResponse) {
            // Static marker header with no PII, used by pipeline tests to
            // prove the guard ran, not a debug leak.
            return $result->setHeader('X-CSRF-Validated', '1');
        }

        if ($result instanceof AppResponse) {
            return $result->withHeader('X-CSRF-Validated', '1');
        }

        return $result;
    }
}
