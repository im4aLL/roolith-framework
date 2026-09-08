<?php

use App\Utils\Str;
use Carbon\Carbon;
use App\Core\RouterFactory;
use Roolith\Configuration\Config;
use Roolith\Configuration\Exception\InvalidArgumentException;

/**
 * Print anything for debugging.
 *
 * Dev-only exit (015-B1, M1): $exit terminates via exit() only when
 * isDevEnvironment() is true (explicit APP_ENV=development). In any other
 * environment the flag is ignored and the function returns, so production
 * can never truncate emission or skip System::complete() (disconnect plus
 * temp cleanup) through this helper.
 *
 * @param mixed $any Value to print.
 * @param bool $exit Terminate after printing, dev environments only.
 * @return void
 */
function p(mixed $any, bool $exit = false): void
{
    echo "<pre>";
    print_r($any);
    echo "</pre>";

    if ($exit && isDevEnvironment()) {
        exit(0);
    }
}

/**
 * Prefix the app base URL to a path with slash normalization.
 *
 * Joins as rtrim(base, '/') . '/' . ltrim(path, '/') so both
 * baseUrl with/without trailing slash and paths with/without leading
 * slash produce one slash. When baseUrl is missing or empty, the
 * misconfiguration is logged and in development throws so it fails fast;
 * in production it falls back to a root-relative path.
 *
 * @param string $path Path to prefix (for example assets/css/app.css or /assets/css/app.css).
 * @return string Absolute URL when baseUrl exists, otherwise a root-relative path.
 * @throws InvalidArgumentException When baseUrl is missing and APP_ENV is development.
 */
function url(string $path): string
{
    $fallback = '/' . ltrim($path, '/');

    try {
        $baseUrl = Config::get("baseUrl");
    } catch (InvalidArgumentException $e) {
        error_log('[Roolith url] Missing baseUrl config: ' . $e->getMessage());

        if (isDevEnvironment()) {
            throw new InvalidArgumentException("Missing baseUrl config: " . $e->getMessage(), 0, $e);
        }

        return $fallback;
    } catch (\Throwable $e) {
        error_log('[Roolith url] Cannot read baseUrl config: ' . $e->getMessage());

        if (isDevEnvironment()) {
            throw new InvalidArgumentException("Missing baseUrl config: " . $e->getMessage(), 0, $e);
        }

        return $fallback;
    }

    if (!is_string($baseUrl) || trim($baseUrl) === '') {
        error_log('[Roolith url] Missing baseUrl config: empty value.');

        if (isDevEnvironment()) {
            throw new InvalidArgumentException("Missing baseUrl config: empty value.");
        }

        return $fallback;
    }

    $base = rtrim(trim($baseUrl), '/');
    $suffix = ltrim($path, '/');

    if ($suffix === '') {
        return $base . '/';
    }

    return $base . '/' . $suffix;
}

/**
 * Get vite dev server url from configuration.
 *
 * Empty means vite dev server is not used.
 *
 * @return string Dev server base URL or empty string.
 */
function viteDevServerUrl(): string
{
    try {
        return rtrim((string) Config::get("viteDevServer"), "/");
    } catch (InvalidArgumentException $e) {
        return "";
    }
}

/**
 * Get a built asset url with version query.
 *
 * Version comes from config `version` (explicit APP_VERSION, else time()
 * in dev / 1.0.0 in prod) so prod URLs stay stable across requests for
 * browser/CDN caching. When a Vite prod manifest maps the built path, callers via
 * viteCss/viteJs prefer the hashed file instead (no query needed).
 *
 * @param string $path Built path e.g. assets/css/app.css.
 * @return string Absolute asset URL with ?v=version.
 */
function viteBuiltAssetUrl(string $path): string
{
    return url($path . "?v=" . getVersion());
}

/**
 * Read the Vite prod manifest when present.
 *
 * Looks for assets/.vite/manifest.json (Vite 5 default when manifest:true)
 * then assets/manifest.json, decoding to an array. Missing or invalid files
 * yield []. Results are cached per request in $GLOBALS so the test seam
 * fully clears; tests can override via setViteManifestForTests().
 *
 * @return array<string, array<string, mixed>> Manifest source to entry map.
 */
function viteManifest(): array
{
    $override = $GLOBALS['_VITE_MANIFEST_OVERRIDE'] ?? null;

    if (is_array($override)) {
        return $override;
    }

    $cached = $GLOBALS['_VITE_MANIFEST_CACHE'] ?? null;

    if (is_array($cached)) {
        return $cached;
    }

    $base = defined('APP_ROOT') ? (string) APP_ROOT : dirname(__DIR__, 2);
    $candidates = [
        rtrim($base, "/\\") . '/assets/.vite/manifest.json',
        rtrim($base, "/\\") . '/assets/manifest.json',
    ];

    foreach ($candidates as $file) {
        try {
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }

            $raw = file_get_contents($file);

            if (!is_string($raw) || trim($raw) === '') {
                continue;
            }

            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                $GLOBALS['_VITE_MANIFEST_CACHE'] = $decoded;

                return $decoded;
            }
        } catch (\Throwable) {
            continue;
        }
    }

    $GLOBALS['_VITE_MANIFEST_CACHE'] = [];

    return [];
}

/**
 * Arm a Vite manifest override for tests (test seam).
 *
 * Pass an array to fake manifest entries, null to clear the override and
 * the cached file read so the next viteManifest() re-reads from disk.
 * Both the $GLOBALS override and the $GLOBALS file cache are cleared so
 * no stale read survives across tests.
 *
 * @param array<string, array<string, mixed>>|null $manifest Manifest map or null to clear.
 * @return void
 */
function setViteManifestForTests(?array $manifest): void
{
    if ($manifest === null) {
        unset($GLOBALS['_VITE_MANIFEST_OVERRIDE'], $GLOBALS['_VITE_MANIFEST_CACHE']);
    } else {
        $GLOBALS['_VITE_MANIFEST_OVERRIDE'] = $manifest;
    }
}

/**
 * Resolve a hashed built file from the manifest when available.
 *
 * Looks up the source entry (for example source/js/app.js) and returns
 * assets/<file> when the manifest has it; otherwise returns the stable
 * built path fallback.
 *
 * @param string $sourcePath Vite source entry (manifest key).
 * @param string $builtPath Stable built path fallback e.g. assets/js/app.js.
 * @return string Hashed or fallback built path.
 */
function viteManifestFile(string $sourcePath, string $builtPath): string
{
    $manifest = viteManifest();
    $entry = $manifest[$sourcePath] ?? null;

    if (is_array($entry) && isset($entry['file']) && is_string($entry['file']) && trim($entry['file']) !== '') {
        return 'assets/' . ltrim(trim($entry['file']), '/');
    }

    return $builtPath;
}

/**
 * Render the vite client tag once per page.
 *
 * Render-once state lives in $GLOBALS so tests can reset it via
 * resetViteClientTagForTests() without process isolation.
 *
 * @param string $devServer Vite dev server base URL.
 * @return string Script tag HTML on first call, empty string afterwards.
 */
function viteClientTag(string $devServer): string
{
    if (!empty($GLOBALS['_VITE_CLIENT_RENDERED'])) {
        return "";
    }

    $GLOBALS['_VITE_CLIENT_RENDERED'] = true;

    return "<script type=\"module\" src=\"{$devServer}/@vite/client\"></script>";
}

/**
 * Reset the vite client once-per-page flag (test seam).
 *
 * Clears the $GLOBALS render flag so the next viteClientTag() call emits
 * again. Safe to call when no flag is armed.
 *
 * @return void
 */
function resetViteClientTagForTests(): void
{
    unset($GLOBALS['_VITE_CLIENT_RENDERED']);
}

/**
 * Render a stylesheet tag for a vite entry.
 *
 * In dev with a Vite server, points at the server source. In prod, prefers
 * the hashed file from the Vite manifest (content-hashed filename, no query
 * needed) and falls back to the stable built path plus ?v=version.
 *
 * @param string $sourcePath Vite source entry e.g. source/scss/app.scss (manifest key).
 * @param string $builtPath Stable built fallback e.g. assets/css/app.css.
 * @return string Link tag HTML.
 */
function viteCss(string $sourcePath, string $builtPath): string
{
    $devServer = viteDevServerUrl();

    if (isDevEnvironment() && $devServer !== "") {
        return viteClientTag($devServer) .
            "<link rel=\"stylesheet\" href=\"{$devServer}/{$sourcePath}\">";
    }

    $resolved = viteManifestFile($sourcePath, $builtPath);

    if ($resolved !== $builtPath) {
        return "<link rel=\"stylesheet\" href=\"" . url($resolved) . "\">";
    }

    return "<link rel=\"stylesheet\" href=\"" . viteBuiltAssetUrl($builtPath) . "\">";
}

/**
 * Render a script tag for a vite entry.
 *
 * In dev with a Vite server, points at the server source. In prod, prefers
 * the hashed file from the Vite manifest and falls back to the stable built
 * path plus ?v=version.
 *
 * @param string $sourcePath Vite source entry e.g. source/js/app.js (manifest key).
 * @param string $builtPath Stable built fallback e.g. assets/js/app.js.
 * @return string Script tag HTML.
 */
function viteJs(string $sourcePath, string $builtPath): string
{
    $devServer = viteDevServerUrl();

    if (isDevEnvironment() && $devServer !== "") {
        return "<script type=\"module\" src=\"{$devServer}/{$sourcePath}\"></script>";
    }

    $resolved = viteManifestFile($sourcePath, $builtPath);

    if ($resolved !== $builtPath) {
        return "<script src=\"" . url($resolved) . "\"></script>";
    }

    return "<script src=\"" . viteBuiltAssetUrl($builtPath) . "\"></script>";
}

/**
 * Get url by router name.
 *
 * @param string $name Route name.
 * @param array<string, mixed> $settings Route params keyed by placeholder.
 * @return string Absolute URL for the named route.
 */
function route(string $name, array $settings = []): string
{
    $routerInstance = RouterFactory::getInstance();

    return $routerInstance->getUrlByName($name, $settings);
}

/**
 * Get active route.
 *
 * Returns the matched route with payload, or an empty array when
 * nothing matches (the router returns null on no-match).
 *
 * @return array<string, mixed> Active route data or empty array.
 */
function getActiveRoute(): array
{
    $routerInstance = RouterFactory::getInstance();

    try {
        return $routerInstance->activeRoute() ?? [];
    } catch (\Throwable) {
        return [];
    }
}

/**
 * Get a message.
 *
 * @param string $name Message key.
 * @return mixed Message value or null when missing.
 */
function __(string $name): mixed
{
    return Str::getMessage($name);
}

/**
 * Build a redirect response for a URL.
 *
 * No-exit by design: returns an immutable App\Core\Response instead of
 * sending headers plus die() so System::complete() (disconnect plus temp
 * cleanup) always runs. Callers must `return redirect(...)` from the
 * controller (emitted via RouterResponse) or throw RedirectException.
 * BC break: previously void plus die().
 *
 * Status is deliberate: 303 (See Other) is the default because it implements
 * Post/Redirect/Get safely by always following up with GET. Pass 302 for a
 * temporary redirect where legacy behavior is needed, or 301/308 for a
 * permanent redirect (308 preserves the method, 301 may not).
 *
 * Only single-slash relative URLs or absolute URLs allowlisted against the
 * base URL are sent; anything else (for example ?next=https://evil.com,
 * //evil.com, javascript:) falls back to / to block open redirects.
 *
 * L3 accepted: this 303 default differs from Request::redirect() 302 by
 * design (global helper favors PRG, Request::redirect keeps legacy BC).
 *
 * @param string $url Redirect target.
 * @param int $statusCode HTTP redirect code, 303 by default.
 * @return \App\Core\Response Redirect response with a Location header.
 */
function redirect(string $url, int $statusCode = 303): \App\Core\Response
{
    $target = \App\Core\PreProcessor::resolveSafeRedirectTarget($url);

    return \App\Core\Response::redirect($target, $statusCode);
}

/**
 * Build a redirect response for a route name.
 *
 * No-exit: returns a Response instead of dying. Uses 303 by default for the
 * same Post/Redirect/Get reason as redirect(). Pass an explicit code when a
 * different redirect semantic is intended. Callers must return the response.
 *
 * @param string $routeName Route name.
 * @param array<string, mixed> $settings Route params.
 * @param int $statusCode HTTP redirect code, 303 by default.
 * @return \App\Core\Response Redirect response with a Location header.
 */
function redirectToRoute(string $routeName, array $settings = [], int $statusCode = 303): \App\Core\Response
{
    $url = route($routeName, $settings);

    return redirect($url, $statusCode);
}

/**
 * Build a JSON envelope response with correct headers and status.
 *
 * Wraps the payload in the standard {status, payload, message} envelope via
 * ApiResponseTransformer, sets Content-Type application/json, and stores the
 * HTTP code (emitted via http_response_code by Response::send or
 * RouterResponse). Controllers standardize on `return json(...)` or
 * `return "...html..."` (Response|string).
 *
 * @param mixed $payload Envelope payload data.
 * @param string $status Envelope status (success or error).
 * @param int $code HTTP status code.
 * @param string $message Human-readable message.
 * @return \App\Core\Response JSON response with application/json header.
 */
function json(mixed $payload, string $status = "success", int $code = 200, string $message = ""): \App\Core\Response
{
    return \App\Core\ApiResponseTransformer::json($payload, $status, $code, $message);
}

/**
 * Get the per-session CSRF token.
 *
 * Generates once per session via random_bytes and reuses it until rotation
 * (for example on login via Csrf::rotate()).
 *
 * @return string 64-char hex CSRF token.
 */
function csrf_token(): string
{
    return \App\Core\Csrf::token();
}

/**
 * Render the hidden CSRF form field.
 *
 * Emits `<input type="hidden" name="_csrf" value="...">` for POST forms.
 * Verified by the CSRF middleware for POST, PUT, PATCH, and DELETE.
 *
 * @return string Hidden input HTML.
 */
function csrf_field(): string
{
    return \App\Core\Csrf::field();
}

/**
 * Generate unique alpha numeric number.
 *
 * @return string Unique identifier with alpha prefix and timestamp.
 */
function generateUniqueAlphaNumericNumber(): string
{
    $prefix = substr(str_shuffle(str_repeat("ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4)), 0, 4);
    $postfix = time();

    return "{$prefix}-{$postfix}";
}

/**
 * Generate unique number.
 *
 * @return string Unique identifier with random and timestamp parts.
 */
function generateUniqueNumber(): string
{
    return substr(str_shuffle(str_repeat("ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4)), 0, 2) .
        "-" .
        mt_rand(100000, 999999) .
        "-" .
        time();
}

/**
 * Get current date and time.
 *
 * @return string Current datetime string (Y-m-d H:i:s).
 */
function getCurrentDateTime(): string
{
    return Carbon::now()->toDateTimeString();
}

/**
 * Get today's date.
 *
 * @return string Current date string (Y-m-d).
 */
function getCurrentDate(): string
{
    return Carbon::now()->toDateString();
}

/**
 * Is dev environment.
 *
 * Single source is APP_ENV via App\Core\Env. Fail-closed: only an explicit
 * APP_ENV=development returns true.
 *
 * @return bool True in development, false otherwise.
 */
function isDevEnvironment(): bool
{
    return \App\Core\Env::isDevelopment();
}

/**
 * Is production environment.
 *
 * Fail-closed: anything that is not development counts as production-safe.
 *
 * @return bool True outside development, false in development.
 */
function isProductionEnvironment(): bool
{
    return \App\Core\Env::isProduction();
}

/**
 * List proxy IPs allowed to set forwarded-IP headers.
 *
 * Single source is config trustedProxies (mapped from TRUSTED_PROXIES).
 * Empty means proxy headers are never trusted. Only exact IP matches are
 * honored (CIDR ranges are not supported and are dropped); invalid IPs are
 * filtered out. Never throws so IP resolution stays available before
 * config boots.
 *
 * @return array<int, string> Trusted proxy IP strings (valid IPs only).
 */
function trustedProxies(): array
{
    try {
        $configured = Config::get('trustedProxies');
    } catch (\Throwable) {
        $configured = null;
    }

    if (is_array($configured)) {
        $proxies = [];

        foreach ($configured as $proxy) {
            if (is_string($proxy)) {
                $proxy = trim($proxy);

                if ($proxy !== '' && filter_var($proxy, FILTER_VALIDATE_IP) !== false) {
                    $proxies[] = $proxy;
                }
            }
        }

        return $proxies;
    }

    $raw = \App\Core\Env::get('TRUSTED_PROXIES', '');

    if ($raw === null || trim($raw) === '') {
        return [];
    }

    $proxies = [];

    foreach (explode(',', $raw) as $proxy) {
        $proxy = trim($proxy);

        if ($proxy !== '' && filter_var($proxy, FILTER_VALIDATE_IP) !== false) {
            $proxies[] = $proxy;
        }
    }

    return $proxies;
}

/**
 * Get user IP address
 *
 * Fail-closed: proxy headers (X-Forwarded-For and friends) are only
 * honored when REMOTE_ADDR itself is an exact match in trustedProxies().
 * Otherwise REMOTE_ADDR is returned so clients cannot spoof rate
 * limiting or logs. Forwarded lists resolve to the first valid IP.
 * CIDR ranges are not supported; list individual proxy IPs.
 *
 * @return string Client IP address.
 */
function getIpAddress(): string
{
    $remote = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $remote = is_string($remote) ? trim($remote) : '127.0.0.1';

    if (filter_var($remote, FILTER_VALIDATE_IP) === false) {
        $remote = '127.0.0.1';
    }

    if (!in_array($remote, trustedProxies(), true)) {
        return $remote;
    }

    $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED'];

    foreach ($headers as $header) {
        if (!isset($_SERVER[$header]) || !is_string($_SERVER[$header])) {
            continue;
        }

        $first = trim(explode(',', $_SERVER[$header])[0] ?? '');

        if ($first !== '' && filter_var($first, FILTER_VALIDATE_IP) !== false) {
            return $first;
        }
    }

    return $remote;
}

/**
 * Parse basic template
 *
 * Replaces {{key}} placeholders with string casts of the given values
 * using literal string replacement (no regex) so keys with regex chars
 * cannot break or inject patterns.
 *
 * @param string|array<string> $string Template string or strings.
 * @param array<string, mixed> $data Placeholder values keyed by name.
 * @return string|string[]|null Replaced template(s).
 */
function parseBasicTemplate(string|array $string, array $data = []): array|string|null
{
    $findArray = [];
    $replaceArray = [];

    foreach ($data as $key => $value) {
        $findArray[] = '{{' . (string) $key . '}}';
        $replaceArray[] = (string) $value;
    }

    if ($findArray === []) {
        return $string;
    }

    return str_replace($findArray, $replaceArray, $string);
}

/**
 * Get a version.
 *
 * Stable in prod when APP_VERSION is set so asset URLs
 * stay cacheable; time() in dev.
 *
 * @return string Version string.
 * @throws InvalidArgumentException When the version key is missing.
 */
function getVersion(): string
{
    return Config::get("version");
}

/**
 * CMS related routes
 */
if (APP_ENABLE_CMS) {
    require_once APP_ROOT . "/app/Utils/Admin/functions.php";
}
