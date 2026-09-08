# Getting Started

Roolith is a minimalistic PHP micro-framework.
It keeps the core small and lets plain PHP do the heavy lifting.
Use it for any project, personal or commercial.

## Requirements

- PHP >= 8.2
- Composer

## Installation

Create a new project with Composer.

```bash
composer create-project roolith/framework your_app_name
```

## Project Structure

```text
├── app/
│   ├── Controllers/       # Application controllers
│   ├── Core/              # Framework glue code (Request, Validator, factories)
│   ├── Http/
│   │   └── routes.php     # Route definitions
│   ├── Middlewares/       # Middleware classes
│   ├── Models/            # Model classes
│   └── Utils/             # Utility classes
├── config/
│   └── config.php         # Application configuration
├── lang/                  # Localization files (en, es, ...)
├── source/                # SCSS and JS sources (frontend workflow)
├── views/                 # Template files
├── constant.php           # Application constants
├── index.php              # Front controller
└── roolith                # CLI entry for the generator
```

## How a Request Flows

All requests hit `index.php`. It delegates to `App\Core\System::run()`, which wraps `bootstrap()->processRequest()->complete()` with `RedirectException` emit plus `ErrorHandler` handling and `complete()` in `finally`, and never throws. Do not call the bare `bootstrap()->processRequest()->complete()` chain yourself, it leaks `RedirectException` redirects.

```php
App\Core\System::run();
```

If no route matches, the framework renders `views/404.php`. A path that exists for another method responds with 405 `Method Not Allowed` and an `Allow` header. Canonical host redirects are emitted before routing, and uncaught errors go to `ErrorHandler` (Whoops page in development, generic 500 with trace id in production).

## Constants

Application constants live in `constant.php`.

```php
const ROOLITH_CONFIG_ROOT = APP_ROOT . '/config';
const APP_VIEW_ROOT = APP_ROOT . '/views';
// CMS is env-driven: APP_ENABLE_CMS=1 in .env mounts the CMS release asset.
```

When `APP_ENABLE_CMS` is off (default), all files under the `Admin` folder are deactivated. See [CMS installer](/cms-installer) for the release-asset flow; `installer.zip` stays tracked locally for reference and local install but is excluded from distribution archives.

## Running the App

The fastest way is Docker, it starts PHP, MySQL and phpMyAdmin with one command.
See [Docker](/docker) for details.

```bash
docker compose up -d --build
```

Alternatively, point your web server or vhost to the project root and open it in the browser.
The default `baseUrl` in `config/config.php` is `http://localhost:8080/`, adjust it to your local setup.

For the frontend assets workflow (SCSS and JS), see [Frontend Workflow](/frontend-workflow).

## Next Steps

- Define your first routes in [Routing](/routing)
- Render pages with [Controllers](/controllers) and [Views](/views)
- Talk to the database with [Models](/models)
