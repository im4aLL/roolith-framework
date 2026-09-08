# Models

Model files live in `app/Models` under the `App\Models` namespace.
They extend the base `Model` class which wraps the [database](/database) driver.
One model maps to one table via `protected string $table` plus `$primaryColumn` (default `id`).

## Basic Model

Define the table your model works with.

```php
<?php
namespace App\Models;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email'];
    protected array $casts = ['id' => 'int'];
}
```

The primary column defaults to `id`.
Override it if your table uses a different one.

```php
protected string $primaryColumn = 'uuid';
```

## Validated writes

Filter to `$fillable`, validate with `validationRules()`, then write inside a transaction:

```php
$model = new User();
$data = $model->filterFillable(Request::all());
$errors = $model->validate($data);

if ($errors !== []) {
    return $errors;
}

User::transaction(static function ($db) use ($data): void {
    $db->table('users')->insert($data);
});
```

## Reading Records

```php
// Get all users
$users = User::all();

// Get a user by primary key
$user = User::orm()->find($id);
```

## The ORM Instance

`User::orm()` returns a table instance for the `users` table.
Everything from the [database](/database) driver is available on it.

```php
User::orm()->where('name', '%Hadi%', 'LIKE')->get();

User::orm()->insert([
    'name' => 'John doe',
    'email' => 'john@email.com',
]);

User::orm()->update(
    ['name' => 'Habib Hadi'],
    ['id' => 1]
);

User::orm()->delete(['id' => 4]);
```

## The Raw Connection

`User::raw()` returns the database connection itself.
Use it for raw queries or other tables, see [Database](/database) for the full API.

```php
$users = User::raw()->query("SELECT * FROM users")->get();
```

## Database Configuration

The connection is read from the `database` key in `config/config.php`.
Set it to `null` if you do not need a database.

```php
"database" => [
    "host" => "localhost",
    "name" => "roolith_cms",
    "user" => "root",
    "pass" => "",
],
```

## Generating Models

```bash
php roolith generate model Product
```

See [Generator](/generator).
