# Views

View files live in the `views` folder.
The framework uses [roolith/template-engine](https://github.com/im4aLL/roolith-template-engine) under the hood.
There is no new template syntax, just plain PHP with output buffering and no `eval`.

## File Structure

```text
views/
├── 404.php
├── home.php
└── partials/
    ├── header.php
    └── footer.php
```

## Rendering

From a controller, call `$this->view($filename, $data)`.
The filename maps directly to a file in the `views` folder.
`view()` delegates to the engine `compile($filename, $data)`.

```php
return $this->view('home', [
    'content' => 'home content',
    'title' => 'home page',
]);
```

Missing templates throw `Roolith\Template\Engine\Exceptions\Exception` with a `resolved:` path in the message.
Invalid view names throw `Roolith\Template\Engine\Exceptions\InvalidArgumentException`, which extends SPL `\InvalidArgumentException` so it can be caught as either.
The base controller catches both and prints the message.

## Injecting Partials

Use `$this->inject()` to include another view.

```php
<?php $this->inject('partials/header') ?>

    <p><?= $this->escape('content') ?></p>

<?php $this->inject('partials/footer') ?>
```

Pass scoped data to a partial with a second argument.
Injected data does not leak into sibling or parent templates.
Consecutive `compile()` calls also start clean.

```php
<?php $this->inject('partials/header', ['title' => 'home page']) ?>
```

## Printing Data

Use the `escape` method to escape a named variable or print it plainly.

```php
<title><?= $this->escape('title') ?></title>
```

or

```php
<title><?= $title ?></title>
```

`escape($var)` throws `Exceptions\Exception` when the variable is not defined.
Use `e($value)` to escape a raw value instead of a named variable.
`null` and `false` render as `''`.
Scalars and stringable objects are supported.
Arrays and non-stringable objects throw `Exceptions\InvalidArgumentException`.

```php
<p><?= $this->e($content) ?></p>
```

## URLs to Assets

The `url` method prefixes the base URL from the config.
The controller sets it from `baseUrl` on every request.
`setBaseUrl(false)` disables it, in which case `url()` returns a root-relative path.
`getBaseUrl()` returns the current base URL or `false`.

```php
<link rel="stylesheet" href="<?= $this->url('assets/app.css') ?>">
<script src="<?= $this->url('assets/app.js') ?>"></script>
```

## Nested Views

A slash path compiles to a folder plus file.

```php
return $this->view('nested/template', $data);
```

This looks for the `nested` folder and the `template.php` file inside it.
The dot form `nested.template` resolves to the same file for backward compatibility, but it is deprecated and triggers `E_USER_DEPRECATED`.
New code should use `/`.

## Template Data

The engine exposes its current variables for advanced use.

```php
$view->setTemplateData(['a' => 1]);
$view->addTemplateData(['b' => 2]);
$view->getTemplateData();
$view->resetTemplateData();
```

`compile($name, $data)` replaces template data for that render and resets it afterwards.

## Path Resolver

`Roolith\Template\Engine\TemplatePathResolver` validates names and resolves them to file paths.
`View` delegates to it, and it is the source of truth for view folder and file extension.
The default extension is `php` and a leading dot is optional when changing it.

```php
$resolver = new \Roolith\Template\Engine\TemplatePathResolver(APP_VIEW_ROOT, 'php');
$resolver->setFileExtension('phtml');
$view = new \Roolith\Template\Engine\View(null, $resolver);
```

`App\Core\TemplateEngineFactory` builds the shared `View` from `APP_VIEW_ROOT`.
Use `getPathResolver()->getViewFolder()` and `getPathResolver()->getFileExtension()` as the source of truth.

## Validation and Security

View names must match `#^[A-Za-z0-9_-]+(?:[./][A-Za-z0-9_-]+)*$#`.
Empty names, `..` segments, absolute paths, backslashes, and stream wrappers (`php://`, `file://`, `data://`, anything containing `:`) are rejected with `InvalidArgumentException`.
Existing files are checked with `realpath` so symlinked paths escaping the view folder are rejected.

## Editor Support

Templates run in `View` scope, so `$this` is a `View` at runtime.
Editors analyze template files standalone and flag `$this` as invalid.
Add this as line 1 in every template that uses `$this`.

```php
<?php /** @var \Roolith\Template\Engine\Interfaces\TemplateContextInterface $this */ ?>
```

It changes no runtime behavior and shows only template helpers in autocomplete: `inject()`, `escape()`, `e()` and `url()`.

## Error Page

If no route matches, the framework renders `views/404.php` by default.
