# Support Helpers

The `App\Support` classes handle URLs, redirects, HTML escaping, translations, debug output, and IDs. Each one is backed by a thin global alias in `app/Utils/functions.php`, so you can call either the class or the function - new code can use either form.

| Helper | Class | Global alias | Use for |
| --- | --- | --- | --- |
| URLs | `App\Support\Url` | `url()`, `route()`, `getActiveRoute()` | Build absolute URLs and named-route links |
| Redirects | `App\Support\Redirect` | `redirect()`, `redirectToRoute()` | Post/Redirect/Get after form writes |
| Escaping | `App\Support\Html` | `escape()` | Escape untrusted data in views |
| Translations | `App\Support\Translator` | `trans()`, `__()` | Dotted-key messages for the active locale |
| Debugging | `App\Support\Debug` | `p()` | Inspect values during development |
| IDs | `App\Support\IdGenerator` | `generateUniqueAlphaNumericNumber()`, `generateUniqueNumber()` | Crypto-random identifiers |

## URLs

`Url::to()` joins the `baseUrl` config with your path and normalizes slashes, so both `assets/css/app.css` and `/assets/css/app.css` produce one slash. When `baseUrl` is missing it throws in development (fail fast) and falls back to a root-relative path in production.

```php
use App\Support\Url;

echo Url::to('assets/css/app.css'); // https://example.com/assets/css/app.css
echo url('/assets/css/app.css'); // same, via alias
```

`Url::route()` resolves a named route through the shared router. It never throws for unknown names - the router returns the base URL unchanged.

```php
echo Url::to('assets/css/app.css'); // https://example.com/assets/css/app.css
echo Url::route('user.show', ['id' => 7]); // https://example.com/users/7
echo route('home'); // same, via alias
```

`Url::activeRoute()` returns the matched route with its payload, or `[]` when nothing matches.

```php
$route = Url::activeRoute(); // ['name' => 'home', ...] or []
$route = getActiveRoute(); // same, via alias
```

## Redirects

`Redirect::to()` returns an immutable response with a `Location` header - it never exits, so `System::complete()` always runs. The default status is `303` (See Other), which implements Post/Redirect/Get: the browser follows up with a `GET` after a form `POST`. Pass `302` for legacy temporary or `301`/`308` for permanent semantics.

```php
use App\Support\Redirect;

return Redirect::to('/thanks'); // 303, via Location: /thanks
return redirect('/thanks', 302); // same, via alias, legacy status
```

Unsafe targets fall back to `/`, which blocks open redirects. Only single-slash relative URLs or absolute URLs allowlisted against the base URL are sent.

```php
return Redirect::to('https://evil.example/phish'); // Location: /
return Redirect::toRoute('user.show', ['id' => 7]); // 303 to named route
```

Always `return` the response from the controller so the router can emit it.

## Escaping

Escape at render time, in the view, for every untrusted value. Do not sanitize on input - stored data like `O'Reilly` keeps its raw form and markup cannot execute at output.

```php
use App\Support\Html;

echo Html::escape($user['name']); // &lt;b&gt;Hadi&lt;/b&gt; stays inert
echo escape($comment['body']); // same, via alias
```

`Html::escape()` stringifies scalars and `null`, encodes with `ENT_QUOTES` in UTF-8, and returns `''` for arrays and non-stringable objects instead of leaking structure.

## Translations

`Translator::trans()` resolves a dotted key for the active locale and returns `null` when the key or locale dictionary is missing, so views coalesce to a default. `__()` is a gettext-compatible alias for existing views - new code should prefer `trans()`.

```php
echo trans('errors.required') ?? 'This field is required';
echo __('errors.required') ?? 'This field is required'; // same, via alias
```

Keys use dot notation against the locale catalog (see [Localization](/localization)).

```php
trans('auth.login.title'); // reads lang/<locale>/message.php -> auth.login.title
```

## Debugging

`Debug::dump()` formats with `print_r`, escapes for web SAPIs inside `<pre>` tags, and prints plain text on CLI. The `$exit` flag terminates only in development (`APP_ENV=development`) - elsewhere it is ignored so production can never truncate the response.

```php
use App\Support\Debug;

Debug::dump($users); // inspect and continue
p($users, true); // same, via alias; exits only in development
```

Remove or gate dump calls before shipping - they are a development aid, not a logging path.

## IDs

`IdGenerator` builds every ID from `random_bytes()`, so values are unpredictable and collision-resistant. There is no `time()`, `mt_rand()`, or `str_shuffle()` on this path.

| Method | Global alias | Format |
| --- | --- | --- |
| `IdGenerator::randomHex(8)` | - | 16 lowercase hex chars from 8 random bytes |
| `IdGenerator::alphaNumeric()` | `generateUniqueAlphaNumericNumber()` | `3F2A-9f4c2a1be07d83c1` (4 upper hex, dash, 16 hex) |
| `IdGenerator::uniqueNumber()` | `generateUniqueNumber()` | `9f4c2a1be07d83c1-4d2e9a0b` (16 hex, dash, 8 hex) |

```php
use App\Support\IdGenerator;

$token = IdGenerator::randomHex(16); // 32 hex chars
$ref = generateUniqueAlphaNumericNumber(); // e.g. 3F2A-9f4c2a1be07d83c1
```
