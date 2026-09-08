# Error Handling

Uncaught failures go through `App\Core\ErrorHandler`. What you see depends on `APP_ENV`: full details in development, a generic message plus a trace ID in every other environment.

## Development vs production

| Environment | What happens |
| --- | --- |
| `APP_ENV=development` | Whoops pretty page with the full exception, and the throwable is rethrown |
| Any other value (or unset, which defaults to production) | HTTP `500` with the text `Internal Server Error (trace: <traceId>)`, details hidden |

Only the exact value `development` enables verbose errors. Staging, UAT, and production all take the production path. In production `display_errors` is forced off and the baseline security headers are sent with the `500` response.

## Finding the trace in logs

Every request gets a trace ID. The `500` page prints it, and the same ID is written on the matching log line. Copy the ID from the error page and search the log file for it.

Default log file: `APP_ROOT/storage/logs/app.log` (override with `LOG_PATH`, see [Logging](/logging)).

```bash
grep "a1b2c3d4e5f60718" storage/logs/app.log
```

The matching line carries the exception class, message, file plus line, and stack trace in its context JSON:

```
[2026-01-01 12:00:00] [a1b2c3d4e5f60718] [ERROR] unhandled exception {"error":"...","class":"...","file":"...","trace":"..."}
```

Warning level and above are always written, even when routine logging is off, so the `500` trace is never lost. See [Logging](/logging) for `LOG_ENABLED` behavior.

## 404 and 405

Unknown paths respond with `404`. A path that exists for another method responds with `405 Method Not Allowed` plus an `Allow` header listing the valid methods.

| Situation | Status | Body |
| --- | --- | --- |
| No route matches the path | `404` | Rendered `views/404.php` |
| Path exists but not for this method | `405` | `Method Not Allowed. Allowed: GET` (plus `Allow: GET` header) |

### Customizing the 404 page

Edit `views/404.php`. The router renders it with a `$message` variable, so keep the escaping the default view uses:

```php
<p><?= htmlspecialchars($message ?? '', ENT_QUOTES, 'UTF-8') ?></p>
```

The `405` response has no view file. Its body is the allowed-methods message above, and the `Allow` header is always sent when headers have not already been sent.

## No custom 500 view

There is no custom `500` view file yet. Production `500` responses are the plain-text `Internal Server Error (trace: ...)` line described above. A `views/500.php` file does not exist and would not be picked up - do not create one expecting the framework to render it.

## Writing fail-closed code

Follow the same pattern the framework uses: never echo failure details with HTTP `200`. Log the detail, then throw or return an error envelope so the pipeline sets the right status.

```php
use App\Core\Log;

try {
    $html = $this->view('report', $data);
} catch (\Throwable $e) {
    Log::error('report render failed', ['error' => $e->getMessage()]);

    return $this->json(null, "error", 500, "Could not build the report.");
}
```

See [Response](/response) for the JSON envelope shape and [Logging](/logging) for the `Log` API.
