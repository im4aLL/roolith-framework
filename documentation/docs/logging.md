# Logging

Logging is a PSR-3 file log with one line per entry. Each line carries the per-request trace ID, so bootstrap lines, dispatch lines, and error lines for the same request can be grepped together.

## Logger vs Log

| Class | What it is | When to use it |
| --- | --- | --- |
| `App\Core\Logger` | The file logger instance owned by `System` | Almost never directly - `System` builds it for you |
| `App\Core\Log` | Static holder mirroring the active logger | App code: `Log::info()`, `Log::error()`, and friends |

`System::__construct()` creates the `Logger` and calls `Log::setLogger()`, so every later `Log::info()` call writes to the same correlated stream. `Log` methods never throw: without a booted `System` they fall back to `error_log()`, so logging can never break a request.

```php
use App\Core\Log;

Log::info('user created', ['id' => $user->id]);
Log::warning('slow query', ['ms' => 850]);
Log::error('payment failed', ['order' => $orderId]);
```

Available levels: `debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`, plus `Log::log($level, $message, $context)`.

## Configuration

| Key | Env | Default | Meaning |
| --- | --- | --- | --- |
| `logPath` | `LOG_PATH` | `APP_ROOT/storage/logs/app.log` | Log file path |
| `logEnabled` | `LOG_ENABLED` | `false` (off) | Write routine `debug`/`info`/`notice` lines |

```bash
# Write routine info lines too (default keeps only warning and above)
LOG_ENABLED=true

# Custom log file (relative paths resolve against APP_ROOT)
LOG_PATH=storage/logs/app.log
```

Notes:

- Routine logs are off by default: `debug`, `info`, and `notice` are dropped, while `warning` and above are always written so crash visibility is never lost.
- `LOG_PATH` rejects empty values, null bytes, and `..` segments and falls back to the default instead of throwing.
- `config/config.php` exposes the same values as `logPath` and `logEnabled` for post-validation consumers. Setting `.env` alone takes effect without re-adding the key. See [Configuration](/configuration).

## Trace ID correlation

Every `System` instance generates a per-request trace ID. It appears on every log line, in the production `500` message (`Internal Server Error (trace: <id>)`, see [Error Handling](/error-handling)), and in the fallback ID `ErrorHandler` generates when boot failed before `System` existed.

Log line format:

```
[2026-01-01 12:00:00] [<traceId>] [<LEVEL>] <message> <context JSON>
```

```bash
grep "<traceId>" storage/logs/app.log
```

## Request lifecycle log lines

With `LOG_ENABLED=true`, a normal request writes lines like these (names are the exact messages, so grep for them):

| Stage | Message | Level |
| --- | --- | --- |
| Bootstrap start | `bootstrap started` | `info` |
| Env sync | `config env synced` | `info` |
| Config check | `config validated` | `info` |
| Bootstrap done | `bootstrap completed` | `info` |
| Route lint | `route validation completed` (or `... with errors`) | `info` |
| Dispatch start | `router dispatch started` with `method` plus `uri` | `info` |
| Dispatch done | `router dispatch completed` with `code` | `info` |
| Unknown path | `route not found` with `method` plus `uri` | `warning` |
| Dispatch failure | `router dispatch failed` with `error`, `class`, `method`, `uri` | `error` |
| View render | `view rendered` with `view` | `info` |
| View failure | `view render failed` with `view` plus `error` | `error` |
| Bootstrap failure | `bootstrap failed` with `error` | `error` |
| Unhandled throwable | `unhandled exception` with `error`, `class`, `file`, `trace` | `error` |

Bad route handlers are logged as `invalid route handler` warnings at boot without breaking the request. Run `php roolith route:list` to lint them as a CI gate instead - see [Response](/response).

## App logging example

Five lines, copy-paste ready:

```php
use App\Core\Log;

public function store()
{
    Log::info('user store started', ['email' => Request::input('email')]);
    $user = User::create($this->validated());
    Log::info('user created', ['id' => $user->id]);

    return $this->json($user, "success", 201, "User created.");
}
```

App logging always goes through `Log` as shown above. See [Cache](/cache) and [Events](/events) for those helpers.
