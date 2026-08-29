# Array Helpers

The `_` utility class provides handy array methods.

```php
_::only(['name' => 'hadi', 'age' => 33], 'name');

_::only(['name' => 'hadi', 'age' => 33, 'something' => 'else'], ['name', 'something']);

_::drop([1, 2, 3, 4, 5]);
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
