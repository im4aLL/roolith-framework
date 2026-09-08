# CSRF with Forms

State-changing routes (`POST`, `PUT`, `PATCH`, `DELETE`) can require a per-session token. The browser sends it back with each write, and `App\Middlewares\CsrfMiddleware` rejects mismatches with `403` before your controller runs. Reading routes (`GET`, `HEAD`, `OPTIONS`) pass through untouched.

Tokens live in the session under `_csrf_token`. `Csrf::token()` creates one 64-char hex value per session and reuses it until rotation. Validation uses `hash_equals()`, so missing or mismatched tokens fail closed.

## Protecting a form

Attach the middleware to the write route, then add the hidden field to the form.

```php
use App\Middlewares\CsrfMiddleware;

$router->get('/contact', [ContactController::class, 'create']);
$router->post('/contact', [ContactController::class, 'store'])->middleware(CsrfMiddleware::class);
```

```php
<!-- views/contact-create.php -->
<form method="POST" action="/contact">
    <?= csrf_field() ?>
    <input type="text" name="name">
    <button type="submit">Send</button>
</form>
```

`csrf_field()` renders `<input type="hidden" name="_csrf" value="...">` for the current session. `Csrf::tokenFromRequest()` reads that `_csrf` field first, so plain HTML forms need nothing else.

A complete controller keeps the CSRF concern on the route and focuses on input:

```php
<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Rules;
use App\Core\Validator;

class ContactController extends Controller
{
    public function create(): string
    {
        return $this->view('contact-create', []);
    }

    public function store(): mixed
    {
        // CsrfMiddleware already verified the token before this runs.
        $validator = new Validator();
        $validator->check(Request::all(), [
            'name' => Rules::set()->isRequired()->minLength(2),
        ]);

        if ($validator->fails()) {
            return $this->view('contact-create', ['errors' => $validator->errors()]);
        }

        return redirect('/thanks');
    }
}
```

## Sending the token with fetch

Fetch or XHR clients send the `X-CSRF-TOKEN` header instead of a hidden field (`X-XSRF-TOKEN` is also accepted). Expose the token to JavaScript once, for example with a meta tag in your layout:

```php
<meta name="csrf-token" content="<?= csrf_token() ?>">
```

```js
const token = document.querySelector('meta[name="csrf-token"]').content;

fetch('/contact', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': token },
    body: new FormData(form),
});
```

## Rotating the token on login

Rotate on privilege changes such as login so a token issued before authentication cannot be reused after it.

```php
use App\Core\Csrf;

if ($loginOk) {
    Csrf::rotate();
    return redirect('/dashboard');
}
```

## What failure looks like

A missing or wrong token stops with `403 Invalid CSRF token.` and your controller never runs. Successful checks add an `X-CSRF-Validated: 1` marker header to the response. When a form suddenly 403s, check these in order:

- The route has `->middleware(CsrfMiddleware::class)` and the form posts to the same URL and method.
- The form contains `<?= csrf_field() ?>` inside the `<form>` tags (fetch clients send the header instead).
- The session persists across requests (cookies enabled, session storage writable).

## Notes

- Token helpers are `csrf_token()` (current token string) and `csrf_field()` (hidden input HTML), with `App\Core\Csrf` behind both. See [Security](/security#csrf-protection) for the token lifecycle.
- Attaching one or several middleware to routes and groups is covered in [Middleware](/middleware).
- Keep form input raw and escape at render; see [Sanitizing Input](/sanitizing-input) and [Validation](/validation).
