# Events

The framework ships with [roolith/event](https://github.com/im4aLL/roolith-event), a simple PHP event listener.

## Usage

```php
use Roolith\Event\Event;

Event::listen('login', function () {
    echo 'Event user login fired! <br>';
});

Event::trigger('login');
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

## With Param

```php
Event::listen('logout', function ($param) {
    echo 'Event ' . $param . ' logout fired! <br>';
});

Event::trigger('logout', 'user');
```

## With Param Array

```php
Event::listen('updated', function ($param1, $param2) {
    echo 'Event (' . $param1 . ', ' . $param2 . ') updated fired! <br>';
});

Event::trigger('updated', ['param1', 'param2']);
```

## Unregister an Event

```php
Event::unregister('updated');
```

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
