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

```php
return $this->view('home', [
    'content' => 'home content',
    'title' => 'home page',
]);
```

## Injecting Partials

Use `$this->inject()` to include another view.

```php
<?php $this->inject('partials/header') ?>

    <p><?= $this->escape('content') ?></p>

<?php $this->inject('partials/footer') ?>
```

## Printing Data

Use the `escape` method to escape a variable or print it plainly.

```php
<title><?= $this->escape('title') ?></title>
```

or

```php
<title><?= $title ?></title>
```

## URLs to Assets

The `url` method prefixes the base URL from the config.

```php
<link rel="stylesheet" href="<?= $this->url('assets/app.css') ?>">
<script src="<?= $this->url('assets/app.js') ?>"></script>
```

## Nested Views

A dot path compiles to a folder plus file.

```php
$view->compile('nested.template', $data);
```

This looks for the `nested` folder and the `template.php` file inside it.

## Error Page

If no route matches, the framework renders `views/404.php` by default.
