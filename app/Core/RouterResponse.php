<?php
namespace App\Core;

use Roolith\Route\Response as VendorResponse;

/**
 * Vendor router response that understands App\Core\Response values.
 *
 * Controllers return string (HTML) or App\Core\Response (JSON, redirect,
 * custom status). The vendor Router calls body($content) with that value;
 * without unwrapping, an App Response object would be JSON-encoded as its
 * private properties. This subclass detects App responses and emits their
 * status, headers, and body string instead.
 */
class RouterResponse extends VendorResponse
{
    /**
     * Show response body, unwrapping App responses.
     *
     * App responses set their own status code and headers (skipped when
     * headers were already sent, matching vendor header safety), then echo
     * the body string verbatim. All other values delegate to the vendor
     * behavior (array/object as JSON, string as HTML).
     *
     * @param mixed $content Controller return value or App response.
     * @return static Self for chaining.
     */
    public function body(mixed $content = ''): static
    {
        if ($content instanceof Response) {
            $this->setStatusCode($content->status());

            foreach ($content->headers() as $name => $value) {
                if (!headers_sent()) {
                    header((string) $name . ': ' . (string) $value);
                }
            }

            $this->markContentTypeWhenPresent($content);

            $output = $content->body();
            echo $output;
            $this->lastOutput = $output;

            return $this;
        }

        return parent::body($content);
    }

    /**
     * Render response body without echoing, unwrapping App responses.
     *
     * Mirrors body() header handling (L5): status plus headers are applied
     * (guarded by headers_sent) and the vendor Content-Type flag is marked
     * so Location and Content-Type survive render-only paths.
     *
     * @param mixed $content Controller return value or App response.
     * @return string Rendered body string.
     */
    public function renderBody(mixed $content = ''): string
    {
        if ($content instanceof Response) {
            $this->setStatusCode($content->status());

            foreach ($content->headers() as $name => $value) {
                if (!headers_sent()) {
                    header((string) $name . ': ' . (string) $value);
                }
            }

            $this->markContentTypeWhenPresent($content);

            $output = $content->body();
            $this->lastOutput = $output;

            return $output;
        }

        return parent::renderBody($content);
    }

    /**
     * Mark the vendor Content-Type flag when the App response carries one.
     *
     * Prevents a later vendor setHeaderJson/setHeaderHtml from emitting a
     * second Content-Type on the same response.
     *
     * @param Response $response App response to inspect.
     * @return void
     */
    private function markContentTypeWhenPresent(Response $response): void
    {
        if ($response->header('Content-Type') !== null) {
            $this->hasHeaderContentType = true;
        }
    }
}
