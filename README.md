# roolith-framework
Roolith PHP micro-framework. Very minimalistic and less overhead.

### Architecture
Roolith is a minimal synchronous PHP micro-framework with an MVC shape: one entrypoint (`index.php`) boots shared services through `App\Core\System`, routes one HTTP request to one controller action, optionally touches MySQL through a thin model layer, then renders a PHP view or returns data and tears down per-request resources.

```
Browser -> index.php -> System (bootstrap) -> Router + Middleware -> Controller -> Model / View -> Response -> System (complete)
```

Shared services are exposed through small singleton factories (`RouterFactory`, `DatabaseFactory`, `TemplateEngineFactory`) and static facades (`Request`, `Storage`, `Sanitize`, `Validator`), composed from standalone `roolith/*` packages plus Carbon and Whoops. The database is optional, and frontend assets are built with Vite (`source/` -> `assets/`).

Interested in the whole picture? Start with [ARCHITECTURE.md](ARCHITECTURE.md) for the system overview, layer map, request lifecycle, and extension points, also available as [Architecture](documentation/docs/architecture.md) in the docs site. `ARCHITECTURE.md` is canonical; the docs page mirrors it.

### Install
```
composer create-project roolith/framework your_app_name
```

### Run with Docker
```
docker compose up -d --build
```
Then open http://localhost:8080. See [DOCKER-README.md](DOCKER-README.md) for details.

### Documentation

* Cache [documentation](https://github.com/im4aLL/roolith-cache)
* Config [documentation](https://github.com/im4aLL/roolith-config)
* Database [documentation](https://github.com/im4aLL/roolith-database)
* Event [documentation](https://github.com/im4aLL/roolith-event)
* Generator [documentation](https://github.com/im4aLL/roolith-generator)
* Router [documentation](https://github.com/im4aLL/roolith-router)
* Template-engine [documentation](https://github.com/im4aLL/roolith-template-engine)


> If you want to use this or need any help, you may reach to `me@habibhadi.com`
> Free to use for any purpose.
> Support: see [SECURITY.md](SECURITY.md) for reporting, [CONTRIBUTING.md](CONTRIBUTING.md) for dev checks (`composer test`, `composer lint`, `composer analyse`), [CHANGELOG.md](CHANGELOG.md) for releases.

### Generator
```
php roolith generate controller DemoController
php roolith generate model Product
php roolith generate middleware AuthMiddleware
```

### Define route
Open `app/Http/routes.php` and define routes as per [documentation](https://github.com/im4aLL/roolith-router). Prefer the callable form `[WelcomeController::class, 'index']`; the string form `WelcomeController::class . "@index"` still works but is legacy. Lint with `php roolith route:list`.

### Error page
If there is no route defined then by default it will look for `404.php` in `views/` folder.

### Config
All application configuration has been stored in `config/config.php` for more details read [documentation](https://github.com/im4aLL/roolith-config)

### Constant
Application constants have been defined in `constant.php`

### Frontend workflow

The framework uses Vite for SCSS and JavaScript. Actual scripts from `package.json`:

```
"dev": "vite",
"watch": "vite build --watch",
"build": "vite build"
```

```
npm install
```

For development with hot module replacement set `viteDevServer` to `http://localhost:5173` in `config/config.php` and run
```
npm run dev
```

Browse via `http://localhost:8080` for direct PHP or `http://localhost:5173` for HMR (the dev server proxies everything except `@vite`, `@id`, `@fs`, `node_modules`, `source`, `__open-in-editor` to PHP on `:8080`, so sessions plus HMR share one origin; see `documentation/docs/frontend-workflow.md` for proxy limits).

To rebuild assets on change without the dev server use
```
npm run watch
```

Entries are `source/js/app.js` (Vite input key `app`) and `source/scss/app.scss` (input key `style`). To add SCSS and JS use those two files.

Use 
```
npm run build
```
for production build.
It creates minified, content-hashed files in the `assets/build` folder (for example `assets/build/js/app-[hash].js` plus `assets/build/css/app-[hash].css` plus `assets/build/.vite/manifest.json`; views resolve via `viteJs()` plus `viteCss()`). Uploads must live outside the build output, e.g. `public/uploads/` (web-accessible fixture survives rebuilds) or `storage/` (outside the docroot); `npm run build` wipes only `assets/build`.

### Model
Model files located in `app/Models`. One model maps to one table via `protected string $table` plus `$primaryColumn` (default `id`); `php roolith migrate` creates those tables under `database/migrations`.

```php
<?php
namespace App\Models;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email'];
    protected array $casts = ['id' => 'int'];

    protected function validationRules(): array
    {
        return [
            'name' => \App\Core\Rules::set()->isRequired()->minLength(2),
            'email' => \App\Core\Rules::set()->isRequired()->isEmail(),
        ];
    }
}
```

Validated write path (filter plus validate plus transaction):

```php
$model = new User();
$data = $model->filterFillable(Request::all());
$errors = $model->validate($data);

if ($errors !== []) {
    return $errors;
}

User::transaction(static function ($db) use ($data): void {
    $db->table('users')->insert($data);
});
```

Migrations plus transactions:

```
php roolith migrate:create create_users_table
php roolith migrate
php roolith migrate:status
php roolith migrate:rollback
```

```php
\App\Core\DatabaseFactory::transaction(static function ($db) {
    $db->table('users')->insert(['name' => 'Hadi']);
});
```

### Controllers
Controller files located in `app/Controllers`

```php
<?php
namespace App\Controllers;
use App\Models\User;

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
    
    public function users()
    {
        return User::all();
    }

    public function show($id)
    {
        return User::orm()->find($id);
    }
}
```

### Views
View files are in `views/` and view files are straight forward [documentation](https://github.com/im4aLL/roolith-template-engine). Escape every untrusted value at render with `escape()` or `$this->escape()`; `Request::input()` stays raw so `O'Reilly` keeps its form.

> Let's keep it simple!

### Print route URL inside template
```php
<form action="<?= route('welcome.form') ?>" method="post">
```

### Request

`Request::input()` returns raw values (no stripping); validate by type, escape at render with `escape()`. `only()` plus `except()` accept a string or array via `_::only` plus `_::except`. JSON bodies parse via `php://input`; `all()` returns raw plus `_files` on POST. Pass `['sanitize' => true]` only for narrow slug or email paths.

```php
Request::input('page');
Request::has('page');
Request::all();
Request::only('page');
Request::only(['page', 'other_param']);
Request::except('password');
Request::cookie('cookie_name');
Request::url();
Request::fullUrl();
Request::method();
Request::isMethod('POST');

Request::file('photo');
Request::file('photo')->isValid();
Request::file('photo')->upload($destination);
Request::hasFile('photo');
```

Full form plus JSON plus files example with validation and error display:

```php
// Form POST: validate then escape at render.
$data = Request::all();
$validator = new \App\Core\Validator();
$validator->check($data, [
    'email' => \App\Core\Rules::set()->isRequired()->isEmail(),
]);

if ($validator->fails()) {
    return $this->view('form', ['errors' => $validator->errors()]);
}

// JSON body: {"email":"a@b.c"} reads the same way.
$email = Request::input('email');

// Files: check hasFile, validate, then upload (destination must be writable).
if (Request::hasFile('photo')) {
    $file = Request::file('photo');

    if ($file->isValid()) {
        $file->upload(APP_ROOT . '/public/uploads');
    }
}
```

`hasFile()` checks `$_FILES`; size plus MIME limits live in `App\Core\File` (see `documentation/docs/file-upload.md`).

### Middleware, rate limiter, and i18n

Attach middleware per route or group:

```php
$router->post('/form', [WelcomeController::class, 'formSubmit'])->middleware(CsrfMiddleware::class);
$router->group(['middleware' => [new AuthMiddleware()]], static function ($r): void {
    $r->get('/dashboard', static fn (): string => 'Dashboard');
});
```

Reset the limiter on success:

```php
if ($loginOk) {
    SessionRateLimiter::clear('login:' . getIpAddress());
}
```

Translate with fallback (missing key or locale returns null):

```php
trans('errors.required') ?? 'This field is required';
__('errors.required') ?? 'This field is required'; // __() is a BC alias of trans()
```

Note: `Settings::setLang('es')` writes a cookie, so the new locale applies on the next request, not the current one.

### Cache and events

Cache expensive reads, fire events for side effects (see `App\Examples\CacheAndEventExamples`):

```php
use App\Examples\CacheAndEventExamples;

$user = CacheAndEventExamples::cachedModelQuery('users_by_id_1', static fn () => User::orm()->where('id', 1)->get());
CacheAndEventExamples::registerUserCreatedListeners();
CacheAndEventExamples::userCreated(['email' => 'a@b.c']);
```

### Validator

String example plus array example (see `App\Core\Validator.php:8-31` usage block):

```php
$validator = new Validator();

// String field: required plus length.
$validator->check(
    ['name' => 'john doe long enough'],
    ['name' => Rules::set()->isRequired()->minLength(10)->maxLength(20)]
);

// Array field: required array plus length on the array.
$validator->check(
    ['tags' => ['php', 'framework']],
    ['tags' => Rules::set()->isArray()->minLength(1)->maxLength(5)]
);

if ($validator->success()) {
    // do something!
} else {
    // ['name' => ['minLength'], ...]
    $errors = $validator->errors();
}
```

Full example with email plus conditional plus URL plus numeric:

```php
$validator = new Validator();
$validator->check(
    [
        'name' => 'john doe long enough',
        'email' => 'me@habibhadi.com',
        'company' => '',
        'age' => 18,
        'url' => 'https://example.com',
    ],
    [
        'name' => Rules::set()->isRequired()->minLength(10)->maxLength(20),
        'email' => Rules::set()->isEmail()->isRequired(),
        'company' => Rules::set()->isRequiredIf('age:greater_than:10'),
        'url' => Rules::set()->isUrl(),
        'age' => Rules::set()->isNumeric(),
    ]
);

if ($validator->success()) {
    // do something!
}
```

Note: `notExists()` plus `exists()` check the database (`notExists(User::class)`); keep them on fields that truly need uniqueness or existence checks.

### Sanitize

Narrow helpers for slug plus email lookups; general input stays raw and is escaped at render with `escape()`.

```php
Sanitize::param('hello-world_1'); // slug-safe
Sanitize::email('user@example.com'); // email-safe
escape($raw); // render-time escaping for views
```

### Array methods

Example:
```php
_::only(['name' => 'hadi', 'age' => 33], 'name');
_::only(['name' => 'hadi', 'age' => 33, 'something' => 'else'], ['name', 'something']);
_::drop([1, 2, 3, 4, 5]);
```

List of methods - 
- except
- chunk
- compact
- concat
- difference
- drop
- dropRight
- dropWhile
- filter
- remove
- findIndex
- indexOf
- join
- last
- first
- reverse
- take
- takeRight
- uniq
- find
- each
- contains
- map
- isMultidimensional
- resetKeys
- order
- orderBy
- orderByString
- random
- add
- flat
- dot
- exists
- get
- has
- pluck
- prepend
- query
- set

### Localization

Get message with fallback (missing key returns null, so coalesce):

```php
trans('errors.required') ?? 'This field is required'; // This field is required
__('errors.required') ?? 'This field is required'; // __() is a BC alias of trans()
```

Set local
```php
Settings::setLang('es');
Settings::getLang();
```

Once `es` lang is set it will look in `lang/es/message.php`. So when `es` has been set then below code will output - 
```php
trans('errors.required'); // este campo es requerido
```

Dependencies use caret deliberately (`roolith/*: ^2.x`, `nesbot/carbon: ^2.73`) so patches flow; exact pins need a comment. Run `composer audit` regularly.

### Observability and checks

```bash
composer test
composer lint
composer analyse
composer audit
php roolith route:list
npm run build
```

Logs carry a per-request trace ID via `App\Core\Logger` plus `App\Core\Log` (bootstrap, router, 404, controller, unhandled).

### Cookie
Set cookie 
```php
Storage::setCookie('name', 'value', Carbon::now()->addMonths());
```

Get cookie
```php
Request::cookie('name');
```

Delete cookie 
```php
Storage::deleteCookie('name');
```

Session 
```php
Storage::setSession('name', 'value');
Storage::deleteSession('name');
```