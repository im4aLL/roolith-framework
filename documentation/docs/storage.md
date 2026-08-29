# Storage

Cookies and sessions are handled through the `App\Core\Storage` class.

## Cookies

Set a cookie with an expiration date.
The example uses [Carbon](https://carbon.nesbot.com/) for dates.

```php
Storage::setCookie('name', 'value', Carbon::now()->addMonths());
```

Get a cookie.

```php
Request::cookie('name');
```

Delete a cookie.

```php
Storage::deleteCookie('name');
```

## Sessions

```php
Storage::setSession('name', 'value');
Storage::deleteSession('name');
```

A session is started automatically by the front controller.
