# roolith-framework
Roolith PHP framework. Very minimalistic and less overhead.

### Install
```
composer create-project roolith/framework your_app_name
```

### Documentation

* Cache [documentation](https://github.com/im4aLL/roolith-cache)
* Config [documentation](https://github.com/im4aLL/roolith-config)
* Database [documentation](https://github.com/im4aLL/roolith-database)
* Event [documentation](https://github.com/im4aLL/roolith-event)
* Generator [documentation](https://github.com/im4aLL/roolith-generator)
* Router [documentation](https://github.com/im4aLL/roolith-router)
* Template-engine [documentation](https://github.com/im4aLL/roolith-template-engine)


> If you want to use this or need any help, you may reach to `me@habibhadi.com`
> This framework has been developed for educational purpose!

### Generator
```
php roolith generate controller DemoController
php roolith generate model Product
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
```
npm install
```
Then 
```
npm start
```

> Before npm start open the gulpfile.js update browsersync options as per your need. Specially change vhost defined `local.roolith-framework.me`

To add SCSS and JS use `source/scss/app.scss` and `source/js/app.js` 

Use 
```
npm run build
```
for production build. It will create min.css and min.js file.

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

### Views
View files are into `/views` and view files are straight forward [documentation](https://github.com/im4aLL/roolith-template-engine)

> Let's keep it simple!