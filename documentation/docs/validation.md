# Validation

The `App\Core\Validator` class validates data against fluent rules built with `Rules::set()`.
Sanitization helpers live in `App\Core\Sanitize`.

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

Sanitize untrusted input before using or storing it.

```php
use App\Core\Sanitize;

Sanitize::param($_GET['param']);
Sanitize::any('untrusted_string<script>alert("a")</script>');
Sanitize::email('something/@bad.com');
Sanitize::string('xss_protect');
```
