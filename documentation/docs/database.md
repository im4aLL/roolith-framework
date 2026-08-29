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

## Raw Query

```php
// Get all users
$users = $db->query("SELECT * FROM users")->get();
print_r($users);

// Get total record of users table
$total = $db->query("SELECT id FROM users")->count();
```

## Select

```php
$db->table('users')->select([
    'field' => ['name', 'email'],
    'condition' => 'WHERE id > 0',
    'limit' => '0, 10',
    'orderBy' => 'name',
    'groupBy' => 'name',
])->get();
```

Get usernames only.

```php
$usernames = $db->table('users')->select([
    'field' => 'name',
])->get();
```

Search with the `LIKE` operator.

```php
$db->table('users')->where('name', '%Hadi%', 'LIKE')->get();
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

or with a raw condition.

```php
$result = $db->table('users')->update(
    ['name' => 'Habib Hadi', 'email' => 'john@email.com'],
    'id = 1'
);
```

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

## Debug Mode

Once debug mode is active, the executed query string is shown.

```php
$db->debugMode()->table('users')->find(1);
```
