# Validation

The `App\Core\Validator` class validates data against fluent rules built with `Rules::set()`.
Output is escaped at render with `escape()` or `App\Support\Html::escape()`; `Request::input()` stays raw so `O'Reilly` keeps its form.

## Validating Data

```php
use App\Core\Validator;
use App\Core\Rules;

$validator = new Validator();
$validator->check(
    [
        'name' => 'john',
        'email' => 'me@habibhadi.com',
        'company' => '',
        'age' => 18,
        'url' => 'something!',
    ],
    [
        'name' => Rules::set()->isRequired()->minLength(10)->maxLength(20),
        'email' => Rules::set()->isEmail()->isRequired(),
        'company' => Rules::set()->isRequiredIf('age:greater_than:10'),
        'url' => Rules::set()->isUrl(),
        'age' => Rules::set()->isNumeric(),
    ]
);

if ($validator->success()) {
    // do something!
}
```

## Available Rules

- `isRequired()`
- `isEmail()`
- `isUrl()`
- `isNumeric()`
- `isArray()`
- `minLength(10)`
- `maxLength(20)`
- `isRequiredIf('age:greater_than:10')`
- `notExists(\App\Models\User::class)`

`notExists()` checks the value against a model table, for example to make sure an email is not already taken.

## Sanitize

Narrow helpers for slug plus email lookups only. General input stays raw; escape at render with `escape()` or `Html::escape()`.

```php
use App\Core\Sanitize;

Sanitize::param('hello-world_1'); // slug-safe: letters, digits, dash, dot, underscore
Sanitize::email('user@example.com'); // email-safe lookup value
escape($raw); // render-time escaping for views
```

`Sanitize::any()`, `Sanitize::string()`, and `Sanitize::items()` are legacy for backward compatibility only. They strip tags and encode entities, destroying legitimate data like `O'Reilly` and risking double escaping when combined with view escaping. Do not call them on general input.
