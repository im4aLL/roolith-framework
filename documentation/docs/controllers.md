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

Use `$this->view($filename, $data)` to render a template from the `views` folder.
The data array is passed to the template as variables.

```php
return $this->view('home', ['title' => 'Roolith Framework']);
```

See [Views](/views) for template syntax.

## Returning Data

Return an array or a model result to send it as a response.
This is handy for quick JSON style endpoints.

```php
public function users()
{
    return User::all();
}

public function show($id)
{
    return User::orm()->find($id);
}
```

## Registering Routes

Point routes to controller methods with the `Controller@method` syntax.
See [Routing](/routing).

```php
$router->get("/example", WelcomeController::class . "@index");
```

## Generating Controllers

```bash
php roolith generate controller DemoController
```

See [Generator](/generator).
