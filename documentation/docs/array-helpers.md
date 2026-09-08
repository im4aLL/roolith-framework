# Array Helpers

The `Arr` utility class provides handy array methods (`App\Utils\Arr`). `App\Utils\_` remains as a BC alias via `class_alias` but is deprecated since PHP 8.4 which deprecates `_` as a standalone class name, so new code should use `Arr`.

```php
use App\Utils\Arr;

Arr::only(['name' => 'hadi', 'age' => 33], 'name');

Arr::only(['name' => 'hadi', 'age' => 33, 'something' => 'else'], ['name', 'something']);

Arr::drop([1, 2, 3, 4, 5]);
```

## Method List

- `except`
- `chunk`
- `compact`
- `concat`
- `difference`
- `drop`
- `dropRight`
- `dropWhile`
- `filter`
- `remove`
- `findIndex`
- `indexOf`
- `join`
- `last`
- `first`
- `reverse`
- `take`
- `takeRight`
- `uniq`
- `find`
- `each`
- `contains`
- `map`
- `isMultidimensional`
- `resetKeys`
- `order`
- `orderBy`
- `orderByString`
- `random`
- `add`
- `flat`
- `dot`
- `exists`
- `get`
- `has`
- `pluck`
- `prepend`
- `query`
- `set`
- `exceptByValue`
- `compareArrays`
- `isJson`
- `toCamelCase`
- `toTitleCase`
- `countObject`
- `pascalCaseToSnakeCase`
- `arrayToObject`
- `slug`
- `isAssociativeArray`
- `isSameArray`

## Additional Signatures

```php
Arr::exceptByValue(array $array, array $values): array;
Arr::compareArrays(array $oldArray, array $newArray): array; // ['added', 'removed', 'unchanged', 'summary']
Arr::isJson(mixed $string): bool;
Arr::toCamelCase(string $text): string;
Arr::toTitleCase(string $text): string;
Arr::countObject(object $object): int;
Arr::pascalCaseToSnakeCase(string $input): string;
Arr::arrayToObject(array $array): object;
Arr::slug(string $title): string;
Arr::isAssociativeArray(array $array): bool;
Arr::isSameArray(array $array1, array $array2): bool;
```

## Quirks

`map(array $array, callable $callback): array` behaves like `filter()`: it keeps the original value with its key when the callback returns truthy and does not transform values.
`set(array $array, string|int $key, mixed $value): mixed` with dot notation is a read: it walks the nested keys and returns the found value, ignoring `$value`. Only non-dot keys write and return the updated array.
`has(array $array, string $name): bool` returns `(bool) get()`, so falsy values (`0`, `''`, `false`, `null`, `[]`) count as missing even when the key exists.
`first(array $array): mixed` and `last(array $array): mixed` return `null` for an empty array.
