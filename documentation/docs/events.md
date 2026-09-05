# Events

The framework ships with [roolith/event](https://github.com/im4aLL/roolith-event) `^2.0`, a simple PHP event listener.

`Event` is a static facade over a shared `Roolith\Event\Dispatcher` instance. Use `Dispatcher` directly when you want dependency injection or isolated state in tests.

## Usage

```php
use Roolith\Event\Event;

Event::listen('login', function () {
    echo 'Event user login fired! <br>';
});

Event::trigger('login'); // returns [null] (ordered listener results)
```

## Working Example

```php
<?php
use Roolith\Event\Event;

class User
{
    public function login()
    {
        return true;
    }
}

Event::listen('login', function () {
    echo 'Event user login fired! <br>';
});

$user = new User();

if ($user->login()) {
    Event::trigger('login');
}
```

## Trigger return value

`trigger()` never throws for a missing listener. It returns ordered listener results, `[]` when nothing matched:

```php
$results = Event::trigger('login'); // e.g. ['ok', null]

if ($results === []) {
    // no listener matched
}
```

Returning boolean `false` from a listener stops further propagation (exact listeners run first, then wildcard listeners):

```php
Event::listen('login', fn () => false); // second listener below never runs
Event::listen('login', fn () => 'never');
```

Use introspection instead of try/catch:

```php
if (Event::has('login')) {
    Event::trigger('login');
}

$all = Event::getListeners(); // all listeners keyed by name
$forName = Event::getListeners('event.login'); // exact + wildcard matches
```

## With Param

```php
Event::listen('logout', function ($param) {
    echo 'Event ' . $param . ' logout fired! <br>';
});

Event::trigger('logout', 'user');
```

`null` means zero args, an array is spread into listener params, anything else is passed as one arg. Falsy `0`, `''`, `false` are passed through as one arg. Listener arity must match trigger args, otherwise PHP throws `ArgumentCountError`.

## With Param Array

```php
Event::listen('updated', function ($param1, $param2) {
    echo 'Event (' . $param1 . ', ' . $param2 . ') updated fired! <br>';
});

Event::trigger('updated', ['param1', 'param2']);
```

## Multiple events

Register one shared listener for multiple events. Validation is atomic: failure leaves state untouched. An empty list returns `false` and registers nothing.

```php
Event::listeners(['login', 'user.login'], function () {
    // shared listener
});
```

## Unregister an Event

```php
Event::unregister('updated');
Event::unregister(['a', 'b']); // array form, true only when every name removed something

$callback = function () {};
Event::listen('updated', $callback);
Event::unregister('updated', $callback); // remove one callback, leaves others intact
```

Missing names return `false`. `listen()` always appends with no dedup: registering the same callable twice fires it twice. `unregister($name, $callback)` removes only the first `===` match.

## Wildcard Events

Listen to a group of events with the `*` wildcard.

```php
Event::listen('event.login', function () {
    echo 'Login Wild card fired! <br>';
});

Event::listen('event.logout', function () {
    echo 'Logout Wild card fired! <br>';
});

Event::listen('event.*', function ($param) {
    echo 'Wild card fired! - ' . $param . ' <br>';
});

Event::trigger('event.login', 'login');
Event::trigger('event.logout', 'logout');
```

Wildcard matching is single-level: `event.*` matches `event.login` but not `event.login.extra`.

## Event names

Valid names are segments of letters, digits, underscore joined by single dots, with optional terminal `.*` (e.g. `login`, `user.login`, `event.*`). Invalid names throw `Roolith\Event\Exceptions\InvalidArgumentException`, which extends SPL `\InvalidArgumentException`.

## Exceptions

Invalid names throw `Roolith\Event\Exceptions\InvalidArgumentException` (extends SPL `\InvalidArgumentException`, so standard catches work). Missing listeners do not throw; `trigger()` returns `[]`.

Note: in 2.x `InvalidArgumentException` no longer extends the legacy library `Roolith\Event\Exceptions\Exception` class. That class is kept for BC but no longer thrown, so existing `catch (Roolith\Event\Exceptions\Exception)` blocks around `listen()` / `trigger()` / `unregister()` will not catch validation errors. Catch `Roolith\Event\Exceptions\InvalidArgumentException` (or SPL `\InvalidArgumentException`) instead.

```php
use Roolith\Event\Exceptions\InvalidArgumentException;

try {
    Event::trigger('invalid..name');
} catch (InvalidArgumentException $e) {
    // handle validation error
}
```

Custom validation messages can be set via `setErrorMessage()`. Only the `name` key is currently thrown.

```php
Event::setErrorMessage(['name' => 'custom-name-error']);
```

## Isolated dispatcher (DI / testing)

Prefer instances for DI and test isolation. They do not share state with the `Event` facade.

```php
use Roolith\Event\Dispatcher;

$events = new Dispatcher();
$events->listen('login', fn () => 'ok');
$events->trigger('login'); // ['ok']
```

Isolate facade state in tests:

```php
Event::reset(); // clears shared dispatcher listeners
Event::setSharedDispatcher(new Dispatcher());
```

Listeners are snapshotted before dispatch. `listen()` / `unregister()` inside a listener affect the next `trigger()`, not the one in progress. Nested `trigger()` calls run independently.
