# Lazy Load Models

`App\Core\LazyLoad` attaches related models to a result set with one extra query per relation. Use it when you already have parent rows and want their relations without looping one query per row (the N+1 problem).

It is eager loading in practice: you queue relations with `with()` and materialize them with `get()`. The helper builds one `WHERE localKey IN (...)` query per `with()`, indexes the related rows once in memory (`O(n+m)`), then attaches matches to each parent row.

## Basic usage

```php
use App\Core\LazyLoad;
use App\Models\Post;
use App\Models\User;

$posts = Post::orm()->where('status', 'published')->get();

$posts = (new LazyLoad($posts))
    ->with(User::class, 'author_id')
    ->get();

echo $posts[0]->user->name;
```

`with()` takes a model class, a foreign key on each parent row, and an optional local key on the related table (defaults to `id`). `get()` returns the same rows with relations attached. Both calls are chainable, so one loader can attach several relations.

```php
use App\Models\Comment;

$loader = new LazyLoad($posts);
$loader
    ->with(User::class, 'author_id')
    ->with(Comment::class, 'id', 'post_id')
    ->get();
```

## How `with()` matches rows

`with(Model::class, $foreignKey, $localKey = 'id')` means: for each parent row, read `$row->{$foreignKey}`, find related rows where `$related->{$localKey}` equals that value, and attach them.

Two directions work with the same call shape:

- Belongs-to: the parent holds the foreign key. `with(User::class, 'author_id')` reads `post.author_id` and matches `users.id`.
- Has-many (reverse): the parent key is matched by a child column. `with(Comment::class, 'id', 'post_id')` reads `post.id` and matches `comments.post_id`. One parent can then collect several children.

IDs are normalized to string before matching, so int `1` matches string `"1"`. Rows with `null` or `''` foreign values are skipped and keep `null` for that relation.

## Where the relation lands

The property name is the snake_case short name of the model: `User` becomes `user`, `Comment` becomes `comment`.

- One match attaches as an object: `$post->user->name`.
- Several matches attach as an array: `$post->comment[0]->body`.
- No match (or missing foreign value) leaves the property set to `null` instead of throwing.

```php
// User::class -> $row->user
// Comment::class -> $row->comment
foreach ($posts as $post) {
    $authorName = $post->user?->name ?? 'Anonymous';

    $comments = $post->comment;

    if ($comments !== null && !is_array($comments)) {
        $comments = [$comments];
    }
}
```

Normalize the has-many side to an array when a parent can have one or many children, as above. That keeps views simple because they can always `foreach` over `$comments`.

## Full example: paginated list with relations

Paginate the parents first, lazy load the relations onto the page slice only, then shape the data for the view.

```php
use App\Core\LazyLoad;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

$pagination = Post::raw()
    ->query('SELECT a.* FROM posts AS a ORDER BY a.id DESC')
    ->paginate([
        'perPage' => PAGINATION_PER_PAGE,
        'total' => $total,
        'pageUrl' => route('posts'),
    ]);

$paginationData = $pagination->getDetails();

$lazyLoad = new LazyLoad($paginationData->data);
$lazyLoad
    ->with(User::class, 'author_id')
    ->with(Comment::class, 'id', 'post_id')
    ->get();

foreach ($paginationData->data as $row) {
    if (!is_array($row->comment)) {
        $row->comment = [$row->comment];
    }

    $row->comment_bodies = [];

    foreach ($row->comment as $comment) {
        $row->comment_bodies[] = $comment->body;
    }
}
```

Only the current page is loaded, so the extra `WHERE ... IN (...)` queries stay small. In the view, each row already carries its relations:

```php
<?php foreach ($paginatedData->data as $row): ?>
    <td><?= implode(', ', $row->comment_bodies) ?></td>
    <td><?= $row->user?->name ?? 'Anonymous' ?></td>
<?php endforeach; ?>
```

## Always attaching the full array with `add_array`

By default a single match attaches as an object. Pass `['add_array' => true]` when you also want the full match list under `{key}_array` regardless of cardinality.

```php
$rows = (new LazyLoad($posts, ['add_array' => true]))
    ->with(User::class, 'author_id')
    ->get();

// $post->user is the object (or null), $post->user_array is always the match array (or null)
```

## Loading the same model twice

The relation key comes from the model name only, so two `with()` calls for the same model share one key and the second overwrites the first. Do not chain two `User` relations on one loader and expect both to survive.

When you need two users on one row (for example `author_id` and `editor_id`), load in two passes and rename between them:

```php
$rows = (new LazyLoad($posts))->with(User::class, 'author_id')->get();

foreach ($rows as $row) {
    $row->author = $row->user;
}

$rows = (new LazyLoad($rows))->with(User::class, 'editor_id')->get();

foreach ($rows as $row) {
    $row->editor = $row->user;
    unset($row->user);
}
```

The second loader only touches `$row->user`, so `$row->author` survives. Each pass still costs one query.

## Safety rules

- Empty input or no `with()` calls returns the input unchanged with no warnings.
- Unknown model classes and non-model classes fail closed per `with()`: rows keep `null` for that key and no exception is thrown. The model must exist and extend `App\Models\Model`.
- ORM failures for one relation leave that key as `null` without breaking the other relations.
- `Traversable` inputs (including generators) are materialized once, so the first element is preserved and the result can be iterated repeatedly.
- `get()` mutates the row objects in place and returns the same iterable shape it received.

## Notes

- The query side is documented in [Models](/models) and [Database](/database). `LazyLoad` calls `Model::orm()->where($localKey, 'IN', $ids)->get()` internally, one query per `with()`.
- A short version of this pattern also lives in [Models](/models#eager-loading-with-lazyload).
- Casts apply only to `all()` and `getAll()`; rows attached by `LazyLoad` are raw driver rows, so cast manually with `castRow()` when you need native types (see [Models](/models#reading-records)).
