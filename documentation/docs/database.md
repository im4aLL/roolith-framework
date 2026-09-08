# Database

The framework uses [roolith/database](https://github.com/im4aLL/roolith-database) under the hood. The connection is created from the `database` key in `config/config.php`, so there is no manual connect step.

Inside a [Model](/models), `Model::raw()` gives the connection and `Model::orm()` gives the table instance. This page documents the full driver API available on both.

## The Connection

```php
use App\Models\User;

$db = User::raw(); // database connection
$users = User::orm(); // table instance for the users table
```

Set `database` to `null` to run without a database (see [Configuration](/configuration)).

## Supported Databases

MySQL is the default; PostgreSQL (`pgsql`) and SQLite work via `type` plus `port` keys, and any other PDO driver works with a raw DSN string such as `$db->connect('sqlite::memory:');`.

## Raw Query

```php
// Get all users
$users = $db->query("SELECT * FROM users")->get();

// Get total record of users table
$total = $db->query("SELECT id FROM users")->count();
```

Values are always bound, never interpolated:

```php
$users = $db->query("SELECT * FROM users WHERE email = :email", null, [':email' => $email])->get();
$db->execute("DELETE FROM users WHERE id = :id", [':id' => $id]);
```

## Select

```php
$db->table('users')->select([
    'field' => ['name', 'email'],
    'condition' => 'WHERE id > :min',
    'bindings' => [':min' => 0],
    'limit' => '0, 10',
    'orderBy' => 'name',
    'groupBy' => 'name',
])->get();
```

Note: `condition` is a trusted SQL literal. Never interpolate input into it, pass variables via `bindings`.

Get usernames only.

```php
$usernames = $db->table('users')->select([
    'field' => 'name',
])->get();
```

Search with the `LIKE` operator.

```php
$db->table('users')->where('name', '%Hadi%', 'LIKE')->get();
// new bound style also works
$db->table('users')->where('age', '>', 18)->get();
$db->table('users')->orWhere('role', '=', 'admin')->get();
$db->table('users')->orderBy('id', 'DESC')->limit(10)->offset(5)->get();
```

Get a record by primary key (`false` when missing), or the first row of a result (`false` when empty).

```php
$db->table('users')->find(1);
$db->table('users')->first();
```

Pluck fields from the result.

```php
$db->table('users')->pluck(['name', 'email']);
```

## Insert

```php
$result = $db->table('users')->insert(
    ['name' => 'Brannon Bruen', 'email' => 'bschmeler@pacocha.net']
);

print_r($result->success());
```

Insert only when the supplied email does not exist in the `users` table.

```php
$result = $db->table('users')->insert(
    ['name' => 'John doe', 'email' => 'john@email.com'],
    ['email']
);
```

Response methods:

```php
$result->affectedRow();
$result->insertedId();
$result->isDuplicate();
$result->success();
```

## Update

```php
$result = $db->table('users')->update(
    ['name' => 'Habib Hadi', 'email' => 'john@email.com'],
    ['id' => 1]
);
```

Note: array `where` only. Raw string where is rejected to prevent injection.

Update the username only if nobody else is using it.

```php
$result = $db->table('users')->update(
    ['username' => 'johndoe'],
    ['id' => 4],
    ['username']
);
```

Response methods:

```php
$result->affectedRow();
$result->isDuplicate();
$result->success();
```

## Delete

```php
$result = $db->table('users')->delete(['id' => 4]);
```

Response methods:

```php
$result->affectedRow();
$result->success();
```

## Pagination

```php
$total = $db->query("SELECT id FROM users")->count();
$result = $db->query("SELECT * FROM users")->paginate([
    'perPage' => 5,
    'pageUrl' => 'http://domain.com',
    'total' => $total,
]);

print_r($result->getDetails());
```

`getDetails()` returns `total`, `perPage`, `currentPage`, `lastPage`, page URLs (`firstPageUrl`, `lastPageUrl`, `nextPageUrl`, `prevPageUrl`), `path`, `from`/`to`, and `data`. Past the last page, `from` and `to` are `0`. `pageNumbers()` returns the numbered page links for templates (ellipsis is `'...'`).

In CLI or tests there are no `$_GET` / `$_SERVER` values, so build the paginator explicitly instead.

```php
use Roolith\Store\Paginate;

$paginate = Paginate::fromRequest(
    ['perPage' => 5, 'total' => $total],
    ['REQUEST_URI' => '/users'],
    ['page' => 2],
);
```

`Paginate::fromGlobals()` reads `$_SERVER` and `$_GET` for legacy web code; prefer `fromRequest()` with explicit values.

## Transactions

Write through the factory or model seam so the shared connection commits on success and rolls back plus rethrows on failure. Nesting is unsupported; use `inTransaction()` in helpers that may run inside or outside one.

```php
\App\Core\DatabaseFactory::transaction(static function ($db) {
    $db->table('users')->insert(['name' => 'A', 'email' => 'a@test.com']);
});

\App\Models\User::transaction(static function ($db) {
    $db->table('users')->insert(['name' => 'B', 'email' => 'b@test.com']);
});
```

Return a value from the callback when you need it, and throw to roll back.

```php
$userId = $db->transaction(function ($db) {
    $result = $db->table('users')->insert(['name' => 'C', 'email' => 'c@test.com']);
    return $result->insertedId();
});

try {
    $db->transaction(function ($db) {
        $db->table('users')->insert(['name' => 'B', 'email' => 'b@test.com']);
        throw new RuntimeException('force rollback');
    });
} catch (RuntimeException $e) {
    // row B was not saved
}
```

Manual control is available but stray `commit()` / `rollBack()` outside a transaction throw, as does a second `beginTransaction()`.

```php
$db->beginTransaction();
try {
    $db->table('users')->insert(['name' => 'D', 'email' => 'd@test.com']);
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}
```

## Debug Mode

Queries are logged only in development (`APP_ENV=development`); production stays silent. Force it on for one request and read the log with no echo side effects.

```php
$db->debugMode(true)->table('users')->find(1);
print_r($db->getDebugLog());
$db->clearDebugLog();
```

## Upgrade to Database 2.0

The framework requires `roolith/database: 2.0.0`. Breaking changes:

1. `update()` requires array `where` (string where removed).
2. `delete()` return shape drops the `debug` key.
3. `pageNumbers()` ellipsis is `'...'` (was `'.'`).
4. `new Paginate` no longer reads `$_GET` / `$_SERVER` (use `Paginate::fromGlobals()` for legacy web or `Paginate::fromRequest()`).
5. Requires `php >= 8.2`.
