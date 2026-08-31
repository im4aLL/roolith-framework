# Extending a Model

A model can be more than a thin wrapper over a table.
By adding static helper methods you can encapsulate application logic - like authentication and business rules - right in the model.
Controllers then read like plain English.

This page builds a full-featured `User` model step by step.
It assumes a `users` table with `email`, `role`, `last_logged_in` and `verification_code` columns, like the one created in the [migration example](/migration#complete-example).

## The Full Model

```php
<?php
namespace App\Models;

use App\Core\Storage;
use Carbon\Carbon;

class User extends Model
{
    protected string $table = 'users';

    /**
     * Get the logged-in user
     *
     * @return object|false
     */
    public static function current(): object|false
    {
        $loggedInEmail = Storage::getSession(AUTH_STORAGE_NAME);

        if (!$loggedInEmail) {
            return false;
        }

        return self::orm()->where('email', $loggedInEmail, '=')->first();
    }

    /**
     * Get the logged-in user ID
     *
     * @return int|false
     */
    public static function getUserId(): int|false
    {
        $user = self::current();

        return $user ? $user->id : false;
    }

    /**
     * Start a user session and record the login
     *
     * @param string $email
     * @return bool
     */
    public static function startSession(string $email): bool
    {
        self::recordLastActivity($email);

        return Storage::setSession(AUTH_STORAGE_NAME, $email);
    }

    /**
     * Destroy the user session
     *
     * @return bool
     */
    public static function destroySession(): bool
    {
        self::recordLastActivity(Storage::getSession(AUTH_STORAGE_NAME));

        return Storage::deleteSession(AUTH_STORAGE_NAME);
    }

    /**
     * Record the last login time and clear the verification code
     *
     * @param string|null $email
     * @return void
     */
    private static function recordLastActivity(string|null $email): void
    {
        if (!$email) {
            return;
        }

        self::orm()->update([
            'last_logged_in' => Carbon::now()->toDateTimeString(),
            'verification_code' => null,
        ], [
            'email' => $email,
        ]);
    }

    /**
     * Check whether a verification code is still unique
     *
     * @param string $code
     * @return bool
     */
    public static function isUniqueVerificationCode(string $code): bool
    {
        $user = self::orm()->where('verification_code', $code)->first();

        return $user ? false : true;
    }
}
```

## What Each Helper Does

### `current()`

Returns the logged-in user as an object, or `false` when no one is logged in.
It reads the logged-in email from the session and fetches the matching row.
See [Storage](/storage) for how sessions work.

```php
$user = User::current();

if ($user) {
    echo $user->name;
}
```

### `getUserId()`

Convenience wrapper around `current()` for when you only need the ID.

```php
if (User::getUserId()) {
    // the user is logged in
}
```

### `startSession()` and `destroySession()`

Centralize login and logout.
`startSession()` records the last activity first, then writes the email to the session.
This pairs well with an `AuthHelper` if you keep helper logic in `app/Misc`.

```php
public function login(Request $request): bool
{
    $user = User::orm()->where('email', $request->email)->first();

    if (!$user || !password_verify($request->password, $user->password)) {
        return false;
    }

    return User::startSession($user->email);
}
```

### `recordLastActivity()`

A private helper that keeps login history up to date.
Because it is private, callers can only go through `startSession()` and `destroySession()`.
Note the [update](/database#update) signature: the second array is the where clause.

```php
User::orm()->update(
    ['last_logged_in' => Carbon::now()->toDateTimeString()],
    ['email' => $email]
);
```

### `isUniqueVerificationCode()`

An example of a business rule: a verification code can only be used once.
It returns `false` when the code is already taken.

```php
if (!User::isUniqueVerificationCode($request->code)) {
    // code already used, reject the request
}
```

## Using the Helpers in a Role Middleware

The helpers compose naturally with [middleware](/middleware).
The `RoleMiddleware` lets a request through only for logged-in managers or admins.

```php
<?php
namespace App\Middlewares;

use App\Misc\AuthHelper;
use App\Models\User;
use Roolith\Route\Middleware;
use Roolith\Route\Request;
use Roolith\Route\Response;

class RoleMiddleware extends Middleware
{
    public function process(Request $request, Response $response): bool
    {
        if (!AuthHelper::isAuthenticated()) {
            return false;
        }

        $currentUser = User::current();

        return $currentUser->role == 'manager' || $currentUser->role == 'admin';
    }
}
```

## Notes

- `orm()` and `raw()` are documented in [Models](/models).
- Keep private helpers prefixed with a clear name (like `record...`) so only the public API of the model is visible to callers.
- Sessions are handled by the [Storage](/storage) class and are started automatically by the front controller.