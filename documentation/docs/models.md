# Models

Model files live in `app/Models` under the `App\Models` namespace. One model maps to one table.

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

`$table` is required. `$fillable` lists the columns allowed for mass assignment (empty means all columns pass through). `$casts` converts values to native types when reading (`int`, `float`, `bool`, `string`, `datetime`). The primary column defaults to `id`; override it when your table differs.

```php
protected string $primaryColumn = 'uuid';
```

Generate a stub any time (see [Generator](/generator)).

```bash
php roolith generate model Product
```

## Saving input safely

Filter to `$fillable`, validate, then write inside a transaction so multi-step writes stay atomic.

```php
$model = new User();
$data = $model->filterFillable(Request::all());
$errors = $model->validate($data);

if ($errors !== []) {
    return $errors; // field => failing rules
}

User::transaction(static function ($db) use ($data): void {
    $db->table('users')->insert($data);
});
```

An empty `$fillable` lets everything through, so set it explicitly on models that accept user input; `filterFillable()` drops anything else (for example `is_admin`). Add rules by overriding `validationRules()`; returning nothing means no validation.

```php
protected function validationRules(): array
{
    return [
        'name' => Rules::set()->isRequired()->minLength(2),
        'email' => Rules::set()->isRequired()->isEmail(),
    ];
}
```

## Reading records

```php
$users = User::all(); // all rows, casts applied
$user = User::orm()->find($id); // one row by primary key, casts NOT applied
```

Casts apply only to `all()` (and its instance form `getAll()`). Queries through `orm()` return raw driver rows, so cast one manually when you need native types.

```php
$model = new User();
$row = $model->castRow(User::orm()->find($id));
```

`null` values stay `null` and unknown cast names leave the value untouched.

## Querying through the model

`User::orm()` returns the query builder scoped to the model's table. Everything from [Database](/database) works on it.

```php
User::orm()->where('name', '%Hadi%', 'LIKE')->get();
User::orm()->insert(['name' => 'John doe', 'email' => 'john@email.com']);
User::orm()->update(['name' => 'Habib Hadi'], ['id' => 1]);
User::orm()->delete(['id' => 4]);
```

`User::tableName()` returns the table name. `User::raw()` returns the raw connection for other tables or raw SQL (see [Database](/database)).

```php
$users = User::raw()->query("SELECT * FROM users")->get();
```

A model with an empty `$table` throws a `RuntimeException` on first use, so a typo fails fast instead of producing invalid SQL.

## Eager loading with LazyLoad

Looping one query per row is the N+1 problem: 1 query for the parents plus N queries for the relations. `App\Core\LazyLoad` fixes it with `with(ModelClass, foreignKey, localKey)` - one extra query per relation, then a keyed map attaches matches in O(n+m).

```php
use App\Core\LazyLoad;

// Before: 1 query for posts + N queries for authors (N+1 total).
foreach ($posts as $post) {
    $post->author = User::orm()->find($post->user_id);
}

// After: 2 queries total (posts + one WHERE id IN (...) for authors).
$posts = (new LazyLoad($posts))->with(User::class, 'user_id')->get();
echo $posts[0]->user->name; // relation key is the snake_case model name
```

The third argument is the related key and defaults to `id`: `with(Comment::class, 'post_id', 'id')`. The relation key is the snake_case model short name (`User` becomes `user`), a single match attaches as an object and several attach as an array, and missing or unknown models leave `null` instead of throwing. Related IDs are normalized to string, so int `1` matches string `"1"`. See the [Lazy Load Models](/lazy-load-models) recipe for the full paginated example, reverse has-many usage, and loading the same model twice.

## Database connection

The connection comes from the `database` key in `config/config.php`. Set it to `null` (or leave `DB_HOST`/`DB_NAME` empty) to run without a database. See [Configuration](/configuration).
