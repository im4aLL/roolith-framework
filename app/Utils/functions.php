<?php

use App\Support\Debug;
use App\Support\Html;
use App\Support\IdGenerator;
use App\Support\Redirect as RedirectSupport;
use App\Support\Translator;
use App\Support\Url as UrlSupport;
use Carbon\Carbon;
use Roolith\Configuration\Exception\InvalidArgumentException;

/**
 * Print anything for debugging.
 *
 * Thin BC alias over App\Support\Debug::dump(). CLI-aware: web SAPIs
 * wrap escaped output in pre tags, CLI prints plain text. Dev-only
 * exit: $exit terminates via exit() only when
 * APP_ENV=development, otherwise ignored so production can never
 * truncate emission or skip System::complete().
 *
 * @param mixed $any Value to print.
 * @param bool $exit Terminate after printing, dev environments only.
 * @return void
 */
function p(mixed $any, bool $exit = false): void
{
    Debug::dump($any, $exit);
}

/**
 * Escape a value for HTML output at render time.
 *
 * Thin BC helper over App\Support\Html::escape(). Use in views for
 * every untrusted value instead of sanitizing on input, so stored
 * data like O'Reilly keeps its raw form and markup cannot execute.
 *
 * @param mixed $value Raw value to escape.
 * @return string Escaped string safe for HTML output.
 */
function escape(mixed $value): string
{
    return Html::escape($value);
}

/**
 * Prefix the app base URL to a path with slash normalization.
 *
 * Thin BC alias over App\Support\Url::to(). See that method for
 * slash and fail-closed baseUrl semantics.
 *
 * @param string $path Path to prefix (for example assets/css/app.css or /assets/css/app.css).
 * @return string Absolute URL when baseUrl exists, otherwise a root-relative path.
 * @throws InvalidArgumentException When baseUrl is missing and APP_ENV is development.
 */
function url(string $path): string
{
    return UrlSupport::to($path);
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
        return rtrim((string) \Roolith\Configuration\Config::get("viteDevServer"), "/");
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
 * Never throws for a missing version: falls back to `dev` in development
 * or `1.0.0` otherwise so a misconfigured version cannot 500 the page.
 *
 * @param string $path Built path e.g. assets/css/app.css.
 * @return string Absolute asset URL with ?v=version.
 */
function viteBuiltAssetUrl(string $path): string
{
    try {
        $version = getVersion();
    } catch (\Throwable $e) {
        error_log('[Roolith viteBuiltAssetUrl] Missing version config, using fallback: ' . $e->getMessage());

        $version = isDevEnvironment() ? 'dev' : '1.0.0';
    }

    if (trim($version) === '') {
        error_log('[Roolith viteBuiltAssetUrl] Empty version config, using fallback.');

        $version = isDevEnvironment() ? 'dev' : '1.0.0';
    }

    return url($path . "?v=" . $version);
}

/**
 * Read the Vite prod manifest when present.
 *
 * Looks for assets/build/.vite/manifest.json (Vite 5 default when
 * manifest:true with outDir assets/build) then assets/build/manifest.json,
 * keeping the legacy assets/.vite locations as a fallback so
 * older deploys still resolve. Missing or invalid files yield
 * []. Results are cached per request in $GLOBALS so the test seam fully
 * clears; tests can override via setViteManifestForTests().
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
        rtrim($base, "/\\") . '/assets/build/.vite/manifest.json',
        rtrim($base, "/\\") . '/assets/build/manifest.json',
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
 * assets/build/<file> when the manifest has it; otherwise returns the stable
 * built path fallback.
 *
 * @param string $sourcePath Vite source entry (manifest key).
 * @param string $builtPath Stable built path fallback e.g. assets/build/js/app.js.
 * @return string Hashed or fallback built path.
 */
function viteManifestFile(string $sourcePath, string $builtPath): string
{
    $manifest = viteManifest();
    $entry = $manifest[$sourcePath] ?? null;

    if (is_array($entry) && isset($entry['file']) && is_string($entry['file']) && trim($entry['file']) !== '') {
        return 'assets/build/' . ltrim(trim($entry['file']), '/');
    }

    return $builtPath;
}

/**
 * Render the vite client tag once per page.
 *
 * Render-once state lives in $GLOBALS so tests can reset it via
 * resetViteClientTagForTests() without process isolation. The dev server
 * URL is escaped so a misconfigured VITE_DEV_SERVER cannot inject markup.
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

    $escapedServer = htmlspecialchars(rtrim($devServer, "/"), ENT_QUOTES, 'UTF-8');

    return "<script type=\"module\" src=\"{$escapedServer}/@vite/client\"></script>";
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
 * In dev with a Vite server, points at the server source and emits the HMR
 * client tag once per page (shared with viteJs, so JS-only pages also get
 * HMR). In prod, prefers the hashed file from the Vite manifest
 * (content-hashed filename, no query needed) and falls back to the stable
 * built path plus ?v=version. All URLs are escaped with htmlspecialchars
 * so manifest or env values cannot inject markup.
 *
 * @param string $sourcePath Vite source entry e.g. source/scss/app.scss (manifest key).
 * @param string $builtPath Stable built fallback e.g. assets/build/css/app.css.
 * @return string Link tag HTML.
 */
function viteCss(string $sourcePath, string $builtPath): string
{
    $devServer = viteDevServerUrl();

    if (isDevEnvironment() && $devServer !== "") {
        $escapedServer = htmlspecialchars(rtrim($devServer, "/"), ENT_QUOTES, 'UTF-8');
        $escapedSource = htmlspecialchars(ltrim($sourcePath, "/"), ENT_QUOTES, 'UTF-8');

        return viteClientTag($devServer) .
            "<link rel=\"stylesheet\" href=\"{$escapedServer}/{$escapedSource}\">";
    }

    $resolved = viteManifestFile($sourcePath, $builtPath);

    if ($resolved !== $builtPath) {
        $escapedUrl = htmlspecialchars(url($resolved), ENT_QUOTES, 'UTF-8');

        return "<link rel=\"stylesheet\" href=\"{$escapedUrl}\">";
    }

    $escapedUrl = htmlspecialchars(viteBuiltAssetUrl($builtPath), ENT_QUOTES, 'UTF-8');

    return "<link rel=\"stylesheet\" href=\"{$escapedUrl}\">";
}

/**
 * Render a script tag for a vite entry.
 *
 * In dev with a Vite server, points at the server source and emits the HMR
 * client tag once per page (shared with viteCss, so JS-only pages no longer
 * miss HMR). In prod, prefers the hashed file from the Vite manifest and
 * falls back to the stable built path plus ?v=version. Both dev and prod
 * tags use type="module" so prod bundles match dev module semantics
 * (Vite emits ES modules; a plain script tag would fail on import syntax).
 * All URLs are escaped with htmlspecialchars.
 *
 * @param string $sourcePath Vite source entry e.g. source/js/app.js (manifest key).
 * @param string $builtPath Stable built fallback e.g. assets/build/js/app.js.
 * @return string Script tag HTML.
 */
function viteJs(string $sourcePath, string $builtPath): string
{
    $devServer = viteDevServerUrl();

    if (isDevEnvironment() && $devServer !== "") {
        $escapedServer = htmlspecialchars(rtrim($devServer, "/"), ENT_QUOTES, 'UTF-8');
        $escapedSource = htmlspecialchars(ltrim($sourcePath, "/"), ENT_QUOTES, 'UTF-8');

        return viteClientTag($devServer) .
            "<script type=\"module\" src=\"{$escapedServer}/{$escapedSource}\"></script>";
    }

    $resolved = viteManifestFile($sourcePath, $builtPath);

    if ($resolved !== $builtPath) {
        $escapedUrl = htmlspecialchars(url($resolved), ENT_QUOTES, 'UTF-8');

        return "<script type=\"module\" src=\"{$escapedUrl}\"></script>";
    }

    $escapedUrl = htmlspecialchars(viteBuiltAssetUrl($builtPath), ENT_QUOTES, 'UTF-8');

    return "<script type=\"module\" src=\"{$escapedUrl}\"></script>";
}

/**
 * Get url by router name.
 *
 * Thin BC alias over App\Support\Url::route().
 *
 * @param string $name Route name.
 * @param array<string, mixed> $settings Route params keyed by placeholder.
 * @return string Absolute URL for the named route.
 */
function route(string $name, array $settings = []): string
{
    return UrlSupport::route($name, $settings);
}

/**
 * Get active route.
 *
 * Thin BC alias over App\Support\Url::activeRoute().
 *
 * @return array<string, mixed> Active route data or empty array.
 */
function getActiveRoute(): array
{
    return UrlSupport::activeRoute();
}

/**
 * Get a translated message.
 *
 * Canonical helper is trans(); __() stays as a thin gettext-compatible
 * alias so existing views keep working while new code prefers trans().
 *
 * @param string $key Message key in dot notation.
 * @return mixed Message value or null when missing.
 */
function trans(string $key): mixed
{
    return Translator::trans($key);
}

/**
 * Get a message.
 *
 * BC alias over trans() kept for gettext familiarity and existing
 * views. New code should call trans() directly.
 *
 * @param string $name Message key.
 * @return mixed Message value or null when missing.
 */
function __(string $name): mixed
{
    return trans($name);
}

/**
 * Build a redirect response for a URL.
 *
 * Thin BC alias over App\Support\Redirect::to() which reuses
 * PreProcessor::resolveSafeRedirectTarget(). No-exit by design:
 * returns an immutable App\Core\Response instead of sending headers
 * plus die() so System::complete() always runs. Callers must
 * `return redirect(...)` from the controller (emitted via
 * RouterResponse) or throw RedirectException.
 *
 * Status is deliberate: 303 (See Other) is the default because it implements
 * Post/Redirect/Get safely by always following up with GET. Pass 302 for a
 * temporary redirect where legacy behavior is needed, or 301/308 for a
 * permanent redirect (308 preserves the method, 301 may not).
 *
 * Only single-slash relative URLs or absolute URLs allowlisted against the
 * base URL are sent; anything else falls back to / to block open redirects.
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
    return RedirectSupport::to($url, $statusCode);
}

/**
 * Build a redirect response for a route name.
 *
 * Thin BC alias over App\Support\Redirect::toRoute(). No-exit: returns
 * a Response instead of dying. Uses 303 by default for the same
 * Post/Redirect/Get reason as redirect().
 *
 * @param string $routeName Route name.
 * @param array<string, mixed> $settings Route params.
 * @param int $statusCode HTTP redirect code, 303 by default.
 * @return \App\Core\Response Redirect response with a Location header.
 */
function redirectToRoute(string $routeName, array $settings = [], int $statusCode = 303): \App\Core\Response
{
    return RedirectSupport::toRoute($routeName, $settings, $statusCode);
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
 * Thin BC alias over App\Support\IdGenerator::alphaNumeric(). Format is
 * 4 uppercase hex chars, dash, 16 hex chars (for example
 * 3F2A-9f4c2a1be07d83c1), all from random_bytes() so IDs are
 * unpredictable and collision-resistant.
 *
 * @return string Unique crypto-random identifier.
 */
function generateUniqueAlphaNumericNumber(): string
{
    return IdGenerator::alphaNumeric();
}

/**
 * Generate unique number.
 *
 * Thin BC alias over App\Support\IdGenerator::uniqueNumber(). Format is
 * 16 hex chars, dash, 8 hex chars (for example
 * 9f4c2a1be07d83c1-4d2e9a0b), all from random_bytes().
 *
 * @return string Unique crypto-random identifier.
 */
function generateUniqueNumber(): string
{
    return IdGenerator::uniqueNumber();
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
        $configured = \Roolith\Configuration\Config::get('trustedProxies');
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
 * @return string|string[] Replaced template(s).
 */
function parseBasicTemplate(string|array $string, array $data = []): array|string
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
    return \Roolith\Configuration\Config::get("version");
}

/**
 * CMS admin helpers (CMS-only).
 *
 * Mounted only when the explicit APP_ENABLE_CMS env flag is on and the CMS
 * release asset installed app/Utils/Admin/functions.php (see
 * docs/cms-installer.md). Core boots without it; installer.zip stays tracked
 * locally for reference and local install but is omitted from dist via
 * archive.exclude plus export-ignore plus dockerignore and hidden over HTTP
 * via .htaccess 404.
 *
 * Gate parity with System.php and routes.php: defined plus flag plus is_file
 * plus is_readable so CMS helpers never load in core-only mode or from an
 * unreadable path.
 */
$cmsAdminFunctions = (defined('APP_ROOT') ? (string) APP_ROOT : dirname(__DIR__, 2)) . "/app/Utils/Admin/functions.php";

if (defined('APP_ENABLE_CMS') && APP_ENABLE_CMS && is_file($cmsAdminFunctions) && is_readable($cmsAdminFunctions)) {
    require_once $cmsAdminFunctions;
}
