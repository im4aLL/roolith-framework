# Sanitizing Input

Keep input raw, validate by type, and escape at render. `Request::input()` stays raw so stored data like `O'Reilly` keeps its form; markup becomes inert when you escape in the view with `escape()` or `App\Support\Html::escape()`. `App\Core\Sanitize` covers two narrow cases only: URL slugs and email lookups.

## The default flow

Validate the shape with `Validator` plus `Rules`, store the raw value, escape on output.

```php
use App\Core\Request;
use App\Core\Rules;
use App\Core\Validator;

$validator = new Validator();
$validator->check(Request::all(), [
    'name' => Rules::set()->isRequired()->minLength(2)->maxLength(60),
    'email' => Rules::set()->isRequired()->isEmail(),
]);

if ($validator->fails()) {
    return $this->view('contact-create', ['errors' => $validator->errors()]);
}

// Store $data raw; escape when rendering.
$data = Request::all();
```

```php
<!-- views/contact-show.php: escape every untrusted value -->
<p><?= escape($contact->name) ?></p>
<p><?= escape($contact->email) ?></p>
```

`Html::escape()` stringifies scalars and `null`, encodes with `ENT_QUOTES` in UTF-8, and returns `''` for arrays and non-stringable objects instead of leaking structure. See [Support Helpers](/support-helpers#escaping) and [Validation](/validation).

## Slugs with `Sanitize::param()`

Use `param()` when a value becomes part of a URL slug or another restricted lookup. It keeps letters, digits, dash, dot, and underscore, and strips everything else.

```php
use App\Core\Sanitize;

$slug = Sanitize::param(Request::input('slug')); // "hello-world_1" survives, "/../" does not
$post = Post::orm()->where('slug', $slug)->first();
```

For several keys at once, `Sanitize::params()` applies the same filter to each value.

```php
$filters = Sanitize::params(['slug' => 'hello-world_1', 'page' => 'page-2']);
```

## Email lookups with `Sanitize::email()`

Use `email()` for the email lookup value before querying. It keeps the characters valid in an address and strips the rest.

```php
use App\Core\Sanitize;

$email = Sanitize::email(Request::input('email'));
$user = User::orm()->where('email', $email)->first();
```

Still validate with `isEmail()` first when the address must be well formed; sanitize only narrows the lookup string.

## What not to do

`Sanitize::any()`, `Sanitize::string()`, and `Sanitize::items()` are legacy for backward compatibility only. They strip tags and encode entities, which destroys legitimate data like `O'Reilly` and risks double escaping when combined with view escaping. Do not call them on general input.

```php
// Avoid: mangles legitimate input and double-escapes in views.
$name = Sanitize::any(Request::input('name'));

// Prefer: keep raw, validate, escape at render.
$name = Request::input('name');
echo escape($name);
```

The same rule applies to output: do not pre-escape before storage and escape again in the view. Store raw once, escape every time you render.

## Notes

- Validation rules and error shapes live in [Validation](/validation).
- File uploads validate content rather than names; see [File Upload](/file-upload) and [Security](/security#file-upload-hardening).
- Redirects after form writes use Post/Redirect/Get with safe targets; see [Support Helpers](/support-helpers#redirects) and [Security](/security#host-allowlist-and-safe-redirects).
