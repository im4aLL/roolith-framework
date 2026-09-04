# Custom View Engine

Roolith renders views with [roolith/template-engine](https://github.com/im4aLL/roolith-template-engine) by default, see [Views](/views).
You can swap it for any template engine that fits your project, for example [Mustache PHP](https://github.com/bobthecow/mustache.php) or [Twig](https://twig.symfony.com/).

## Installation

Install the engine you want with Composer.

```bash
composer require mustache/mustache
```

```bash
composer require "twig/twig:^3.0"
```

The base controller owns the template engine, so the swap happens in `app/Controllers/Controller.php`.
The default engine is resolved by `App\Core\TemplateEngineFactory`.
`view()` delegates to the engine `compile()` method.

## Using Mustache

### Updating the Controller

```php
<?php
namespace App\Controllers;

use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

class Controller
{
    private Mustache_Engine $templateEngine;

    public function __construct()
    {
        $this->templateEngine = new Mustache_Engine([
            'loader' => new Mustache_Loader_FilesystemLoader(APP_VIEW_ROOT),
        ]);
    }

    /**
     * Render a view
     *
     * @param $filename
     * @param array $data
     * @return string
     */
    public function view($filename, array $data = []): string
    {
        return $this->templateEngine->render($filename, $data);
    }
}
```

### Creating Templates

Mustache reads templates from the same `views` folder but expects `.mustache` files.
A template named `home` maps to `views/home.mustache`.

```hbs
<h1>{{title}}</h1>

<p>{{content}}</p>
```

### Rendering in a Controller

Controllers stay unchanged: call `$this->view($filename, $data)`.

```php
<?php
namespace App\Controllers;

class WelcomeController extends Controller
{
    public function index()
    {
        return $this->view('home', [
            'content' => 'Welcome to Roolith framework!',
            'title' => 'Roolith Framework',
        ]);
    }
}
```

### Escaping

Text between double mustache tags is HTML-escaped by default.
Text between triple mustache tags is printed raw.

```hbs
<p>{{title}}</p>
<p>{{{content}}}</p>
```

### Recreating the Base URL Helper

The default engine provides `$this->url()` inside templates.
With Mustache, add a helper in the constructor instead.

```php
$this->templateEngine = new Mustache_Engine([
    'loader' => new Mustache_Loader_FilesystemLoader(APP_VIEW_ROOT),
    'helpers' => [
        'url' => function ($text) {
            return rtrim(Config::get('baseUrl'), '/') . '/' . ltrim($text, '/');
        },
    ],
]);
```

```hbs
<link rel="stylesheet" href="{{#url}}assets/app.css{{/url}}">
```

## Using Twig

### Updating the Controller

Twig compiles templates down to plain PHP and auto-escapes output.

```php
<?php
namespace App\Controllers;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Controller
{
    private Environment $templateEngine;

    public function __construct()
    {
        $loader = new FilesystemLoader(APP_VIEW_ROOT);
        $this->templateEngine = new Environment($loader, [
            'cache' => false,
        ]);
    }

    /**
     * Render a view
     *
     * @param $filename
     * @param array $data
     * @return string
     */
    public function view($filename, array $data = []): string
    {
        return $this->templateEngine->render($filename . '.twig', $data);
    }
}
```

### Creating Templates

A template named `home` maps to `views/home.twig`.

```twig
<h1>{{ title }}</h1>

<p>{{ content }}</p>
```

### Rendering in a Controller

Controllers stay unchanged: call `$this->view($filename, $data)`.

```php
<?php
namespace App\Controllers;

class WelcomeController extends Controller
{
    public function index()
    {
        return $this->view('home', [
            'content' => 'Welcome to Roolith framework!',
            'title' => 'Roolith Framework',
        ]);
    }
}
```

### Escaping

Twig auto-escapes by default.
Use the `raw` filter to print raw HTML.

```twig
<p>{{ title }}</p>
<p>{{ content|raw }}</p>
```

### Recreating the Base URL Helper

Add a function to the environment in the constructor.

```php
$this->templateEngine->addFunction(new \Twig\TwigFunction('url', function ($text) {
    return rtrim(Config::get('baseUrl'), '/') . '/' . ltrim($text, '/');
}));
```

```twig
<link rel="stylesheet" href="{{ url('assets/app.css') }}">
```

## Notes

- Mustache includes partials and layouts with the partial tag, which resolves to `views/partials/header.mustache` for a template named `partials/header`.
- Twig includes partials with the include tag and composes layouts with the extend tag and blocks.
- Neither engine has a `baseUrl` or `escape` method from the default engine; recreate them as helpers or functions if your templates rely on them.
- Mustache caches templates in memory; leave the cache option unset while iterating in development.
- Twig caches compiled templates on disk; set the cache option to a writable directory in production.
- Twig 3.x requires PHP 8.1 or newer.
- Apply the same swap in `App\Core\TemplateEngineFactory` if other code resolves the engine through the factory.