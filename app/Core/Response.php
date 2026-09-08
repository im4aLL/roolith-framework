<?php
namespace App\Core;

/**
 * Immutable HTTP response value object.
 *
 * Controllers and helpers return this instead of calling exit/die or echoing
 * directly, so System::complete() (disconnect plus temp cleanup) always runs.
 * The front controller and RouterResponse emit it via send().
 */
final class Response
{
    /**
     * Create an immutable response value.
     *
     * @param string $body Response body (HTML string or JSON string).
     * @param int $status HTTP status code.
     * @param array<string, string> $headers Header name to value map.
     */
    public function __construct(
        private string $body = '',
        private int $status = 200,
        private array $headers = []
    ) {}

    /**
     * Get the response body.
     *
     * @return string Response body.
     */
    public function body(): string
    {
        return $this->body;
    }

    /**
     * Get the HTTP status code.
     *
     * @return int HTTP status code.
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * Get all headers.
     *
     * @return array<string, string> Header name to value map.
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Get a single header value case-insensitively.
     *
     * @param string $name Header name to look up.
     * @return string|null Header value or null when missing.
     */
    public function header(string $name): ?string
    {
        $wanted = strtolower($name);

        foreach ($this->headers as $key => $value) {
            if (strtolower((string) $key) === $wanted) {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * Return a copy with a new body.
     *
     * @param string $body New body.
     * @return static New instance with the body replaced.
     */
    public function withBody(string $body): static
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    /**
     * Return a copy with a new status.
     *
     * @param int $status New HTTP status code.
     * @return static New instance with the status replaced.
     */
    public function withStatus(int $status): static
    {
        $clone = clone $this;
        $clone->status = $status;

        return $clone;
    }

    /**
     * Return a copy with one header set.
     *
     * @param string $name Header name.
     * @param string $value Header value.
     * @return static New instance with the header set.
     */
    public function withHeader(string $name, string $value): static
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    /**
     * Return a copy with headers merged.
     *
     * @param array<string, string> $headers Headers to merge.
     * @return static New instance with merged headers.
     */
    public function withHeaders(array $headers): static
    {
        $clone = clone $this;

        foreach ($headers as $name => $value) {
            $clone->headers[(string) $name] = (string) $value;
        }

        return $clone;
    }

    /**
     * Create an HTML response.
     *
     * @param string $body HTML body.
     * @param int $status HTTP status code.
     * @param array<string, string> $headers Extra headers.
     * @return static HTML response.
     */
    public static function html(string $body, int $status = 200, array $headers = []): static
    {
        return new static($body, $status, array_merge(['Content-Type' => 'text/html; charset=UTF-8'], $headers));
    }

    /**
     * Create a plain-text response.
     *
     * @param string $body Text body.
     * @param int $status HTTP status code.
     * @param array<string, string> $headers Extra headers.
     * @return static Text response.
     */
    public static function text(string $body, int $status = 200, array $headers = []): static
    {
        return new static($body, $status, array_merge(['Content-Type' => 'text/plain; charset=UTF-8'], $headers));
    }

    /**
     * Create a JSON response with Content-Type application/json.
     *
     * @param mixed $payload Payload encoded with json_encode.
     * @param int $status HTTP status code.
     * @param array<string, string> $headers Extra headers.
     * @return static JSON response.
     */
    public static function json(mixed $payload, int $status = 200, array $headers = []): static
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (!is_string($encoded)) {
            $encoded = json_encode(['status' => 'error', 'message' => 'JSON encoding failed'], JSON_UNESCAPED_SLASHES);
        }

        if (!is_string($encoded)) {
            $encoded = '{"status":"error","message":"JSON encoding failed"}';
        }

        return new static($encoded, $status, array_merge(['Content-Type' => 'application/json; charset=UTF-8'], $headers));
    }

    /**
     * Create a redirect response with a Location header and empty body.
     *
     * The target must already be allowlisted via
     * PreProcessor::resolveSafeRedirectTarget() by callers.
     *
     * @param string $target Safe redirect target.
     * @param int $statusCode HTTP redirect code.
     * @param array<string, string> $headers Extra headers.
     * @return static Redirect response.
     */
    public static function redirect(string $target, int $statusCode = 303, array $headers = []): static
    {
        return new static('', $statusCode, array_merge(['Location' => $target], $headers));
    }

    /**
     * Emit the response: status, headers, then body.
     *
     * Header emission is skipped when headers were already sent (CLI output,
     * prior echo) so tests and embedded usage stay warning-free. The status
     * is always stored for http_response_code() readers.
     *
     * @return void
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                header((string) $name . ': ' . (string) $value);
            }
        }

        echo $this->body;
    }
}
