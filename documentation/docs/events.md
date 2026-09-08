# Events

Events let one part of your code react when something happens elsewhere, such as sending a welcome email after signup. The framework does not fire events itself; you define your own names and listeners.

Register listeners once at boot (for example in `routes.php` or a service provider), then trigger where the action happens.

## Basic usage

```php
use Roolith\Event\Event;

// register once at boot:
Event::listen('user.created', function (array $user) {
    return 'welcome:' . $user['email'];
});

// where the action happens:
$results = Event::trigger('user.created', [['email' => 'a@b.com']]); // ['welcome:a@b.com']
```

## Passing data

No argument calls listeners with zero args, a single value is passed as one arg, and an array is spread into listener params.

```php
use Roolith\Event\Event;

Event::listen('logout', function ($who) {
    echo 'Goodbye ' . $who;
});

Event::trigger('logout', 'a@b.com');

Event::listen('updated', function ($field, $by) {
    echo $field . ' changed by ' . $by;
});

Event::trigger('updated', ['email', 'admin']);
```

Listener params must match what you trigger with, otherwise PHP throws an `ArgumentCountError`.

## Trigger results

`trigger()` returns listener results in order, or `[]` when nothing matched. It does not throw for missing listeners.

```php
$results = Event::trigger('login'); // e.g. ['ok', null]

if ($results === []) {
    // no listener matched
}

if (Event::has('login')) {
    Event::trigger('login');
}
```

Returning `false` from a listener stops the rest from running:

```php
Event::listen('login', fn () => false); // the next listener never runs
Event::listen('login', fn () => 'never');
```

## Group listeners

Register one listener for several events, or catch a group with a wildcard. Wildcards match one level: `event.*` matches `event.login` but not `event.login.extra`.

```php
use Roolith\Event\Event;

Event::listeners(['login', 'user.login'], function () {
    // runs for either event
});

Event::listen('event.*', function ($which) {
    echo 'Saw ' . $which;
});

Event::trigger('event.login', 'login');
```

## Remove listeners

```php
use Roolith\Event\Event;

Event::unregister('updated'); // remove all listeners for one event
Event::unregister(['a', 'b']); // remove several at once

$callback = function () {};
Event::listen('updated', $callback);
Event::unregister('updated', $callback); // remove just that one
```

## Event names

Names are letters, digits, and underscores joined by dots, with an optional trailing `.*` (for example `login`, `user.login`, `event.*`). Anything else throws an `InvalidArgumentException`:

```php
use Roolith\Event\Event;
use Roolith\Event\Exceptions\InvalidArgumentException;

try {
    Event::trigger('invalid..name');
} catch (InvalidArgumentException $e) {
    // handle validation error
}
```

## Isolated dispatcher

Use a `Dispatcher` instance when you want dependency injection or isolated state in tests. Instances do not share listeners with the `Event` facade.

```php
use Roolith\Event\Dispatcher;
use Roolith\Event\Event;

$events = new Dispatcher();
$events->listen('login', fn () => 'ok');
$events->trigger('login'); // ['ok']

// reset shared facade state in tests:
Event::reset();
Event::setSharedDispatcher(new Dispatcher());
```

## Worked example

See `app/Examples/CacheAndEventExamples.php` (`userCreated`, `registerUserCreatedListeners`) for the register-once-at-boot shape. Use events only for decoupled side effects - welcome emails, order-placed hooks - not for the main request flow.
