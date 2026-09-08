# Request

The `App\Core\Request` class gives you access to the current request. `input()` returns raw values (no stripping); validate by type with `Validator` plus `Rules`, then escape at render with `escape()` or `$this->escape()`. `unsafeInput()` is a BC alias of `input()`.

## Input

`input($name, $default = null)` checks POST, then GET, then the parsed `php://input` stream, and returns `$default` when the key is missing everywhere. Stream parsing is cached per request. When the Content-Type is `application/json` (matched case-insensitively with any `; charset=...` suffix stripped) the body is JSON-decoded; empty, invalid, or non-array JSON yields `[]`. All other bodies use `parse_str`.

```php
use App\Core\Request;

Request::input('page');
Request::input('page', 1);
Request::has('page');
Request::all();
Request::only('page');
Request::only(['page', 'other_param']);
Request::except('password');
```

`has($name)` uses `array_key_exists` on POST, GET, and stream inputs, so `"0"`, `0`, `false`, `null`, and `[]` count as present; only a missing key returns `false`.

`all()` returns raw inputs split by method: POST merges raw `$_POST` plus files under `_files` only when at least one file is present, GET returns raw `$_GET`, and all other methods return the parsed stream inputs. Values stay raw unless you pass `['sanitize' => true]`, which is only for narrow slug or email paths.

`only()` plus `except()` accept a string or array via `Arr::only` plus `Arr::except`. Use `Sanitize::param()`, `Sanitize::params()`, or `Sanitize::email()` only for those narrow slug or email cases.

```php
use App\Core\Request;
use App\Core\Rules;
use App\Core\Validator;

$data = Request::all();
$validator = new Validator();
$validator->check($data, ['email' => Rules::set()->isRequired()->isEmail()]);

if ($validator->fails()) {
    $errors = $validator->errors();
}
```

## URL and Method

`method()` returns the current HTTP method (defaults to `GET` outside web requests). `isMethod($name)` compares exactly and is case-sensitive, so pass an uppercase name such as `POST`.

```php
use App\Core\Request;

Request::url();
Request::fullUrl();
Request::method();
Request::isMethod('POST');
```

`url()` returns `fullUrl()` without the query string. See [Security](/security) for host allowlist and `fullUrl()` fallback behavior.

`redirect($url)` returns a `302` `Response` without exiting, so return it from the controller. See [Security](/security) for safe redirect rules and [Response](/response) for all redirect helpers plus the JSON envelope.

`ajax()` returns `true` when the `X-Requested-With` header equals `xmlhttprequest` (case-insensitive).

## Cookies

```php
use App\Core\Request;

Request::cookie('cookie_name');
```

See [Storage](/storage) for setting and deleting cookies.

## Files

```php
use App\Core\Request;

Request::file('photo');
Request::hasFile('photo');
```

`file($name, $fileData = null)` returns a `FileInterface` wrapper or `false` when the key is missing, so always check for `false` before calling `isValid()`. `hasFile($name)` only checks `isset($_FILES[$name])` and does not mean the upload is valid; call `isValid()` for extension, MIME, error, and size checks. `allFiles()` returns wrappers keyed by input name and `splitMultipleFiles()` splits `<input type="file" multiple>` entries into single-file chunks. `upload($destination)` creates the destination directory when needed via `FS::makeDirectory()`, so it does not need to exist in advance; size plus MIME limits live in `App\Core\File`.

```php
use App\Core\Request;

$file = Request::file('photo');

if ($file !== false && $file->isValid()) {
    $file->upload($destination);
}
```

Full form plus files flow:

```php
use App\Core\Request;

if (Request::hasFile('photo')) {
    $file = Request::file('photo');

    if ($file !== false && $file->isValid()) {
        $file->upload(APP_ROOT . '/public/uploads');
    }
}
```
