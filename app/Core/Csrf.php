<?php
namespace App\Core;

/**
 * Per-session CSRF token helper.
 *
 * Tokens are 64 hex chars from random_bytes(32), stored in the session under
 * a dedicated key so clearing the token does not disturb other session data.
 * Validation uses hash_equals to avoid timing leaks. All entry points start
 * the session via Session::start() so cookie flags stay consistent.
 */
final class Csrf
{
    /**
     * Session key holding the CSRF token.
     *
     * @var string
     */
    public const TOKEN_KEY = '_csrf_token';

    /**
     * Request field and header names carrying the token.
     *
     * @var string
     */
    public const FIELD = '_csrf';

    /**
     * Header name carrying the token for fetch/XHR clients.
     *
     * @var string
     */
    public const HEADER = 'X-CSRF-TOKEN';

    /**
     * Get the per-session CSRF token, generating it once per session.
     *
     * Primary randomness is random_bytes(32). The fallback path is hardened
     * (M3): random_bytes can still throw when the CSPRNG is unavailable, so
     * the retry is guarded and degrades to random_int hex, then to a
     * uniqid-based hex that never throws, keeping token generation total.
     *
     * @return string 64-char hex token.
     */
    public static function token(): string
    {
        Session::start();

        $existing = $_SESSION[self::TOKEN_KEY] ?? null;

        if (is_string($existing) && preg_match('/\A[0-9a-f]{64}\z/', $existing) === 1) {
            return $existing;
        }

        try {
            $token = bin2hex(random_bytes(32));
        } catch (\Throwable) {
            try {
                $fallback = '';

                for ($i = 0; $i < 8; $i++) {
                    $fallback .= sprintf('%08x', random_int(0, 0xffffffff));
                }

                $token = $fallback;
            } catch (\Throwable) {
                // Last resort when even random_int fails: uniqid-based hex
                // (unique per call, not cryptographic) so token creation
                // never throws and the session still gets a 64-char token.
                $token = hash('sha256', uniqid('', true) . mt_rand() . microtime(true));
            }
        }

        $_SESSION[self::TOKEN_KEY] = $token;

        return $token;
    }

    /**
     * Validate a candidate token against the session token.
     *
     * @param string|null $token Candidate token from the request.
     * @return bool True when the candidate matches the session token.
     */
    public static function validate(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        Session::start();

        $expected = $_SESSION[self::TOKEN_KEY] ?? null;

        if (!is_string($expected) || $expected === '') {
            return false;
        }

        try {
            return hash_equals($expected, $token);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Render the hidden form field carrying the token.
     *
     * Values are hex-only, so no escaping is needed, but the field is built
     * with htmlspecialchars for defense in depth.
     *
     * @return string Hidden input HTML.
     */
    public static function field(): string
    {
        $token = self::token();
        $name = htmlspecialchars(self::FIELD, ENT_QUOTES, 'UTF-8');
        $value = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="' . $name . '" value="' . $value . '">';
    }

    /**
     * Extract the candidate token from the current request.
     *
     * Checks POST field, then X-CSRF-TOKEN and X-XSRF-TOKEN headers so HTML
     * forms and fetch clients share one check.
     *
     * @return string|null Candidate token or null when absent.
     */
    public static function tokenFromRequest(): ?string
    {
        if (isset($_POST[self::FIELD]) && is_string($_POST[self::FIELD])) {
            return $_POST[self::FIELD];
        }

        $headers = ['HTTP_X_CSRF_TOKEN', 'HTTP_X_XSRF_TOKEN'];

        foreach ($headers as $key) {
            if (isset($_SERVER[$key]) && is_string($_SERVER[$key]) && $_SERVER[$key] !== '') {
                return $_SERVER[$key];
            }
        }

        return null;
    }

    /**
     * Rotate the token (call on privilege change such as login).
     *
     * @return string Fresh token.
     */
    public static function rotate(): string
    {
        Session::start();
        unset($_SESSION[self::TOKEN_KEY]);

        return self::token();
    }

    /**
     * Clear token state for tests.
     *
     * @return void
     */
    public static function resetForTests(): void
    {
        unset($_SESSION[self::TOKEN_KEY]);
    }
}
