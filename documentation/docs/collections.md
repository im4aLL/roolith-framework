# Collections and Utilities

Three small helpers cover the middle ground between plain arrays and the database: `Collection` for fluent in-memory transforms, `Str` for locale message lookup, and `FS` for standalone filesystem work. For plain-array functions (`only`, `pluck`, `dot`, `get`, and similar) see [Array Helpers](/array-helpers) - this page does not repeat `Arr`.

| Helper | Class | Reach for it when |
| --- | --- | --- |
| `Collection` | `App\Utils\Collection` | You chain map/filter/sort/sum over rows already in memory |
| `Str` | `App\Utils\Str` | You read a translated message for the active locale |
| `FS` | `App\Utils\FS` | You move, create, or delete files outside the request wrapper |

## Collection

Wrap an array with `Collection::make()` and chain. Every transform returns a new instance; terminal calls (`sum`, `avg`, `first`, `last`, `count`, `toArray`, `toJson`) return values.

```php
use App\Utils\Collection;

$total = Collection::make([1, 2, 3, 4, 5])
    ->filter(fn ($x) => $x > 2)
    ->map(fn ($x) => $x * 2)
    ->sum(); // 24
```

Three behaviors deserve a closer look.

### last() is falsy-safe

`last()` returns falsy values (`0`, `"0"`, `""`, `false`, `[]`) as-is and returns `null` only for an empty collection or no match.

```php
Collection::make([0, 1, 2])->last(); // 2
Collection::make([0])->last(); // 0, not null
Collection::make([])->last(); // null
Collection::make([1, 2, 3])->last(fn ($x) => $x > 1); // 3
```

### sum() and avg() take a column or a callable

Pass `null` for raw values, a string column for `pluck()`-then-sum, or any callable for computed values. Strings are checked first, so a column named like a function (for example `"count"`) still resolves as a column. `avg()` returns `null` for an empty collection.

```php
$users = Collection::make([
    ['name' => 'Alice', 'age' => 30],
    ['name' => 'Bob', 'age' => 25],
]);

$users->sum('age'); // 55
$users->sum(fn ($u) => $u['age'] * 2); // 110
$users->avg('age'); // 27.5
Collection::make([])->avg(); // null
```

### pluck() extracts one column

`pluck()` works on arrays and objects and feeds the rest of the chain.

```php
$names = Collection::make($users)->pluck('name')->toArray(); // ['Alice', 'Bob']
$adults = $users->where('age', '>=', 30)->pluck('name')->toArray(); // ['Alice']
```

Other chainable calls you will use often: `filter`, `map`, `where`, `sort` / `sortBy`, `groupBy`, `unique`, `reverse`, `chunk`, `take`, `skip`, `each`, `reduce`, `contains`, `isEmpty` / `isNotEmpty`, `count`.

## Str

`Str` is intentionally small: `Str::getMessage()` reads one dotted key from the active locale catalog (see [Localization](/localization)). It resolves the locale via `Settings::getLang()`, loads the catalog, and applies `Arr::get()` dot notation. Missing keys return `null`.

```php
use App\Utils\Str;

Str::getMessage('errors.required') ?? 'This field is required';
trans('errors.required') ?? 'This field is required'; // same, via Translator
```

For anything else string-shaped (slugs, casing, JSON checks), use `Arr` in [Array Helpers](/array-helpers).

## FS standalone

`FS` is a static filesystem helper with no instance to configure. Prefer it directly when you already have paths; prefer the [request](/request) file wrapper and [File Upload](/file-upload) flow when you handle `$_FILES` validation (extension, MIME, size).

```php
use App\Utils\FS;

FS::upload($file['tmp_name'], $destination); // move an uploaded file
FS::makeDirectory(APP_ROOT . '/uploads/avatars'); // recursive, true when present
```

Deletion refuses dangerous targets: empty paths, filesystem root, and `APP_ROOT` itself throw `InvalidArgumentException`, so a bad variable can never wipe the project or disk root.

```php
FS::removeFile(APP_ROOT . '/uploads/old.txt'); // false when no file exists
FS::removeFilesInDirectory(APP_ROOT . '/uploads/tmp'); // top-level files only
FS::removeDirectory(APP_ROOT . '/uploads/tmp'); // recursive, dotfiles included
```

`protectUploadDirectory()` writes a deny-execution `.htaccess` into a web-accessible upload directory as defense in depth. Existing files are left untouched, and storing outside the docroot is still preferred.

```php
FS::protectUploadDirectory(APP_ROOT . '/uploads'); // true when protected
```
