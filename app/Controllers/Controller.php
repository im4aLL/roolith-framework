<?php
namespace App\Controllers;


use App\Core\Log;
use App\Core\TemplateEngineFactory;
use Roolith\Configuration\Config;
use Roolith\Configuration\Exception\InvalidArgumentException;
use Roolith\Template\Engine\Exceptions\Exception as TemplateException;
use Roolith\Template\Engine\Interfaces\ViewInterface;
use Throwable;

/**
 * Base controller with view plus JSON helpers.
 *
 * Thin by design: view() renders HTML strings without echoing, json()
 * proxies ApiResponseTransformer so actions return Response|string for
 * RouterResponse emission. Failures never leak paths with HTTP 200.
 */
class Controller
{
    /**
     * Template engine instance.
     *
     * @var ViewInterface
     */
    private ViewInterface $templateEngine;

    /**
     * Controller constructor.
     *
     * @throws \App\Core\Exceptions\Exception When baseUrl is not configured.
     */
    public function __construct()
    {
        $this->templateEngine = TemplateEngineFactory::getInstance();

        try {
            $this->templateEngine->setBaseUrl(Config::get('baseUrl'));
        } catch (InvalidArgumentException $e) {
            throw new \App\Core\Exceptions\Exception("`baseUrl` is not defined!", 0, $e);
        }
    }

    /**
     * Render template engine
     *
     * Fail-closed: template failures are never echoed (which would leak
     * paths with HTTP 200). The failure is logged, then the exception is
     * rethrown with view context so the front-controller ErrorHandler logs
     * the full trace and returns a generic 500 in prod (details only in
     * dev via Whoops). BC break: false is never returned; failures throw
     * instead of returning string|bool. Controllers standardize on
     * Response|string returns: HTML via view(), JSON or redirects via
     * json()/redirect helpers returning App\Core\Response.
     *
     * @param string $filename View name.
     * @param array<string, mixed> $data View data.
     * @return string Rendered HTML.
     * @throws \App\Core\Exceptions\Exception When rendering fails.
     */
    public function view(string $filename, array $data = []): string
    {
        try {
            $rendered = $this->templateEngine->compile($filename, $data);

            try {
                Log::info('view rendered', ['view' => substr($filename, 0, 200)]);
            } catch (\Throwable) {
                // Logging must never break rendering.
            }

            return $rendered;
        } catch (TemplateException $e) {
            $detail = substr(str_replace(["\r", "\n"], ' ', $e->getMessage()), 0, 500);

            try {
                Log::error('view render failed', ['view' => substr($filename, 0, 200), 'error' => $detail]);
            } catch (\Throwable) {
                // Logging must never mask the failure.
            }

            error_log('[Roolith Controller] Failed rendering view \'' . $filename . '\': ' . $detail);

            throw new \App\Core\Exceptions\Exception(
                "Failed rendering view '{$filename}': " . $e->getMessage(),
                0,
                $e
            );
        } catch (Throwable $e) {
            $detail = substr(str_replace(["\r", "\n"], ' ', $e->getMessage()), 0, 500);

            try {
                Log::error('view render failed', ['view' => substr($filename, 0, 200), 'error' => $detail]);
            } catch (\Throwable) {
                // Logging must never mask the failure.
            }

            error_log('[Roolith Controller] Failed rendering view \'' . $filename . '\': ' . $detail);

            throw new \App\Core\Exceptions\Exception(
                "Failed rendering view '{$filename}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Build a JSON envelope response with correct headers and status.
     *
     * Thin proxy over ApiResponseTransformer::json() so controllers stay
     * uniform: `return $this->json($data);` for success or
     * `return $this->json(null, "error", 422, "Invalid");` for failures.
     * Emitted via RouterResponse with Content-Type application/json.
     *
     * @param mixed $payload Envelope payload data.
     * @param string $status Envelope status (success or error).
     * @param int $code HTTP status code.
     * @param string $message Human-readable message.
     * @return \App\Core\Response JSON response.
     */
    protected function json(mixed $payload, string $status = "success", int $code = 200, string $message = ""): \App\Core\Response
    {
        return \App\Core\ApiResponseTransformer::json($payload, $status, $code, $message);
    }
}
