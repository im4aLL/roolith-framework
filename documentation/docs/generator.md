# Generator

The `roolith` CLI generates PHP classes from templates.
It is powered by [roolith/generator](https://github.com/im4aLL/roolith-generator).

## Usage

Run the generator from the project root.

```bash
php roolith generate controller DemoController
php roolith generate model Product
php roolith generate middleware AuthMiddleware
```

Related CLI commands live in `App\Console\Cli` and are covered in [CLI](/cli): `php roolith route:list` lints and displays routes, `php roolith migrate` / `migrate:status` / `migrate:create` / `migrate:rollback` manage migrations (see [Migration](/migration)), `php roolith seed` / `seed:status` / `seed:create` / `seed:run` plus `seeder:` aliases manage seeders (see [Seeder](/seeder)).

## Where Files Are Written

Generated files land in the default framework folders.

```text
app/Controllers/DemoController.php
app/Models/Product.php
app/Middlewares/AuthMiddleware.php
```

## Templates

The templates live in `app/Core/generator-templates`.
Each template file starts with an `outputBaseDir` instruction that tells the generator where to write the file.

```text
# outputBaseDir: app/Controllers
<?php
namespace App\Controllers;

class {{name}} extends Controller
{
    public function index()
    {
    }
}
```

- `outputBaseDir` means in which folder the file will be generated.
- `{{name}}` is replaced with the name argument in title case, `{name}` keeps the raw value.

Shortcuts: `generate` = `g`, `controller` = `c`, `command` = `cmd`.

```bash
php roolith g c DemoController
```

Feel free to edit the existing templates or add your own.
For example, add a `test.txt` template and generate from it.

```bash
php roolith generate test something
```

## Custom Commands

You can also register your own console commands.
A command is a class implementing `CommandInterface`.

```php
<?php
use Roolith\Generator\Command;
use Roolith\Generator\Console;
use Roolith\Generator\FileGenerator;
use Roolith\Generator\FileParser;
use Roolith\Generator\Interfaces\CommandInterface;

class TestCommand implements CommandInterface
{
    public function register()
    {
        return [
            'name' => 'test',
            'alias' => [],
            'typeAlias' => [],
        ];
    }

    public function handle(Command $command, Console $console, FileParser $fileParser, FileGenerator $fileGenerator)
    {
        $console->output('Test command registered!');
    }
}
```

Register it in the `roolith` CLI entry point and run `php roolith test`.

```php
$generator
    ->setTemplateDirectory(__DIR__ . '/app/Core/generator-templates')
    ->setProjectBaseDirectory(__DIR__)
    ->registerCommandClass([
        TestCommand::class
    ])
    ->watch($argv);
```
