# Database

The framework uses [roolith/database](https://github.com/im4aLL/roolith-database) under the hood.
The connection is created from the `database` key in `config/config.php`, so there is no manual connect step inside the framework.

Inside a [Model](/models) you get the connection and table instances through `Model::raw()` and `Model::orm()`.
This page documents the full driver API available on them.

## The Connection

```php
use App\Models\User;

$db = User::raw(); // database connection
$users = User::orm(); // table instance for the users table
```

Set `database` to `null` in `config/config.php` if your application does not need a database.

## Supported Databases

Supports MySQL, PostgreSQL (`pgsql`), and SQLite via PDO.
Any other PDO driver only works when you pass a raw DSN string directly.

| Driver | `type` value | `config/config.php` example |
| --- | --- | --- |
| MySQL | `mysql` (default) | `['type' => 'mysql', 'host' => 'localhost', 'port' => 3306, 'name' => 'dbname', 'user' => 'username', 'pass' => 'password']` |
| PostgreSQL | `pgsql` | `['type' => 'pgsql', 'host' => 'localhost', 'port' => 5432, 'name' => 'dbname', 'user' => 'username', 'pass' => 'password']` |
| SQLite | `sqlite` | `['type' => 'sqlite', 'name' => 'path/to/database.sqlite']` |

The array from `config/config.php` is passed straight to the driver, so `type`, `port`, and SQLite `name` work without any framework change.
Raw PDO DSN strings are also passed through, for example `$db->connect('sqlite::memory:');`.

Default MySQL configuration:

```php
"database" => [
    "host" => "localhost",
    "name" => "roolith_cms",
    "user" => "root",
    "pass" => "",
],
```

PostgreSQL configuration:

```php
"database" => [
    "type" => "pgsql",
    "host" => "localhost",
    "port" => 5432,
    "name" => "roolith_cms",
    "user" => "postgres",
    "pass" => "",
],
```

SQLite configuration:

```php
"database" => [
    "type" => "sqlite",
    "name" => "path/to/database.sqlite",
],
```

## Raw Query

```php
// Get all users
$users = $db->query("SELECT * FROM users")->get();
print_r($users);

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

Note: `condition` is a trusted SQL literal escape hatch.
Never interpolate input into it, pass variables via `bindings`.

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
$db->table('users')->orderBy('id', 'DESC')->limit(10)->offset(5)->get();
```

Get a record by primary key.

```php
$db->table('users')->find(1);
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

Note: array `where` only.
Raw string where is unsupported to prevent injection.

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
    'primaryColumn' => 'id',
    'pageParam' => 'page',
    'total' => $total,
]);
```

A shorter version, `perPage` defaults to 20.

```php
$result = $db->query("SELECT * FROM users")->paginate([
    'perPage' => 5,
    'total' => $total,
]);
```

CLI / test safe pagination without `$_GET` / `$_SERVER`:

```php
use Roolith\Store\Paginate;

$paginate = Paginate::fromRequest(
    ['perPage' => 5, 'total' => $total],
    ['REQUEST_URI' => '/users'],
    ['page' => 2],
);
```

Get the pagination details.

```php
print_r($result->getDetails());
```

```text
{
    "total": 50,
    "perPage": 15,
    "currentPage": 1,
    "lastPage": 4,
    "firstPageUrl": "http://domain.com?page=1",
    "lastPageUrl": "http://domain.com?page=4",
    "nextPageUrl": "http://domain.com?page=2",
    "prevPageUrl": null,
    "path": "http://domain.com",
    "from": 1,
    "to": 15,
    "data": [
        // records
    ]
}
```

## Transactions

`transaction()` commits on success, rolls back and rethrows on failure.
Nesting is unsupported.
Use `inTransaction()` when a helper may run inside or outside a transaction.

```php
$db->transaction(function ($db) {
    $db->table('users')->insert(['name' => 'A', 'email' => 'a@test.com']);
    $db->table('orders')->insert(['user_email' => 'a@test.com', 'total' => 100]);
});
```

Return a value from the callback.

```php
$userId = $db->transaction(function ($db) {
    $result = $db->table('users')->insert(['name' => 'C', 'email' => 'c@test.com']);
    return $result->insertedId();
});
```

Throwing inside the callback triggers a rollback.

```php
try {
    $db->transaction(function ($db) {
        $db->table('users')->insert(['name' => 'B', 'email' => 'b@test.com']);
        throw new RuntimeException('force rollback');
    });
} catch (RuntimeException $e) {
    // row B was not saved
}
```

Manual commit and rollback.

```php
$db->beginTransaction();
try {
    $db->table('users')->insert(['name' => 'D', 'email' => 'd@test.com']);
    $db->table('users')->update(['name' => 'D2'], ['email' => 'd@test.com']);
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}
```

Reusable helper that is safe in both contexts.

```php
function createUser($db, array $data): void
{
    $run = function () use ($db, $data) {
        $db->table('users')->insert($data);
    };

    if ($db->inTransaction()) {
        $run();
        return;
    }

    $db->transaction($run);
}

$db->transaction(function ($db) {
    createUser($db, ['name' => 'E', 'email' => 'e@test.com']);
    createUser($db, ['name' => 'F', 'email' => 'f@test.com']);
});
```

These all throw.

```php
$db->commit(); // throws when no transaction is active
$db->rollBack(); // throws when no transaction is active

$db->beginTransaction();
$db->beginTransaction(); // throws, nesting is unsupported

$db->transaction(function ($db) {
    $db->transaction(function ($db) {}); // throws, nesting is unsupported
});
```

## Debug Mode

Once debug mode is active queries are collected via `getDebugLog()` with no echo output!

```php
$db->debugMode()->table('users')->find(1);
print_r($db->getDebugLog());
```

## Upgrade to Database 2.0

The framework requires `roolith/database: 2.0.0`.
If you are coming from 1.x, these are the breaking changes:

1. `update()` requires array `where` (string where removed).
2. `delete()` return shape drops `debug` key.
3. `pageNumbers()` ellipsis is `'...'` (was `'.'`).
4. `new Paginate` no longer reads `$_GET` / `$_SERVER` (use `Paginate::fromGlobals()` for legacy web or `Paginate::fromRequest()`).
5. New required interface methods (`buildConditionFragment`, transactions, debug log, `orderBy` / `limit` / `offset`).
6. Requires `php >= 8.0`.

Notes:

1. `getDetails()` returns `from=0,to=0` past the last page.
2. `fromRequest()` preserves query params minus `pageParam`.
3. Transactions reject nesting and stray `commit` / `rollBack` (check `inTransaction()`).
