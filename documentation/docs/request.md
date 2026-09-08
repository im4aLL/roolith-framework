# Request

The `App\Core\Request` class gives you access to the current request. `input()` returns raw values (no stripping); validate by type with `Validator` plus `Rules`, then escape at render with `escape()` or `$this->escape()`. `unsafeInput()` is a BC alias of `input()`.

## Input

```php
Request::input('page');
Request::has('page');
Request::all();
Request::only('page');
Request::only(['page', 'other_param']);
Request::except('password');
```

`only()` plus `except()` accept a string or array via `_::only` plus `_::except`. JSON bodies (`{"page":1}`) parse via `php://input` and read the same way. `all()` returns raw inputs plus `_files` on POST; pass `['sanitize' => true]` only for narrow slug or email paths. Use `Sanitize::param()` or `Sanitize::email()` only for those narrow cases.

```php
$data = Request::all();
$validator = new \App\Core\Validator();
$validator->check($data, ['email' => \App\Core\Rules::set()->isRequired()->isEmail()]);

if ($validator->fails()) {
    $errors = $validator->errors();
}
```

## URL and Method

```php
Request::url();
Request::fullUrl();
Request::method();
Request::isMethod('POST');
```

## Cookies

```php
Request::cookie('cookie_name');
```

See [Storage](/storage) for setting and deleting cookies.

## Files

```php
Request::file('photo');
Request::hasFile('photo');
```

Uploaded files expose a small helper API. The destination directory must exist and be writable; size plus MIME limits live in `App\Core\File`.

```php
$file = Request::file('photo');

if ($file->isValid()) {
    $file->upload($destination);
}
```

Full form plus files flow:

```php
if (Request::hasFile('photo')) {
    $file = Request::file('photo');

    if ($file->isValid()) {
        $file->upload(APP_ROOT . '/public/uploads');
    }
}
```
