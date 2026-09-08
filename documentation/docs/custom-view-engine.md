# Custom View Engine

Roolith renders views with [roolith/template-engine](https://github.com/im4aLL/roolith-template-engine) by default, see [Views](/views). You only need this page if you want to swap it for something else, for example [Mustache PHP](https://github.com/bobthecow/mustache.php) or [Twig](https://twig.symfony.com/).

> Do you need this? For most apps, no. The default engine is plain PHP with no syntax to learn. Switch only if your team already knows Mustache or Twig, or you share templates with another project.

## How the swap works

Your controllers keep calling the same method:

```php
return $this->view('home', ['title' => 'Roolith Framework']);
```

Only the base controller changes: its constructor builds a different engine, and `view()` forwards to it. The template name maps to a file:

| Call | Default | Mustache | Twig |
| --- | --- | --- | --- |
| `$this->view('home', $data)` | `views/home.php` | `views/home.mustache` | `views/home.twig` |

Pick one engine below and follow its three steps. Controllers outside `app/Controllers/Controller.php` stay unchanged.

## Step 1 - Install one engine

Pick one, not both:

```bash
composer require mustache/mustache
```

```bash
composer require "twig/twig:^3.0"
```

## Step 2 - Point the base controller at it

Edit `app/Controllers/Controller.php`. Replace the default engine setup with one of these.

### Mustache

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

    public function view($filename, array $data = []): string
    {
        return $this->templateEngine->render($filename, $data);
    }
}
```

### Twig

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

    public function view($filename, array $data = []): string
    {
        return $this->templateEngine->render($filename . '.twig', $data);
    }
}
```

If other code resolves the engine through `App\Core\TemplateEngineFactory`, apply the same swap there.

## Step 3 - Add a template and render it

Create the file for your engine:

```hbs
<h1>{{title}}</h1>

<p>{{content}}</p>
```

```twig
<h1>{{ title }}</h1>

<p>{{ content }}</p>
```

Render it as usual. This part is identical for both engines:

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

If the page renders, the swap works. Everything below is only for templates that relied on default-engine features.

## Only if you used these features

### Escaping

Both replacements escape by default, same as Roolith. Use raw output only for trusted HTML:

```hbs
<p>{{title}}</p>
<p>{{{content}}}</p>
```

```twig
<p>{{ title }}</p>
<p>{{ content|raw }}</p>
```

### The `url()` helper

The default engine provides `$this->url()` inside templates. Recreate it only if your templates call it.

Mustache, in the constructor:

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

Twig, in the constructor:

```php
$this->templateEngine->addFunction(new \Twig\TwigFunction('url', function ($text) {
    return rtrim(Config::get('baseUrl'), '/') . '/' . ltrim($text, '/');
}));
```

```twig
<link rel="stylesheet" href="{{ url('assets/app.css') }}">
```

### Partials and layouts

Mustache partial, loads `views/partials/header.mustache`:

```hbs
{{> partials/header }}
```

Twig partial and layout:

```twig
{% include 'partials/header.twig' %}
{% extends 'layout.twig' %}
{% block content %}{% endblock %}
```

## Checklist

- Twig caches compiled templates on disk. Keep `cache => false` while developing, point it at a writable directory in production.
- Twig 3.x needs PHP 8.1+, which Roolith's PHP >= 8.2 requirement already covers.
- A missing template file is a server error. When one appears, check the name and extension first.
