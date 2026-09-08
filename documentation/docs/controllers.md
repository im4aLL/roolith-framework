# Controllers

Controllers live in `app/Controllers` under the `App\Controllers` namespace.
They extend the base `Controller` class.

## Basic Controller

```php
<?php
namespace App\Controllers;

class WelcomeController extends Controller
{
    public function index()
    {
        $data = [
            'content' => 'Welcome to Roolith framework!',
            'title' => 'Roolith Framework',
        ];

        return $this->view('home', $data);
    }
}
```

## Returning a View

Use `$this->view($filename, $data)` to render a template from the `views` folder. The data array is passed to the template as variables. `view():string` is fail-closed: it returns the rendered HTML string, never echoes, never returns false, and throws `App\Core\Exceptions\Exception` when rendering fails. The base `Controller::__construct()` requires `baseUrl` config and throws when it is missing.

```php
return $this->view('home', ['title' => 'Roolith Framework']);
```

See [Views](/views) for template syntax.

## Returning Data

Return `$this->json($payload)` to send a JSON envelope response (`App\Core\Response` via `App\Core\ApiResponseTransformer`, `Content-Type: application/json`). The canonical controller returns are `string|App\Core\Response`, emitted by `App\Core\RouterResponse`.

```php
public function users()
{
    return $this->json(User::all());
}

public function show($id)
{
    return $this->json(User::orm()->find($id));
}
```

## Registering Routes

Point routes to controller methods with the callable `[Controller::class, 'method']` form. The string `Controller::class . "@method"` form is legacy BC only. See [Routing](/routing).

```php
$router->get("/example", [WelcomeController::class, "index"]);
```

## Generating Controllers

```bash
php roolith generate controller DemoController
```

See [Generator](/generator).
