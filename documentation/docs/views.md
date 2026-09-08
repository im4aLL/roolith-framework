# Views

Views are plain PHP files in the `views/` folder. There is no template syntax to learn.

```text
views/
├── 404.php
├── home.php
└── partials/
    ├── header.php
    └── footer.php
```

## Rendering from a controller

```php
return $this->view('home', [
    'content' => 'home content',
    'title' => 'home page',
]);
```

The name maps to a file, so `'home'` renders `views/home.php` and `'users/show'` renders `views/users/show.php`. A missing file is a server error, so check the name when one appears.

## Layouts with partials

Build pages by injecting shared header and footer partials.

```php
<?php $this->inject('partials/header') ?>

<p><?= $this->escape('content') ?></p>

<?php $this->inject('partials/footer') ?>
```

Give a partial its own data with a second argument. That data stays scoped to the partial and does not leak into the rest of the page.

```php
<?php $this->inject('partials/header', ['title' => 'home page']) ?>
```

## Escaping output

Escape every untrusted value at render time. `$this->escape()` takes the variable name; the global `escape()` takes the value itself.

```php
<title><?= $this->escape('title') ?></title>
<h1><?= escape($user->name) ?></h1>
```

## Links and assets

Link to named routes with `route()` and to files with `url()`. For CSS and JS built by Vite, use `viteCss()` and `viteJs()` so development uses hot reload and production uses hashed files (see [Frontend Workflow](/frontend-workflow)).

```php
<a href="<?= route('welcome.form') ?>">Form</a>
<link rel="stylesheet" href="<?= $this->url('assets/app.css') ?>">
<?= viteCss('source/scss/app.scss', 'assets/build/css/app.css') ?>
<?= viteJs('source/js/app.js', 'assets/build/js/app.js') ?>
```

## Forms

Protected forms need the hidden CSRF token field (see [Middleware](/middleware)).

```php
<form method="POST" action="/form">
    <?= csrf_field() ?>
    <button type="submit">Submit</button>
</form>
```

## 404 page

When no route matches, the framework renders `views/404.php`. Edit that file to customize the not-found page.

## 500 page

Uncaught errors show the Whoops page in development and a generic `Internal Server Error (trace: ...)` message in production. There is no `views/500.php` override yet, so production always returns that generic message with the trace id for log correlation.

## Editor tip

Templates run with `$this` set to the view object, which editors flag as invalid in standalone files. Add this as line 1 in templates that use `$this` for correct autocomplete. It changes nothing at runtime.

```php
<?php /** @var \Roolith\Template\Engine\Interfaces\TemplateContextInterface $this */ ?>
```
