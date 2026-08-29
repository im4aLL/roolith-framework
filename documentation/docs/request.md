# Request

The `App\Core\Request` class gives you access to the current request.

## Input

```php
Request::input('page');
Request::has('page');
Request::all();
Request::only('page');
Request::only(['page', 'other_param']);
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

Uploaded files expose a small helper API.

```php
$file = Request::file('photo');

if ($file->isValid()) {
    $file->upload($destination);
}
```
