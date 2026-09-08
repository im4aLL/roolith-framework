<?php
namespace App\Core\Exceptions;

use App\Core\Response;
use Exception as BaseException;
use Throwable;

/**
 * Redirect control-flow exception carrying a returnable Response.
 *
 * Thrown instead of calling exit/die so System::complete() (disconnect plus
 * temp cleanup) still runs via the front controller or shutdown fallback.
 * The front controller catches this, emits the Response, then completes.
 */
class RedirectException extends BaseException
{
    /**
     * Create a redirect exception for the given target.
     *
     * @param string $target Safe redirect target (already allowlisted by callers).
     * @param int $statusCode HTTP redirect code (301, 302, 303, 307, 308).
     * @param Throwable|null $previous Previous throwable for chaining.
     */
    public function __construct(
        private string $target,
        private int $statusCode = 303,
        ?Throwable $previous = null
    ) {
        parent::__construct("Redirect to {$target}", $statusCode, $previous);
    }

    /**
     * Get the redirect target.
     *
     * @return string Safe redirect target.
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * Get the redirect status code.
     *
     * @return int HTTP redirect code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Build the carried Response with a Location header and empty body.
     *
     * @return Response Response emitting the redirect.
     */
    public function getResponse(): Response
    {
        return Response::redirect($this->target, $this->statusCode);
    }
}
