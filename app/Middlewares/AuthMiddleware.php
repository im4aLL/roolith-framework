<?php
namespace App\Middlewares;

use App\Core\PreProcessor;
use App\Core\Response as AppResponse;
use App\Core\Session;
use Roolith\Route\Interfaces\NextMiddlewareInterface;
use Roolith\Route\Request as RouterRequest;
use Roolith\Route\Response as VendorResponse;

/**
 * Example deny-by-default auth middleware (vendor native next-style).
 *
 * Checks the session for an authenticated user id; when present the request
 * flows to $next (controller) with post-processing adding an auth marker
 * header on Response results. When absent the middleware denies by returning
 * a vendor redirect Response to the login path instead of calling $next, so
 * the controller never runs. Copy this pattern for real guards.
 *
 * Attach directly, no adapter:
 *   $router->get("/dashboard", Ctrl::class . "@index")
 *       ->middleware(AuthMiddleware::class);
 */
class AuthMiddleware implements NextMiddlewareInterface
{
    /**
     * Create an auth guard for a session key plus login path.
     *
     * @param string $loginPath Redirect target for unauthenticated requests.
     * @param string $sessionKey Session key holding the authenticated user id.
     */
    public function __construct(
        private string $loginPath = '/login',
        private string $sessionKey = 'user_id'
    ) {}

    /**
     * Allow authenticated requests, redirect guests to login.
     *
     * Auth is an explicit positive check via isAuthenticatedId(): only int
     * greater than zero or a trimmed non-empty non-zero string passes; 0,
     * "0" (plus numeric zero variants), false, "", null, and [] all deny.
     * The login redirect target is allowlisted via
     * PreProcessor::resolveSafeRedirectTarget() so a misconfigured
     * loginPath can never become an open redirect. The guest redirect is a
     * vendor Response (built with renderBody-free redirect storage) so the
     * vendor pipeline emits its status plus Location directly.
     *
     * @param RouterRequest $request Current router request.
     * @param callable $next Next handler receiving the request.
     * @return mixed Next result for authenticated users, vendor redirect Response otherwise.
     */
    public function process(RouterRequest $request, callable $next): mixed
    {
        Session::start();

        $userId = $_SESSION[$this->sessionKey] ?? null;

        if (!$this->isAuthenticatedId($userId)) {
            $target = PreProcessor::resolveSafeRedirectTarget($this->loginPath);
            $redirect = new VendorResponse();
            $redirect->setStatusCode(302);
            $redirect->redirect($target);

            return $redirect;
        }

        $result = $next($request);

        if ($result instanceof VendorResponse) {
            // Static marker header with no PII, used by pipeline tests to
            // prove the guard ran, not a debug leak.
            return $result->setHeader('X-Auth-Checked', '1');
        }

        if ($result instanceof AppResponse) {
            return $result->withHeader('X-Auth-Checked', '1');
        }

        return $result;
    }

    /**
     * Check whether a session value is a real positive user id.
     *
     * Allows int greater than zero and trimmed strings that are non-empty
     * and non-zero; numeric strings must be greater than zero so "00" and
     * "0.0" also deny. All other types (null, bool, array, float, object)
     * deny so falsy values like 0, "0", false, "", null, and [] never
     * authenticate.
     *
     * @param mixed $userId Session value to check.
     * @return bool True only for a real positive id.
     */
    private function isAuthenticatedId(mixed $userId): bool
    {
        if (is_int($userId)) {
            return $userId > 0;
        }

        if (is_string($userId)) {
            $trimmed = trim($userId);

            if ($trimmed === '' || $trimmed === '0') {
                return false;
            }

            if (is_numeric($trimmed) && (float) $trimmed <= 0) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Get the login path (test seam).
     *
     * @return string Login redirect target.
     */
    public function loginPath(): string
    {
        return $this->loginPath;
    }

    /**
     * Get the session key (test seam).
     *
     * @return string Session key holding the user id.
     */
    public function sessionKey(): string
    {
        return $this->sessionKey;
    }
}
