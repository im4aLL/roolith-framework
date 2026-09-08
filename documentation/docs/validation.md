# Validation

The `App\Core\Validator` class validates data against fluent rules built with `Rules::set()`. Output is escaped at render with `escape()` or `App\Support\Html::escape()`; `Request::input()` stays raw so `O'Reilly` keeps its form.

## Validating Data

```php
use App\Core\Validator;
use App\Core\Rules;

$validator = new Validator();
$validator->check(
    [
        'name' => 'john doe long enough',
        'email' => 'me@habibhadi.com',
        'company' => '',
        'age' => 18,
        'url' => 'https://example.com',
    ],
    [
        'name' => Rules::set()->isRequired()->minLength(10)->maxLength(20),
        'email' => Rules::set()->isRequired()->isEmail(),
        'company' => Rules::set()->isRequiredIf('age:greater_than:10'),
        'url' => Rules::set()->isUrl(),
        'age' => Rules::set()->isNumeric(),
    ]
);

if ($validator->success()) {
    // do something!
}

if ($validator->fails()) {
    $errors = $validator->errors(); // field -> [rule, ...]
}
```

When an input key is missing, only presence rules (`required`, `requiredArray`, `requiredIf`) run; all other rules are skipped so a missing optional email, URL, or numeric field passes. Add `isRequired()` when presence is mandatory. Unknown rule names throw `InvalidArgumentException` instead of passing silently, so typos fail loudly.

`errors()` returns failures grouped by field as `field -> [rule, ...]` (array-valued rules store `[rule, ruleValue]` pairs), while `success()` and `fails()` report the overall result.

## Available Rules

All rules are chained off `Rules::set()`. Each example below is a complete `check()` call you can copy and adapt.

### `isRequired()`

Requires the field to be present and non-empty. Strings are trimmed before checking. `"0"`, `0`, `0.0`, `false`, and `[0]` pass; `null`, `""`, whitespace-only strings, and `[]` fail. A missing key reads as `null` and fails.

```php
use App\Core\Rules;
use App\Core\Validator;

$validator = new Validator();
$validator->check(
    ['name' => 'john'],
    ['name' => Rules::set()->isRequired()]
);

if ($validator->fails()) {
    // 'name' was null, "", whitespace-only, [] or missing
}
```

### `isEmail()`

Passes when the value is a valid email address (checked with `filter_var` plus `FILTER_VALIDATE_EMAIL`). The key may be absent when the field is optional; combine with `isRequired()` to also require presence.

```php
use App\Core\Rules;
use App\Core\Validator;

$validator = new Validator();
$validator->check(
    ['email' => 'me@habibhadi.com'],
    ['email' => Rules::set()->isRequired()->isEmail()]
);
```

### `isUrl()`

Passes when the value is a valid URL (checked with `filter_var` plus `FILTER_VALIDATE_URL`). The key may be absent when the field is optional; combine with `isRequired()` to also require presence.

```php
use App\Core\Rules;
use App\Core\Validator;

$validator = new Validator();
$validator->check(
    ['website' => 'https://example.com'],
    ['website' => Rules::set()->isUrl()]
);
```

### `isNumeric()`

Passes when the value is numeric (checked with `is_numeric`, so `'18'`, `18`, and `18.5` pass). The key may be absent when the field is optional; combine with `isRequired()` to also require presence.

```php
use App\Core\Rules;
use App\Core\Validator;

$validator = new Validator();
$validator->check(
    ['age' => 18],
    ['age' => Rules::set()->isRequired()->isNumeric()]
);
```

### `isArray()`

Passes when the value is an array (`is_array`). Fails for a missing key (reads as `null`), `null`, strings, ints, and objects. Use it when you only need the shape, without requiring sub-fields.

```php
use App\Core\Rules;
use App\Core\Validator;

$validator = new Validator();
$validator->check(
    ['tags' => ['php', 'roolith']],
    ['tags' => Rules::set()->isArray()]
);
```

### `isRequiredArray(['name', 'type'])`

Requires the field to be a non-empty array. With no sub-fields it passes for any non-empty array; with sub-fields each named key must exist and be present with `required()` semantics (`"0"`, `0`, `[0]` pass; `null`, `""`, whitespace-only, `[]` fail), and list values require every element to be present.

```php
use App\Core\Rules;
use App\Core\Validator;

// Any non-empty array passes.
$validator = new Validator();
$validator->check(
    ['data' => ['a', 'b']],
    ['data' => Rules::set()->isRequiredArray()]
);

// Named sub-fields must all be present.
$validator = new Validator();
$validator->check(
    ['meta' => ['name' => 'logo.png', 'type' => 'image/png']],
    ['meta' => Rules::set()->isRequiredArray(['name', 'type'])]
);
```

### `minLength(10)`

Passes when the value has at least the given number of characters. Scalars are cast to string and measured with `mb_strlen` (falling back to `strlen` when mbstring is unavailable), so multibyte strings like `éé` count as 2. Fails on a missing key, `null`, an array, or an object without `__toString`.

```php
use App\Core\Rules;
use App\Core\Validator;

$validator = new Validator();
$validator->check(
    ['name' => 'john doe long enough'],
    ['name' => Rules::set()->isRequired()->minLength(10)]
);
```

### `maxLength(20)`

Passes when the value has at most the given number of characters. Same measuring and guard behavior as `minLength()`: scalars are cast to string and measured with `mb_strlen` (fallback to `strlen`), and a missing key, `null`, an array, or an object without `__toString` fails.

```php
use App\Core\Rules;
use App\Core\Validator;

$validator = new Validator();
$validator->check(
    ['name' => 'john doe'],
    ['name' => Rules::set()->isRequired()->maxLength(20)]
);
```

Combine both for a range:

```php
use App\Core\Rules;

$rules = Rules::set()->isRequired()->minLength(10)->maxLength(20);
```

### `isRequiredIf('age:greater_than:10')`

Makes a field required only when another field meets a condition. The string form is `field:operator:value` split with limit 3 so values may contain colons (for example `time:equals:10:30` keeps `10:30` as the value); the array form is `[field, operator, value]`. Supported operators are `equals` (`==`), `less_than` (`<`), `less_than_equals_to` (`<=`), `greater_than` (`>`), and `greater_than_equals_to` (`>=`). A malformed struct, an unknown operator, or a missing condition field means the field is not required and the check passes; when the condition holds, the field must satisfy `required()`.

```php
use App\Core\Rules;
use App\Core\Validator;

// String form: 'company' is required when 'age' > 10.
$validator = new Validator();
$validator->check(
    ['age' => 18, 'company' => 'Acme'],
    ['company' => Rules::set()->isRequiredIf('age:greater_than:10')]
);

// Array form: same condition as a 3-tuple struct.
$validator = new Validator();
$validator->check(
    ['age' => 18, 'company' => 'Acme'],
    ['company' => Rules::set()->isRequiredIf(['age', 'greater_than', 10])]
);
```

All operators:

```php
use App\Core\Rules;

Rules::set()->isRequiredIf('age:equals:10');
Rules::set()->isRequiredIf('age:less_than:10');
Rules::set()->isRequiredIf('age:less_than_equals_to:10');
Rules::set()->isRequiredIf('age:greater_than:10');
Rules::set()->isRequiredIf('age:greater_than_equals_to:10');
Rules::set()->isRequiredIf('starts_at:equals:10:30'); // colon stays in the value
```

### `notExists(\App\Models\User::class)`

Passes when no row in the given model table matches the field value (for example to make sure an email is not already taken). Accepts a model class name or instance. It throws `InvalidArgumentException` on an invalid model class instead of passing or failing silently.

```php
use App\Core\Rules;
use App\Core\Validator;
use App\Models\User;

$validator = new Validator();
$validator->check(
    ['email' => 'new@example.com'],
    ['email' => Rules::set()->isRequired()->isEmail()->notExists(User::class)]
);
```

### `exists(\App\Models\User::class, 'id')`

Passes when at least one row matches the field value in the given column (`count > 0`). The second argument is the column to match (defaults to `'id'`). It throws `InvalidArgumentException` on a malformed struct or an invalid model class.

```php
use App\Core\Rules;
use App\Core\Validator;
use App\Models\User;

$validator = new Validator();
$validator->check(
    ['author_id' => 7],
    ['author_id' => Rules::set()->isRequired()->isNumeric()->exists(User::class, 'id')]
);

// Match a non-id column, for example an email that must already exist.
$validator = new Validator();
$validator->check(
    ['email' => 'me@habibhadi.com'],
    ['email' => Rules::set()->isRequired()->isEmail()->exists(User::class, 'email')]
);
```

## Sanitize

Narrow helpers for slug plus email lookups only. General input stays raw; escape at render with `escape()` or `Html::escape()`.

```php
use App\Core\Sanitize;

Sanitize::param('hello-world_1'); // slug-safe: letters, digits, dash, dot, underscore
Sanitize::params(['slug' => 'hello-world_1']); // same filter applied to each value
Sanitize::email('user@example.com'); // email-safe lookup value
escape($raw); // render-time escaping for views
```

`Sanitize::any()`, `Sanitize::string()`, and `Sanitize::items()` are legacy for backward compatibility only. They strip tags and encode entities, destroying legitimate data like `O'Reilly` and risking double escaping when combined with view escaping. Do not call them on general input.
