# Response

Controllers return values, they never echo or exit. Return a string for HTML or an `App\Core\Response` for JSON, redirects, and custom status codes. The pipeline emits the value and `System::complete()` (database disconnect plus temp cleanup) always runs.

## String vs Response

| Return this | You get | When to use it |
| --- | --- | --- |
| `string` | HTML body with status `200` | Pages, fragments, plain text output |
| `Response::html($body, $status)` | HTML body with your status plus headers | HTML with a non-200 status |
| `$this->json($payload)` | JSON envelope with `Content-Type: application/json` | API output |
| `redirect($url)` | `Location` header plus empty body, no exit | Post/Redirect/Get, login gates |
| `Response::text($body, $status)` | Plain-text body | Health checks, debug endpoints |

Rules to remember:

- Never call `echo`, `exit`, or `die` in a controller. Exiting skips `System::complete()`.
- `view()` returns a string. `return $this->view('home', $data);` is a plain HTML return.
- `json()` and `redirect()` return a `Response`. Always `return` it so the pipeline can emit it.

## JSON responses

`$this->json()` is a thin proxy over `App\Core\ApiResponseTransformer::json()`. Every API response uses the same envelope shape.

Envelope shape:

| Key | Type | Meaning |
| --- | --- | --- |
| `status` | `string` | `success` or `error` |
| `payload` | `mixed` | The data, or `null` on errors |
| `message` | `string` | Human-readable message, empty by default |

Signature: `$this->json($payload, $status = "success", $code = 200, $message = "")`.

```php
public function users()
{
    return $this->json(User::all());
}
```

Response body with status `200`:

```json
{"status":"success","payload":[{"id":1,"name":"Ada"}],"message":""}
```

Error example with status `422`:

```php
public function store()
{
    return $this->json(null, "error", 422, "Email is required.");
}
```

```json
{"status":"error","payload":null,"message":"Email is required."}
```

The global `json()` helper builds the same envelope outside controllers:

```php
return json($rows, "success", 200, "User list loaded.");
```

## Redirects without exit

`redirect()` returns a `Response` with a `Location` header and an empty body. It does not send headers or stop the script, so return it from the controller.

```php
public function formSubmit()
{
    // ... save the form ...
    return redirect('/form');
}
```

Redirect helpers compared:

| Helper | Default status | Use it when |
| --- | --- | --- |
| `redirect($url, $statusCode = 303)` | `303` | Form posts (Post/Redirect/Get), the default choice |
| `redirectToRoute('welcome.form')` | `303` | Redirecting to a named route |
| `Request::redirect($url)` | `302` | Legacy callers that expect a temporary redirect |

Pass `301` or `308` for permanent moves (`308` preserves the method, `301` may not). Pass `302` only when legacy behavior is needed.

Targets are allowlisted by `PreProcessor::resolveSafeRedirectTarget()`: single-slash relative URLs such as `/dashboard` pass, absolute URLs must match the configured `baseUrl` host, and anything else (protocol-relative `//evil`, `javascript:` URLs, unknown hosts, empty input) falls back to `/`. CR/LF sequences are stripped to block header injection.

### RedirectException flow

Some redirects happen before or outside a controller return, for example the canonical www/non-www redirect in `System::preProcessor()`. Those paths throw `App\Core\Exceptions\RedirectException`, which carries a returnable `Response`:

1. Code throws `RedirectException` with a safe target plus status.
2. `System::run()` (or `ErrorHandler::handle()`) catches it - this is control flow, not an error, so nothing is logged as a failure.
3. The carried `Response` is emitted, then `complete()` runs cleanup.

You rarely need to throw it yourself. Return `redirect()` from controllers; the exception path exists so early-pipeline redirects still run cleanup.

## How emission works

`App\Core\RouterResponse` unwraps what your handler returns:

- `App\Core\Response` values emit their own status, headers, and body string.
- Everything else keeps vendor behavior: arrays and objects become JSON, strings become HTML.

`Response` is immutable. `withBody()`, `withStatus()`, `withHeader()`, and `withHeaders()` each return a modified copy.

```php
use App\Core\Response;

return Response::html('<p>Created</p>', 201);
return Response::text('ok');
return Response::json(['a' => 1], 200)->withHeader('X-Total', '1');
```

## Route lint

Prefer the callable handler form so typos fail fast:

```php
$router->get("/example", [WelcomeController::class, "index"]);
```

The legacy string form `WelcomeController::class . "@index"` still runs but is checked by `App\Core\RouteValidator` (class plus method must exist) and linted by the CLI. Invalid handlers exit with code `1`, so this works as a CI gate.

```bash
php roolith route:list
```

See [Routing](/routing) for route syntax and [CLI](/cli) for the full command list.

## Controller examples

HTML page:

```php
<?php
namespace App\Controllers;

class PageController extends Controller
{
    public function about()
    {
        return $this->view('about', ['title' => 'About us']);
    }
}
```

JSON API with success plus error paths:

```php
<?php
namespace App\Controllers;

use App\Core\Request;

class UserController extends Controller
{
    public function show($id)
    {
        $user = User::orm()->find($id);

        if ($user === null) {
            return $this->json(null, "error", 404, "User not found.");
        }

        return $this->json($user);
    }

    public function store()
    {
        $email = Request::input('email');

        if (!is_string($email) || trim($email) === '') {
            return $this->json(null, "error", 422, "Email is required.");
        }

        $user = User::create(['email' => $email]);

        return redirect('/users/' . $user->id);
    }
}
```

Redirect after a form post (Post/Redirect/Get with the `303` default):

```php
<?php
namespace App\Controllers;

class FormController extends Controller
{
    public function formSubmit()
    {
        // ... validate and save ...

        return redirect('/form');
    }
}
```
