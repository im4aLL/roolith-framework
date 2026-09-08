<?php
namespace App\Core;


use App\Core\Interfaces\FileInterface;
use App\Core\Interfaces\RequestInterface;
use App\Utils\_;

/**
 * HTTP request facade over POST, GET, and php://input streams.
 *
 * input() returns sanitized values (POST via Sanitize::any, GET via
 * Sanitize::param, stream strings via Sanitize::any with scalar types
 * preserved); unsafeInput() returns the raw value for validated/escaped
 * uses. all() sanitizes unless skipSanitization is set.
 */
class Request implements RequestInterface
{
    /**
     * Cached php://input parse result for the current request.
     *
     * @var array<string, mixed>|null
     */
    private static ?array $streamInputsCache = null;

    /**
     * Raw php://input override for tests (null means read the real stream).
     *
     * @var string|null
     */
    private static ?string $rawInputOverride = null;

    /**
     * Whether a raw input override is armed (distinguishes null-body from no override).
     *
     * @var bool
     */
    private static bool $hasRawInputOverride = false;

    /**
     * Content-Type override for tests (null means read $_SERVER).
     *
     * @var string|null
     */
    private static ?string $contentTypeOverride = null;

    /**
     * Get a sanitized request input value.
     *
     * Checks POST, then GET, then the php://input stream. Uses
     * array_key_exists so falsy values ("0", 0, null, false) count as
     * present; only a missing key falls through to the default. POST/GET
     * values are sanitized; stream (JSON/urlencoded) string values are
     * sanitized via Sanitize::any so a JSON body like
     * {"comment":"<script>alert(1)</script>hi"} never returns raw markup
     * from input(). Non-string stream scalars (int, float, bool) are
     * returned as-is to preserve types, arrays via Sanitize::items, and
     * null stays null. Use unsafeInput() for the raw stream value.
     *
     * @param string $name Input key.
     * @param mixed $default Fallback when the key is missing everywhere.
     * @return mixed Sanitized value, stream value, or the default.
     */
    public static function input(string $name, mixed $default = null): mixed
    {
        if (array_key_exists($name, $_POST)) {
            $value = $_POST[$name];

            if ($value === null) {
                return null;
            }

            if (is_array($value)) {
                return Sanitize::items($value);
            }

            return Sanitize::any($value);
        }

        if (array_key_exists($name, $_GET)) {
            $value = $_GET[$name];

            if ($value === null) {
                return null;
            }

            return is_array($value) ? Sanitize::params($value) : Sanitize::param((string) $value);
        }

        $inputs = self::streamInputs();

        if (array_key_exists($name, $inputs)) {
            return self::sanitizeStreamValue($inputs[$name]);
        }

        return $default;
    }

    /**
     * Get a raw (unsanitized) request input value.
     *
     * Same lookup order as input() but without sanitization. Only use for
     * values that are validated or escaped later. Uses array_key_exists so
     * falsy values ("0", 0, null, false) count as present.
     *
     * @param string $name Input key.
     * @return mixed Raw value or null when missing.
     */
    public static function unsafeInput(string $name): mixed
    {
        if (array_key_exists($name, $_POST)) {
            return $_POST[$name];
        }

        if (array_key_exists($name, $_GET)) {
            return $_GET[$name];
        }

        $inputs = self::streamInputs();

        if (array_key_exists($name, $inputs)) {
            return $inputs[$name];
        }

        return null;
    }

    /**
     * Get a single php://input stream value.
     *
     * Returns the parsed value for the key, or false when the key is absent.
     * The false sentinel (not null) keeps stored null/"0"/0 distinguishable
     * from missing for internal callers; public has()/input() use
     * array_key_exists on streamInputs() directly.
     *
     * @param string $name Stream key.
     * @return mixed Stream value or false when missing.
     */
    protected static function streamInput(string $name): mixed
    {
        $var = self::streamInputs();

        return array_key_exists($name, $var) ? $var[$name] : false;
    }

    /**
     * Get all php://input stream values with per-request caching.
     *
     * Reads php://input once per request and caches the parse result. When
     * the Content-Type is application/json (optionally with charset), the
     * body is decoded via json_decode(assoc=true); valid JSON objects yield
     * their assoc array, otherwise an empty array. All other bodies use
     * parse_str. Empty or unreadable bodies yield an empty array instead of
     * a TypeError, and non-array parse results are normalized to an array.
     *
     * @return array<string, mixed> Parsed stream inputs.
     */
    protected static function streamInputs(): array
    {
        if (self::$streamInputsCache !== null) {
            return self::$streamInputsCache;
        }

        $raw = self::readRawInput();

        if (!is_string($raw) || $raw === '') {
            self::$streamInputsCache = [];

            return self::$streamInputsCache;
        }

        if (self::isJsonContentType()) {
            try {
                $decoded = json_decode($raw, true);
            } catch (\Throwable) {
                $decoded = null;
            }

            if (is_array($decoded)) {
                self::$streamInputsCache = $decoded;

                return self::$streamInputsCache;
            }

            self::$streamInputsCache = [];

            return self::$streamInputsCache;
        }

        $var = [];

        try {
            parse_str($raw, $var);
        } catch (\Throwable) {
            $var = [];
        }

        if (!is_array($var)) {
            $var = [];
        }

        self::$streamInputsCache = $var;

        return $var;
    }

    /**
     * Read the raw request body, preferring the test override.
     *
     * In production reads php://input once per request (result cached by
     * streamInputs()). In tests setRawInputForTests() arms an override so
     * JSON and urlencoded bodies can be asserted without a real stream.
     *
     * @return string|false Raw body string or false when unreadable.
     */
    private static function readRawInput(): string|false
    {
        if (self::$hasRawInputOverride) {
            return self::$rawInputOverride ?? '';
        }

        try {
            $raw = file_get_contents("php://input");
        } catch (\Throwable) {
            $raw = false;
        }

        return $raw;
    }

    /**
     * Check whether the current request carries a JSON content type.
     *
     * Matches `application/json` with optional parameters (for example
     * `application/json; charset=utf-8`), case-insensitively. Reads the
     * test override when armed, otherwise CONTENT_TYPE then
     * HTTP_CONTENT_TYPE from $_SERVER.
     *
     * @return bool True when the body should be JSON-decoded.
     */
    private static function isJsonContentType(): bool
    {
        $contentType = self::$contentTypeOverride;

        if ($contentType === null) {
            $serverType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
            $contentType = is_string($serverType) ? $serverType : '';
        }

        $mediaType = strtolower(trim(explode(';', $contentType)[0] ?? ''));

        return $mediaType === 'application/json';
    }

    /**
     * Arm a raw body plus content type for tests (test seam).
     *
     * Sets the body returned by readRawInput() and the content type checked
     * by isJsonContentType(), clearing the parsed cache so the next
     * streamInputs() call re-parses. Pass null to simulate an empty body.
     * Clear with resetForTests().
     *
     * @param string|null $rawBody Raw body to parse (null means empty body).
     * @param string|null $contentType Content-Type header value (null means urlencoded default).
     * @return void
     */
    public static function setRawInputForTests(?string $rawBody, ?string $contentType = null): void
    {
        self::$rawInputOverride = $rawBody;
        self::$hasRawInputOverride = true;
        self::$contentTypeOverride = $contentType;
        self::$streamInputsCache = null;
    }

    /**
     * Clear the cached php://input parse result (test seam).
     *
     * Canonical test seam for this facade: drops the per-request stream
     * cache plus any raw-body/content-type override so each test parses a
     * fresh body without order dependence. Kept as the canonical name;
     * resetStreamInputsForTests() remains as a BC alias.
     *
     * @return void
     */
    public static function resetForTests(): void
    {
        self::$streamInputsCache = null;
        self::$rawInputOverride = null;
        self::$hasRawInputOverride = false;
        self::$contentTypeOverride = null;
    }

    /**
     * Clear the cached php://input parse result (test seam).
     *
     * Alias of resetForTests() kept for backwards compatibility.
     *
     * @return void
     */
    public static function resetStreamInputsForTests(): void
    {
        self::resetForTests();
    }

    /**
     * Check whether a request input key exists.
     *
     * Uses array_key_exists on POST, GET, and parsed stream inputs so falsy
     * values like "0", 0, false, null, and [] count as present; only a
     * missing key returns false.
     *
     * @param string $name Input key.
     * @return bool True when the key exists in POST, GET, or stream inputs.
     */
    public static function has(string $name): bool
    {
        if (array_key_exists($name, $_POST)) {
            return true;
        }

        if (array_key_exists($name, $_GET)) {
            return true;
        }

        $inputs = self::streamInputs();

        return array_key_exists($name, $inputs);
    }

    /**
     * Sanitize a single stream (JSON/urlencoded) value preserving scalar types.
     *
     * Strings are cleaned via Sanitize::any so embedded markup like
     * `<script>` never returns raw; arrays recurse via Sanitize::items;
     * null stays null; int/float/bool pass through unchanged because they
     * carry no markup and preserving them keeps `assertSame(0, ...)` style
     * checks stable across input() and streamInputs().
     *
     * @param mixed $value Parsed stream value.
     * @return mixed Sanitized value with scalar types preserved.
     */
    private static function sanitizeStreamValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return Sanitize::items($value);
        }

        if (is_string($value)) {
            return Sanitize::any($value);
        }

        return $value;
    }

    /**
     * Get all request inputs for the current method.
     *
     * POST merges sanitized $_POST plus files under _files; GET returns
     * sanitized $_GET; other methods return parsed stream inputs sanitized
     * via Sanitize::items (strings cleaned, `<script>` stripped) unless
     * skipSanitization is set, in which case the raw parse is returned.
     *
     * @param array<string, mixed> $settings Optional flags (skipSanitization).
     * @return iterable<string, mixed> All inputs.
     */
    public static function all(array $settings = []): iterable
    {
        $isSkipSanitization = isset($settings['skipSanitization']) && $settings['skipSanitization'];

        if (self::isMethod('POST')) {
            $items = $isSkipSanitization ? $_POST : Sanitize::items($_POST);
            $files = self::allFiles();

            if (count($files) > 0) {
                $items['_files'] = $files;
            }

            return $items;
        }

        if (self::isMethod('GET')) {
            return $isSkipSanitization ? $_GET : Sanitize::items($_GET);
        }

        $inputs = self::streamInputs();

        return $isSkipSanitization ? $inputs : Sanitize::items($inputs);
    }

    /**
     * Get all files
     *
     * @return array<string, mixed> Uploaded files keyed by input name.
     */
    public static function allFiles(): array
    {
        $files = $_FILES;
        $result = [];

        if (count($files) == 0) {
            return $result;
        }

        foreach ($files as $key => $value) {
            if (is_array($value['name'])) {
                $multipleFiles = self::splitMultipleFiles($value);

                $result[$key] = [];
                foreach ($multipleFiles as $index => $file) {
                    $result[$key][] = self::file($key, $file);
                }
            } else {
                $result[$key] = self::file($key);
            }
        }

        return $result;
    }

    /**
     * Split multiple uploaded files into a single chuck array
     *
     * @param array<string, mixed> $files Raw $_FILES entry with array shapes.
     * @return array<int, array<string, mixed>> Single-file chunks.
     */
    public static function splitMultipleFiles(array $files): array
    {
        $result = [];

        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            $result[] = [
                'name'     => $files['name'][$i],
                'full_path'=> $files['full_path'][$i] ?? '',
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
        }

        return $result;
    }

    /**
     * Get only the given keys from all inputs.
     *
     * @param string|array<int|string, mixed> $name Key or keys to keep.
     * @return array<string, mixed> Filtered inputs.
     */
    public static function only(string|array $name): array
    {
        $inputs = self::all();

        return _::only($inputs, $name);
    }

    /**
     * Get all inputs except the given keys.
     *
     * @param string|array<int|string, mixed> $name Key or keys to drop.
     * @return array<string, mixed> Remaining inputs.
     */
    public static function except(string|array $name): array
    {
        $inputs = self::all();

        return _::except($inputs, $name);
    }

    /**
     * Redirect to the given URL with an explicit temporary code.
     *
     * Deliberately 302 (Found): the legacy temporary redirect used by
     * existing callers. Use the global redirect() helper with 303 for
     * Post/Redirect/Get after form posts, or 301/308 for permanent moves.
     * CR/LF is stripped and only single-slash relative URLs or allowlisted
     * absolute URLs are sent; anything else falls back to / to block open
     * redirects.
     *
     * @param string $url Redirect target.
     * @return void
     */
    public static function redirect(string $url): void
    {
        $target = PreProcessor::resolveSafeRedirectTarget($url);

        header('Location: ' . $target, true, 302);
        exit();
    }

    /**
     * Get a cookie value by name.
     *
     * @param string $name Cookie name.
     * @return mixed Cookie value or null when missing.
     */
    public static function cookie(string $name): mixed
    {
        return $_COOKIE[$name] ?? null;
    }

    /**
     * Get an uploaded file wrapper by input name.
     *
     * @param string $name File input name.
     * @param array<string, mixed>|null $fileData Single-file chunk override (multiple uploads).
     * @return bool|FileInterface File wrapper or false when missing.
     */
    public static function file(string $name, ?array $fileData = null): bool|FileInterface
    {
        if (self::hasFile($name)) {
            $fileInstance = new File();

            return $fileInstance->setFile($fileData ?? $_FILES[$name]);
        }

        return false;
    }

    /**
     * Check whether the request has an uploaded file.
     *
     * @param string $name File input name.
     * @return bool True when $_FILES contains the key.
     */
    public static function hasFile(string $name): bool
    {
        return isset($_FILES[$name]);
    }

    /**
     * @inheritDoc
     */
    public static function ajax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * @inheritDoc
     */
    public static function url(): bool|string
    {
        return strtok(self::fullUrl(), '?');
    }

    /**
     * Get full url with query param
     *
     * The Host header is validated against the baseUrl allowlist so a
     * spoofed Host can never leak into built URLs; mismatches fall back
     * to the configured base host and are logged.
     *
     * @return string Absolute URL for the current request.
     */
    public static function fullUrl(): string
    {
        $uri = PreProcessor::encodeUri((string) ($_SERVER['REQUEST_URI'] ?? '/'));
        $rawHost = $_SERVER['HTTP_HOST'] ?? null;

        if (!is_string($rawHost) || trim(str_replace(["\r", "\n"], '', $rawHost)) === '') {
            return self::fallbackUrl($uri);
        }

        $host = PreProcessor::sanitizeHost($rawHost);
        $baseUrl = self::configuredBaseUrl();

        if ($baseUrl !== null && !PreProcessor::isAllowedHost($host, $baseUrl)) {
            error_log('[Roolith Request] Host mismatch, falling back to baseUrl host: ' . substr(str_replace(["\r", "\n"], ' ', $host), 0, 200));

            return self::fallbackUrl($uri, $baseUrl);
        }

        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');

        return $scheme . '://' . $host . $uri;
    }

    /**
     * Build a fallback URL from the configured base URL.
     *
     * Used when no Host header is present (CLI, tests) or when the Host
     * header fails allowlist validation.
     *
     * @param string $uri Encoded request URI starting with /.
     * @param string|null $baseUrl Optional base URL override.
     * @return string Absolute fallback URL.
     */
    private static function fallbackUrl(string $uri, ?string $baseUrl = null): string
    {
        $resolved = $baseUrl ?? self::configuredBaseUrl();

        if ($resolved === null) {
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');

            return $scheme . '://localhost' . $uri;
        }

        $parts = parse_url(trim($resolved));

        if (!is_array($parts) || !isset($parts['host'])) {
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');

            return $scheme . '://localhost' . $uri;
        }

        $scheme = is_string($parts['scheme'] ?? null) ? (string) $parts['scheme'] : 'http';
        $host = (string) $parts['host'];

        if (isset($parts['port'])) {
            $host .= ':' . (string) $parts['port'];
        }

        return $scheme . '://' . $host . $uri;
    }

    /**
     * Read the configured base URL without throwing.
     *
     * @return string|null Configured base URL or null when unavailable.
     */
    private static function configuredBaseUrl(): ?string
    {
        try {
            $baseUrl = \Roolith\Configuration\Config::get('baseUrl');
        } catch (\Throwable) {
            return null;
        }

        if (!is_string($baseUrl) || trim($baseUrl) === '') {
            return null;
        }

        return $baseUrl;
    }

    /**
     * Get the current HTTP method, defaulting to GET outside web requests.
     *
     * @return string Uppercase method name or GET when unavailable.
     */
    public static function method(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        return is_string($method) ? $method : 'GET';
    }

    /**
     * Check whether the current method matches the given name.
     *
     * Comparison is exact (case-sensitive); pass an uppercase name.
     *
     * @param string $methodName Method name to compare (for example GET).
     * @return bool True when the current method matches.
     */
    public static function isMethod(string $methodName): bool
    {
        return self::method() === $methodName;
    }
}
