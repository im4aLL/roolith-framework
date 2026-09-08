<?php
namespace App\Core;

use Psr\Log\LoggerInterface;
use Roolith\Configuration\Config;

/**
 * HTTP host pre-processing redirects (www handling).
 *
 * Guards skip redirects when no HTTP host is present (CLI, tests) so
 * bootstrap stays runnable outside a web request. Hosts are validated
 * against the baseUrl allowlist so a spoofed Host header can never leak
 * into a Location redirect; mismatches are logged and dropped.
 */
class PreProcessor
{
    /**
     * Permanent redirect code for canonical www redirects.
     *
     * @var int
     */
    public const CANONICAL_REDIRECT_CODE = 301;

    /**
     * Redirect www hosts to the bare domain.
     *
     * No-exit by design: returns a redirect Response instead of calling
     * exit so System::complete() (disconnect plus temp cleanup) always runs.
     * Callers must emit the response or throw RedirectException. No-op
     * (null) when HTTP_HOST is unset, already bare, or not allowlisted.
     * Mismatched hosts are logged and dropped without a redirect.
     *
     * @param LoggerInterface|null $logger Optional PSR-3 logger for host mismatches.
     * @param string|null $baseUrl Optional base URL override (tests); defaults to config baseUrl.
     * @return Response|null Redirect response when a redirect is needed, null otherwise.
     */
    public static function forceNonWww(?LoggerInterface $logger = null, ?string $baseUrl = null): ?Response
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            return null;
        }

        $host = self::sanitizeHost((string) $_SERVER['HTTP_HOST']);

        if ($host === '') {
            return null;
        }

        $resolvedBaseUrl = $baseUrl ?? self::configuredBaseUrl();

        if ($resolvedBaseUrl !== null && !self::isAllowedHost($host, $resolvedBaseUrl)) {
            self::logHostMismatch($host, $logger);

            return null;
        }

        if (str_starts_with($host, 'www.')) {
            $uri = self::encodeUri((string) ($_SERVER['REQUEST_URI'] ?? '/'));
            $scheme = self::currentScheme();
            $target = $scheme . '://' . substr($host, 4) . $uri;

            return Response::redirect($target, self::CANONICAL_REDIRECT_CODE);
        }

        return null;
    }

    /**
     * Redirect bare hosts to the www domain.
     *
     * No-exit by design: returns a redirect Response instead of calling
     * exit so System::complete() always runs. Callers must emit the
     * response or throw RedirectException. No-op (null) when HTTP_HOST is
     * unset, already has www, or not allowlisted. Mismatched hosts are
     * logged and dropped without a redirect.
     *
     * @param LoggerInterface|null $logger Optional PSR-3 logger for host mismatches.
     * @param string|null $baseUrl Optional base URL override (tests); defaults to config baseUrl.
     * @return Response|null Redirect response when a redirect is needed, null otherwise.
     */
    public static function forceWww(?LoggerInterface $logger = null, ?string $baseUrl = null): ?Response
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            return null;
        }

        $host = self::sanitizeHost((string) $_SERVER['HTTP_HOST']);

        if ($host === '') {
            return null;
        }

        $resolvedBaseUrl = $baseUrl ?? self::configuredBaseUrl();

        if ($resolvedBaseUrl !== null && !self::isAllowedHost($host, $resolvedBaseUrl)) {
            self::logHostMismatch($host, $logger);

            return null;
        }

        if (!str_starts_with($host, 'www.')) {
            $uri = self::encodeUri((string) ($_SERVER['REQUEST_URI'] ?? '/'));
            $scheme = self::currentScheme();
            $target = $scheme . '://www.' . $host . $uri;

            return Response::redirect($target, self::CANONICAL_REDIRECT_CODE);
        }

        return null;
    }

    /**
     * Check whether a Host header value is allowlisted by the base URL.
     *
     * Comparison is case-insensitive on the hostname part and ignores the
     * port so localhost:8080 still matches a localhost base URL. The
     * allowlist is the base host plus its www/bare counterpart so
     * canonical redirects keep working. Null or unparsable base URLs
     * return true so CLI and tests without config stay runnable.
     *
     * @param string $host Raw Host header value.
     * @param string|null $baseUrl Base URL to derive the allowlist from.
     * @return bool True when the host may be trusted for redirects and URL building.
     */
    public static function isAllowedHost(string $host, ?string $baseUrl = null): bool
    {
        $resolvedBaseUrl = $baseUrl ?? self::configuredBaseUrl();

        if ($resolvedBaseUrl === null) {
            return true;
        }

        $baseHost = self::baseHost($resolvedBaseUrl);

        if ($baseHost === null) {
            return true;
        }

        $hostname = self::hostnameOf($host);

        if ($hostname === '') {
            return false;
        }

        foreach (self::allowedHosts($resolvedBaseUrl) as $allowed) {
            if ($hostname === $allowed) {
                return true;
            }
        }

        return false;
    }

    /**
     * List hostnames allowed for the given base URL.
     *
     * Contains the base host plus its www/bare counterpart, lowercased
     * and without ports. Empty when the base URL has no parsable host.
     *
     * @param string $baseUrl Base URL to derive the allowlist from.
     * @return array<int, string> Allowed lowercase hostnames.
     */
    public static function allowedHosts(string $baseUrl): array
    {
        $baseHost = self::baseHost($baseUrl);

        if ($baseHost === null) {
            return [];
        }

        if (str_starts_with($baseHost, 'www.')) {
            return [$baseHost, substr($baseHost, 4)];
        }

        return [$baseHost, 'www.' . $baseHost];
    }

    /**
     * Extract the lowercase hostname from a base URL.
     *
     * @param string $baseUrl Base URL to parse.
     * @return string|null Lowercase hostname or null when unparsable.
     */
    public static function baseHost(string $baseUrl): ?string
    {
        $parts = parse_url(trim($baseUrl));

        if (!is_array($parts) || !isset($parts['host']) || !is_string($parts['host'])) {
            return null;
        }

        $host = strtolower(trim($parts['host']));

        return $host === '' ? null : $host;
    }

    /**
     * Build the non-www redirect target without sending headers (test seam).
     *
     * Returns null when no redirect is needed so tests can assert the
     * decision without triggering exit.
     *
     * @param string $host Sanitized Host header value.
     * @param string $uri Raw request URI.
     * @param string $scheme URL scheme with no trailing ://.
     * @return string|null Redirect target or null when already bare.
     */
    public static function buildNonWwwRedirect(string $host, string $uri, string $scheme): ?string
    {
        if (!str_starts_with($host, 'www.')) {
            return null;
        }

        return $scheme . '://' . substr($host, 4) . self::encodeUri($uri);
    }

    /**
     * Build the www redirect target without sending headers (test seam).
     *
     * Returns null when no redirect is needed so tests can assert the
     * decision without triggering exit.
     *
     * @param string $host Sanitized Host header value.
     * @param string $uri Raw request URI.
     * @param string $scheme URL scheme with no trailing ://.
     * @return string|null Redirect target or null when already www.
     */
    public static function buildWwwRedirect(string $host, string $uri, string $scheme): ?string
    {
        if (str_starts_with($host, 'www.')) {
            return null;
        }

        return $scheme . '://www.' . $host . self::encodeUri($uri);
    }

    /**
     * Resolve a redirect target to a safe Location value (test seam).
     *
     * Allows single-slash relative URLs (for example /dashboard) and absolute
     * http/https URLs whose host is allowlisted by the base URL. All other
     * inputs (protocol-relative //evil, other schemes like javascript:, unknown
     * hosts, empty) fall back to / so open-redirect payloads such as
     * ?next=https://evil.com can never escape. CR/LF is stripped first to
     * block header injection.
     *
     * @param string $url Raw redirect target.
     * @param string|null $baseUrl Optional base URL override (tests); defaults to config baseUrl.
     * @param LoggerInterface|null $logger Optional PSR-3 logger for dropped targets.
     * @return string Safe redirect target, / when the input is rejected.
     */
    public static function resolveSafeRedirectTarget(string $url, ?string $baseUrl = null, ?LoggerInterface $logger = null): string
    {
        $sanitized = trim(str_replace(["\r", "\n"], '', $url));

        if ($sanitized === '') {
            return '/';
        }

        if (str_starts_with($sanitized, '/')) {
            if (str_starts_with($sanitized, '//') || str_starts_with($sanitized, '/\\')) {
                self::logHostMismatch($sanitized, $logger);

                return '/';
            }

            return $sanitized;
        }

        $parts = parse_url($sanitized);

        if (!is_array($parts)) {
            return '/';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if ($scheme !== 'http' && $scheme !== 'https') {
            self::logHostMismatch($sanitized, $logger);

            return '/';
        }

        if (!isset($parts['host']) || !is_string($parts['host']) || trim($parts['host']) === '') {
            return '/';
        }

        $host = (string) $parts['host'];

        if (isset($parts['port'])) {
            $host .= ':' . (string) $parts['port'];
        }

        $resolvedBaseUrl = $baseUrl ?? self::configuredBaseUrl();

        if ($resolvedBaseUrl !== null && !self::isAllowedHost($host, $resolvedBaseUrl)) {
            self::logHostMismatch($host, $logger);

            return '/';
        }

        return $sanitized;
    }

    /**
     * Encode a request URI for safe use in a Location header.
     *
     * Strips CR/LF to block header injection, requires a leading slash
     * (falling back to / otherwise), and percent-encodes each path
     * segment. The query string and fragment are preserved verbatim after
     * CR/LF stripping (only CR/LF removed, no percent-encoding) so = and &
     * semantics survive; callers needing stricter query encoding should
     * encode params before building the URI.
     *
     * @param string $uri Raw request URI (path plus optional query).
     * @return string Encoded URI safe for a Location header.
     */
    public static function encodeUri(string $uri): string
    {
        $sanitized = str_replace(["\r", "\n"], '', $uri);

        if ($sanitized === '' || $sanitized[0] !== '/') {
            return '/';
        }

        $parts = parse_url($sanitized);

        if ($parts === false) {
            return '/';
        }

        $path = $parts['path'] ?? '/';
        $segments = explode('/', (string) $path);

        foreach ($segments as &$segment) {
            $segment = rawurlencode(rawurldecode($segment));
        }

        unset($segment);

        $encoded = implode('/', $segments);

        if (isset($parts['query'])) {
            $encoded .= '?' . str_replace(["\r", "\n"], '', (string) $parts['query']);
        }

        if (isset($parts['fragment'])) {
            $encoded .= '#' . str_replace(["\r", "\n"], '', (string) $parts['fragment']);
        }

        return $encoded;
    }

    /**
     * Strip CR/LF from a Host header value.
     *
     * @param string $host Raw Host header value.
     * @return string Sanitized host with CR/LF removed and trimmed.
     */
    public static function sanitizeHost(string $host): string
    {
        return trim(str_replace(["\r", "\n"], '', $host));
    }

    /**
     * Extract the lowercase hostname without port for comparison.
     *
     * Handles plain hosts, host:port, and bracketed IPv6.
     *
     * @param string $host Sanitized Host header value.
     * @return string Lowercase hostname without port.
     */
    public static function hostnameOf(string $host): string
    {
        $trimmed = trim($host);

        if ($trimmed === '') {
            return '';
        }

        if (str_starts_with($trimmed, '[')) {
            $end = strpos($trimmed, ']');

            if ($end === false) {
                return strtolower($trimmed);
            }

            return strtolower(substr($trimmed, 0, $end + 1));
        }

        $colonPos = strrpos($trimmed, ':');

        if ($colonPos !== false && substr_count($trimmed, ':') === 1) {
            $port = substr($trimmed, $colonPos + 1);

            if ($port === '' || ctype_digit($port)) {
                $trimmed = substr($trimmed, 0, $colonPos);
            }
        }

        return strtolower(trim($trimmed));
    }

    /**
     * Get the current request scheme from HTTPS state.
     *
     * @return string http or https.
     */
    public static function currentScheme(): string
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
    }

    /**
     * Read the configured base URL without throwing.
     *
     * Returns null when config is unavailable or empty so CLI and tests
     * without a booted config stay runnable.
     *
     * @return string|null Configured base URL or null when unavailable.
     */
    private static function configuredBaseUrl(): ?string
    {
        try {
            $baseUrl = Config::get('baseUrl');
        } catch (\Throwable) {
            return null;
        }

        if (!is_string($baseUrl) || trim($baseUrl) === '') {
            return null;
        }

        return $baseUrl;
    }

    /**
     * Log a dropped host mismatch without leaking CR/LF into logs.
     *
     * Prefers the injected PSR-3 logger and falls back to error_log so
     * static redirect paths without a logger still leave a trail.
     *
     * @param string $host Sanitized Host header value.
     * @param LoggerInterface|null $logger Optional PSR-3 logger.
     * @return void
     */
    private static function logHostMismatch(string $host, ?LoggerInterface $logger): void
    {
        $safe = substr(str_replace(["\r", "\n"], ' ', $host), 0, 200);

        if ($logger instanceof LoggerInterface) {
            try {
                $logger->warning('host mismatch dropped', ['host' => $safe]);
            } catch (\Throwable) {
                error_log('[Roolith PreProcessor] Host mismatch dropped: ' . $safe);
            }

            return;
        }

        error_log('[Roolith PreProcessor] Host mismatch dropped: ' . $safe);
    }
}
