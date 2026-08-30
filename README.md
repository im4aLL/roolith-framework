# roolith-framework
Roolith PHP micro-framework. Very minimalistic and less overhead.

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

### Generator
```
php roolith generate controller DemoController
php roolith generate model Product
php roolith generate middleware AuthMiddleware
```

### Define route
Open `app/Http/routes.php` and define routes as per [documentation](https://github.com/im4aLL/roolith-router)

### Error page
If there is no route defined then by default it will look for `404.php` in `viwes` folder.

### Config
All application configuration has been stored on `config/config.php` for more details read [documentation](https://github.com/im4aLL/roolith-config)

### Constant
Application constants has been defined into `/constant.php`

### Frontend workflow

The framework uses Vite for SCSS and JavaScript.
```
npm install
```

For development with hot module replacement set `viteDevServer` to `http://localhost:5173` in `config/config.php` and run
```
npm run dev
```

The site can then be browsed through `http://localhost:5173` where the dev server proxies the PHP app.

To rebuild assets on change without the dev server use
```
npm run watch
```

To add SCSS and JS use `source/scss/app.scss` and `source/js/app.js`

Use 
```
npm run build
```
for production build.
It creates minified files in the `assets` folder.

### Model
Model files located into `app/Models`

```php
<?php
namespace App\Models;

class User extends Model
{
    protected $table = 'users';
}
```

### Controllers
Controller files located into `app/Controllers`

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
View files are into `/views` and view files are straight forward [documentation](https://github.com/im4aLL/roolith-template-engine)

> Let's keep it simple!

### Print route URL inside template
```php
<form action="<?= route('welcome.form') ?>" method="post">
```

### Request

```php
Request::input('page');
Request::has('page');
Request::all();
Request::only('page');
Request::only(['page', 'other_param']);
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

### Validator
```php
$validator = new Validator();
$validator->check(
    [
        'name' => 'john',
        'email' => 'me@habibhadi.com',
        'company' => '',
        'age' => 18,
        'url' => 'something!',
    ],
    [
        'name' => Rules::set()->isRequired()->minLength(10)->isArray()->maxLength(20)->notExists(\App\Models\User::class),
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

### Sanitize
```php
Sanitize::param($_GET['param']);
Sanitize::any('untrusted_string<script>alert("a")</script>');
Sanitize::email('something/@bad.com');
Sanitize::string('xss_protect');
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

Get message 
```php
__('errors.required'); // This field is required
```

Set local
```php
Settings::setLang('es');
Settings::getLang();
```

Once `es` lang is set it will look `lang/es/message.php`. So when `es` has been set then below code will output - 
```php
__('errors.required'); // este campo es requerido
```

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