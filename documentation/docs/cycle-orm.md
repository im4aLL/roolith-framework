# Custom ORM (Cycle)

Roolith ships with [roolith/database](https://github.com/im4aLL/roolith-database) and a thin `App\Models\Model` wrapper.
You can keep it, run side by side, or replace it entirely with a DataMapper ORM such as [Cycle ORM](https://github.com/cycle/orm) ([docs](https://cycle-orm.dev/docs/intro-install/current/en)).
This recipe shows the replacement using Cycle ORM 2 with PHP 8 attributes.

Cycle is a DataMapper, not ActiveRecord.
Entities are plain PHP objects, the schema is compiled from attributes, and persistence goes through `Cycle\ORM\EntityManager`.
It is designed for classic and daemonized PHP apps (RoadRunner) with an immutable core and disposable Unit of Work.

It touches the same three places as the Doctrine recipe, verified in this codebase.

- `app/Models/Model.php:1` - base model that returns `DatabaseFactory::getInstance()` and exposes `orm()` / `raw()`.
- `app/Core/DatabaseFactory.php:1` - singleton that creates `Roolith\Store\Database`.
- `app/Core/System.php:39` - `bootstrap()` reads `config/config.php` key `database` and calls `connectToDatabase()`, `complete()` calls `disconnectFromDatabase()`.

You will replace or augment these three files.
Pick full replacement if you want only Cycle, or side by side if you want to keep the query builder for legacy code.

## Installation

Install Cycle ORM, the annotated schema extension, and the tokenizer.
Cycle 2 needs `cycle/annotated` for attributes and `spiral/tokenizer` plus `symfony/finder` for class discovery.

```bash
composer require cycle/orm cycle/database cycle/annotated spiral/tokenizer symfony/finder
```

For MySQL use `pdo_mysql`, for Postgres `pdo_pgsql`, for SQLite `pdo_sqlite`.
Cycle supports all of them through `cycle/database`.

Optional helpers that you will likely need later.

```bash
composer require cycle/migrations cycle/schema-renderer
```

`cycle/migrations` handles versioned migrations.
`cycle/schema-renderer` dumps the compiled schema for debugging.

## Configuration

Cycle needs a `DatabaseManager` config and a list of entity paths.
Add a `cycle` key to `config/config.php` and keep or null out the legacy `database` key.

Full replacement - disable Roolith database:

```php
<?php
return [
    "baseUrl" => "http://localhost:8080/",
    "viteDevServer" => "",
    "database" => null,

    "cycle" => [
        "default" => "default",
        "databases" => [
            "default" => ["connection" => "mysql"],
        ],
        "connections" => [
            "mysql" => [
                "driver" => "mysql",
                "host" => "localhost",
                "port" => 3306,
                "database" => "roolith_cms",
                "username" => "root",
                "password" => "",
                "queryCache" => true,
            ],
        ],
        // where your entities live
        "entityPaths" => [APP_ROOT . "/app/Entities"],
        "schemaCache" => APP_ROOT . "/storage/cache/cycle-schema.php",
        "syncTables" => !isProductionEnvironment(),
    ],

    "forceNonWww" => true,
    "version" => time(),
];
```

Side by side - keep both:

```php
"database" => [
    "host" => "localhost",
    "name" => "roolith_cms",
    "user" => "root",
    "pass" => "",
],
"cycle" => [
    "default" => "default",
    "databases" => [
        "default" => ["connection" => "mysql"],
    ],
    "connections" => [
        "mysql" => [
            "driver" => "mysql",
            "host" => "localhost",
            "port" => 3306,
            "database" => "roolith_cms",
            "username" => "root",
            "password" => "",
            "queryCache" => true,
        ],
    ],
    "entityPaths" => [APP_ROOT . "/app/Entities"],
    "schemaCache" => APP_ROOT . "/storage/cache/cycle-schema.php",
    "syncTables" => !isProductionEnvironment(),
],
```

Other driver examples, from [Connect to Database](https://cycle-orm.dev/docs/database-connect/current/en).

SQLite memory (good for tests):

```php
"sqlite" => [
    "driver" => "sqlite",
    "connection" => "sqlite::memory:",
    "queryCache" => true,
],
```

Postgres:

```php
"postgres" => [
    "driver" => "postgres",
    "host" => "127.0.0.1",
    "port" => 5432,
    "database" => "roolith_cms",
    "username" => "postgres",
    "password" => "",
    "queryCache" => true,
],
```

`isProductionEnvironment()` is defined in `app/Utils/functions.php`.
When `syncTables` is true, Cycle will alter tables to match your attributes on every bootstrap.
Keep it true in development, false in production and use migrations instead.

## Creating a Cycle Factory

Mirror `DatabaseFactory` but for Cycle.
Create `app/Core/CycleFactory.php`.
It builds a `Cycle\Database\DatabaseManager`, compiles the ORM schema from attributes, and returns a `Cycle\ORM\ORM` singleton.

```php
<?php
namespace App\Core;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Factory as OrmFactory;
use Cycle\ORM\ORM;
use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Registry;
use Cycle\Annotated;
use Spiral\Tokenizer\ClassLocator;
use Symfony\Component\Finder\Finder;
use Roolith\Configuration\Config;

class CycleFactory
{
    private static ?ORM $orm = null;
    private static ?DatabaseManager $dbal = null;

    private function __construct() {}

    public static function getORM(): ORM
    {
        if (self::$orm === null) {
            self::$orm = self::createORM();
        }

        return self::$orm;
    }

    public static function getDBAL(): DatabaseManager
    {
        // ensure DBAL is initialized
        self::getORM();

        return self::$dbal;
    }

    private static function createORM(): ORM
    {
        $cycleConfig = Config::get("cycle");

        if (!$cycleConfig) {
            throw new \RuntimeException("Missing `cycle` config in config/config.php");
        }

        $dbal = new DatabaseManager(new DatabaseConfig(
            self::buildDbalConfig($cycleConfig)
        ));

        self::$dbal = $dbal;

        $entityPaths = $cycleConfig["entityPaths"] ?? [APP_ROOT . "/app/Entities"];
        $schemaCache = $cycleConfig["schemaCache"] ?? APP_ROOT . "/storage/cache/cycle-schema.php";
        $syncTables = $cycleConfig["syncTables"] ?? !isProductionEnvironment();

        $schemaArray = null;

        if (!$syncTables && is_file($schemaCache)) {
            $schemaArray = require $schemaCache;
        }

        if ($schemaArray === null) {
            $schemaArray = self::compileSchema($dbal, $entityPaths, $syncTables);

            if (!$syncTables && $schemaCache) {
                $dir = dirname($schemaCache);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                file_put_contents(
                    $schemaCache,
                    "<?php return " . var_export($schemaArray, true) . ";"
                );
            }
        }

        return new ORM(new OrmFactory($dbal), new Schema($schemaArray));
    }

    private static function buildDbalConfig(array $cfg): array
    {
        $connections = [];

        foreach ($cfg["connections"] as $name => $conn) {
            $driver = strtolower($conn["driver"] ?? "mysql");

            if ($driver === "sqlite") {
                $queryCache = $conn["queryCache"] ?? true;
                // support both DSN style and file/memory configs
                if (isset($conn["connection"]) && str_starts_with($conn["connection"], "sqlite:")) {
                    $connections[$name] = new \Cycle\Database\Config\SQLiteDriverConfig(
                        connection: new \Cycle\Database\Config\SQLite\DsnConnectionConfig(dsn: $conn["connection"]),
                        queryCache: $queryCache
                    );
                } elseif (($conn["database"] ?? null) === ":memory:") {
                    $connections[$name] = new \Cycle\Database\Config\SQLiteDriverConfig(
                        connection: new \Cycle\Database\Config\SQLite\MemoryConnectionConfig(),
                        queryCache: $queryCache
                    );
                } else {
                    $connections[$name] = new \Cycle\Database\Config\SQLiteDriverConfig(
                        connection: new \Cycle\Database\Config\SQLite\FileConnectionConfig(
                            database: $conn["database"] ?? APP_ROOT . "/storage/database.sqlite"
                        ),
                        queryCache: $queryCache
                    );
                }
                continue;
            }

            if ($driver === "mysql") {
                $connections[$name] = new \Cycle\Database\Config\MySQLDriverConfig(
                    connection: new \Cycle\Database\Config\MySQL\TcpConnectionConfig(
                        database: $conn["database"],
                        host: $conn["host"] ?? "127.0.0.1",
                        port: $conn["port"] ?? 3306,
                        user: $conn["username"] ?? $conn["user"] ?? "root",
                        password: $conn["password"] ?? $conn["pass"] ?? ""
                    ),
                    queryCache: $conn["queryCache"] ?? true
                );
                continue;
            }

            if ($driver === "postgres" || $driver === "pgsql") {
                $connections[$name] = new \Cycle\Database\Config\PostgresDriverConfig(
                    connection: new \Cycle\Database\Config\Postgres\TcpConnectionConfig(
                        database: $conn["database"],
                        host: $conn["host"] ?? "127.0.0.1",
                        port: $conn["port"] ?? 5432,
                        user: $conn["username"] ?? $conn["user"] ?? "postgres",
                        password: $conn["password"] ?? $conn["pass"] ?? ""
                    ),
                    schema: $conn["schema"] ?? "public",
                    queryCache: $conn["queryCache"] ?? true
                );
                continue;
            }

            throw new \RuntimeException("Unsupported cycle driver `$driver` for connection `$name`");
        }

        return [
            "default" => $cfg["default"] ?? "default",
            "databases" => $cfg["databases"] ?? ["default" => ["connection" => array_key_first($connections)]],
            "connections" => $connections,
        ];
    }

    private static function compileSchema(DatabaseManager $dbal, array $paths, bool $syncTables): array
    {
        $finder = (new Finder())->files()->in($paths);
        $classLocator = new ClassLocator($finder);

        // Cycle 3.x uses TokenizerEntityLocator / TokenizerEmbeddingLocator wrappers
        // Fallback to classLocator for older cycle/annotated 3.x
        if (class_exists(\Cycle\Annotated\Locator\TokenizerEntityLocator::class)) {
            $embeddingLocator = new \Cycle\Annotated\Locator\TokenizerEmbeddingLocator($classLocator);
            $entityLocator = new \Cycle\Annotated\Locator\TokenizerEntityLocator($classLocator);
            $embeddings = new Annotated\Embeddings($embeddingLocator);
            $entities = new Annotated\Entities($entityLocator);
        } else {
            $embeddings = new Annotated\Embeddings($classLocator);
            $entities = new Annotated\Entities($classLocator);
        }

        $generators = [
            new \Cycle\Schema\Generator\ResetTables(),
            $embeddings,
            $entities,
            new Annotated\TableInheritance(),
            new Annotated\MergeColumns(),
            new \Cycle\Schema\Generator\GenerateRelations(),
            new \Cycle\Schema\Generator\GenerateModifiers(),
            new \Cycle\Schema\Generator\ValidateEntities(),
            new \Cycle\Schema\Generator\RenderTables(),
            new \Cycle\Schema\Generator\RenderRelations(),
            new \Cycle\Schema\Generator\RenderModifiers(),
            new \Cycle\Schema\Generator\ForeignKeys(),
            new Annotated\MergeIndexes(),
            new \Cycle\Schema\Generator\GenerateTypecast(),
        ];

        if ($syncTables) {
            $generators[] = new \Cycle\Schema\Generator\SyncTables();
        }

        return (new Compiler())->compile(new Registry($dbal), $generators);
    }

    public static function reset(): void
    {
        self::$orm = null;
        self::$dbal = null;
    }
}
```

### Why a Factory

`System::bootstrap()` needs a single place to connect and `System::complete()` needs a single place to reset.
The factory gives you `getORM()` like `DatabaseFactory::getInstance():15` does today.
Controllers and models then call `CycleFactory::getORM()` instead of `DatabaseFactory::getInstance()`.

The schema is cached to `storage/cache/cycle-schema.php` when `syncTables` is false.
Delete that file after changing entities in production, or bump a version key.
In development `SyncTables` keeps the database in sync automatically, but never use it in production.

If you use `cycle/annotated` 4.x, the locator classes are `TokenizerEntityLocator` and `TokenizerEmbeddingLocator`.
The factory above handles both.

## Wiring It Into System.php

### Option A - Full Replacement

Disable the legacy connection and bootstrap Cycle instead.
Edit `app/Core/System.php`.

```php
<?php
namespace App\Core;

use App\Core\Exceptions\Exception;
use Cycle\ORM\ORM;
use Roolith\Configuration\Config;
use Roolith\Configuration\Exception\InvalidArgumentException;
use Roolith\Route\Interfaces\RouterInterface;

class System
{
    protected ?ORM $orm = null;

    public function __construct()
    {
        require_once APP_ROOT . "/constant.php";

        $cmsConstantPath = APP_ROOT . "/cms-constant.php";
        if (file_exists($cmsConstantPath)) {
            require_once $cmsConstantPath;
        }

        require_once APP_ROOT . "/app/Utils/functions.php";

        $this->registerCustomError();
    }

    public function bootstrap(): static
    {
        $this->preProcessor();

        try {
            $cycleConfig = Config::get("cycle");
            if ($cycleConfig) {
                $this->orm = CycleFactory::getORM();
                // trigger connection early to surface config errors
                $this->orm->getFactory()->getDatabaseManager()->database(
                    $cycleConfig["default"] ?? "default"
                )->getDriver()->getConnection()->getPDO();
            }
        } catch (InvalidArgumentException $e) {
            throw new Exception($e->getMessage());
        } catch (\Throwable $e) {
            throw new Exception("Unable to connect with Cycle: " . $e->getMessage());
        }

        return $this;
    }

    public function complete(): static
    {
        CycleFactory::reset();
        Storage::removeTemp();

        return $this;
    }

    // processRequest(), router(), preProcessor(), registerCustomError() stay unchanged
}
```

Set `"database" => null` in `config/config.php` so the old `connectToDatabase():105` path is not needed.
If you keep the method for reference, make it a no-op or remove it.

### Option B - Side by Side

Keep Roolith database for existing models and add Cycle for new ones.
Keep `protected ?DatabaseInterface $db` and add `protected ?ORM $orm`.

```php
protected ?DatabaseInterface $db = null;
protected ?ORM $orm = null;

public function bootstrap(): static
{
    $this->preProcessor();

    // legacy Roolith database
    try {
        $dbConfig = Config::get("database");
        if ($dbConfig) {
            $this->db = DatabaseFactory::getInstance();
            $isConnected = $this->db->connect($dbConfig);
            if (!$isConnected) {
                throw new Exception("Unable to connect to the database");
            }
        }
    } catch (InvalidArgumentException $e) {
        throw new Exception($e->getMessage());
    }

    // Cycle ORM
    try {
        $cycleConfig = Config::get("cycle");
        if ($cycleConfig) {
            $this->orm = CycleFactory::getORM();
            $this->orm->getFactory()->getDatabaseManager()->database(
                $cycleConfig["default"] ?? "default"
            )->getDriver()->getConnection()->getPDO();
        }
    } catch (\Throwable $e) {
        throw new Exception("Unable to connect with Cycle: " . $e->getMessage());
    }

    return $this;
}

public function complete(): static
{
    $this->db?->disconnect();
    CycleFactory::reset();
    Storage::removeTemp();

    return $this;
}
```

Pick one option and keep `System` consistent.
Cycle's `EntityManager` is disposable per request, so do not store it in `System`, create a new one in controllers via `new EntityManager($orm)`.

## Replacing the Base Model

`app/Models/Model.php:9` today wraps `DatabaseFactory` and exposes `orm():49`, `raw():102`, `all():84`.
With Cycle, entities are DataMappers, not query builders.
Provide a small abstract base that exposes the Cycle `ORM` and repository helpers.

Replace `app/Models/Model.php` with a Cycle-aware base, or keep it and add `app/Models/CycleModel.php` for new code.
The example below replaces it and keeps a compatibility shim.

```php
<?php
namespace App\Models;

use App\Core\CycleFactory;
use Cycle\ORM\ORM;
use Cycle\ORM\Select\Repository;

abstract class Model
{
    protected static function orm(): ORM
    {
        return CycleFactory::getORM();
    }

    protected static function repository(): Repository
    {
        $class = static::$entityClass ?? static::class;

        return static::orm()->getRepository($class);
    }

    /**
     * Override in child if entity class differs from model class
     */
    protected static string $entityClass = "";

    public static function findByPK(mixed $id): ?object
    {
        return static::repository()->findByPK($id);
    }

    public static function findOne(array $scope = []): ?object
    {
        return static::repository()->findOne($scope);
    }

    public static function findAll(array $scope = []): iterable
    {
        return static::repository()->findAll($scope);
    }

    public static function all(): iterable
    {
        return static::findAll();
    }

    /**
     * Low level select query for complex conditions
     */
    public static function query(): \Cycle\ORM\Select
    {
        return static::repository()->select();
    }
}
```

For side by side, do not replace `Model.php`.
Create `app/Models/CycleModel.php` with the same content and extend it only for Cycle entities.
Keep `User extends Model` working on `roolith/database` and make `Product extends CycleModel` for Cycle.

If your entity class lives in `App\Entities\Product` but you want a model facade in `App\Models\Product`, set the mapping:

```php
<?php
namespace App\Models;

use App\Entities\Product as ProductEntity;

class Product extends Model
{
    protected static string $entityClass = ProductEntity::class;
}
```

Otherwise put helpers directly on a custom repository.

Cycle repositories are read-only by design.
Writes always go through `Cycle\ORM\EntityManager`.

## Defining Entities

Create `app/Entities/Product.php`.
This mirrors the Cycle annotated entity example from the [installation guide](https://cycle-orm.dev/docs/intro-install/current/en).

```php
<?php
namespace App\Entities;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity(table: "products")]
class Product
{
    #[Column(type: "primary")]
    private int $id;

    #[Column(type: "string(255)")]
    private string $name;

    #[Column(type: "decimal(10,2)", nullable: true)]
    private ?string $price = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): void
    {
        $this->price = $price;
    }
}
```

Add relations with annotated attributes.

One to many and many to one:

```php
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity]
class User
{
    #[Column(type: "primary")]
    private int $id;

    #[Column(type: "string")]
    private string $email;

    #[HasMany(target: Post::class)]
    private array $posts = [];
}

#[Entity]
class Post
{
    #[Column(type: "primary")]
    private int $id;

    #[Column(type: "string")]
    private string $title;

    #[BelongsTo(target: User::class)]
    private ?User $user = null;
}
```

Other relations: `#[HasOne]`, `#[ManyToMany]`, `#[RefersTo]`, `#[Embedded]`.
See [Relations](https://cycle-orm.dev/docs/relation-has-many/current/en) and [Annotated Relations](https://cycle-orm.dev/docs/annotated-relations/current/en).

A UUID primary key with entity behavior:

```php
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity]
class Product
{
    #[Column(type: "uuid", primary: true)]
    private string $id;

    #[Column(type: "string")]
    private string $name;
}
```

Combine with `cycle/entity-behavior-uuid` and `#[GeneratedValue]`-like handling via `cycle/entity-behavior`.

Migrations note: when you add a nullable column, set `nullable: true` or a `default` so existing rows can migrate.

## Using Entities in Controllers

Cycle's write API is `EntityManager->persist()->run()` and `delete()->run()`.
Reads go through the repository.

### Create

```php
<?php
namespace App\Controllers;

use App\Core\CycleFactory;
use App\Entities\Product;
use Cycle\ORM\EntityManager;

class ProductController extends Controller
{
    public function store()
    {
        $orm = CycleFactory::getORM();
        $em = new EntityManager($orm);

        $product = new Product();
        $product->setName("Widget");
        $product->setPrice("19.99");

        $em->persist($product);
        $em->run();

        return $this->view("products/show", ["product" => $product]);
    }
}
```

### Read

```php
use App\Core\CycleFactory;
use App\Entities\Product;

$orm = CycleFactory::getORM();

// by primary key
$product = $orm->getRepository(Product::class)->findByPK(1);

// via base model helpers if you replaced Model.php
$product = Product::findByPK(1);
$allActive = Product::findAll(["status" => "active"]);
$one = Product::findOne(["name" => "Hadi"]);

// compound condition with operator
$cheap = $orm->getRepository(Product::class)->findAll([
    "price" => [">=" => 10]
]);

// low level select with where, order, limit, and relation loading
$products = $orm->getRepository(Product::class)
    ->select()
    ->where("price", ">", 10)
    ->orderBy("name", "ASC")
    ->limit(10)
    ->load("user")
    ->fetchAll();

// eager load with query
$usersWithOrders = $orm->getRepository(\App\Entities\User::class)
    ->select()
    ->where("active", true)
    ->load("posts", [
        "method" => \Cycle\ORM\Select::SINGLE_QUERY,
        "load" => function ($q) {
            $q->where("published", true)->orderBy("created_at", "DESC");
        }
    ])
    ->fetchAll();
```

Repositories are read-only, see [Select](https://cycle-orm.dev/docs/basic-select/current/en).
For custom finders, create a repository class and point `#[Entity(repository: MyRepo::class)]` to it.

### Update

```php
$orm = CycleFactory::getORM();
$em = new EntityManager($orm);

$product = $orm->getRepository(Product::class)->findByPK(1);
$product->setName("Habib Hadi");

$em->persist($product);
$em->run();
```

Cycle tracks changes via the Heap, you must still call `persist()` then `run()`.

### Delete

```php
$orm = CycleFactory::getORM();
$em = new EntityManager($orm);

$product = $orm->getRepository(Product::class)->findByPK(4);
if ($product) {
    $em->delete($product);
    $em->run();
}
```

### Transactions

`EntityManager->run()` wraps in a transaction by default.
For direct DBAL transactions:

```php
$orm = CycleFactory::getORM();
$db = $orm->getFactory()->getDatabaseManager()->database("default");

$db->transaction(function () use ($orm) {
    $em = new \Cycle\ORM\EntityManager($orm);
    $em->persist(new Product());
    $em->run();
});
```

### Pagination

Cycle has no built-in paginator, combine `limit()` / `offset()` with a count query.

```php
use App\Entities\Product;

$orm = CycleFactory::getORM();
$repo = $orm->getRepository(Product::class);

$page = max(1, (int) ($_GET["page"] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$total = $repo->select()->count();
$items = $repo->select()->orderBy("id")->limit($perPage)->offset($offset)->fetchAll();

$lastPage = (int) ceil($total / $perPage);
```

For a reusable helper, wrap this in a repository method or a paginator utility.
See [Pagination](https://cycle-orm.dev/docs/advanced-pagination/current/en).

## Schema and Migrations

### SyncTables for Development

With `syncTables => true` in `config/config.php`, the `SyncTables` generator will create and alter tables on every bootstrap.
This is convenient in development but must be disabled in production.

Clear the schema cache after changing entities:

```bash
rm storage/cache/cycle-schema.php
```

### Migrations for Production

Install the migrator:

```bash
composer require cycle/migrations
```

Create a console script `bin/cycle` (make it executable).

```php
#!/usr/bin/env php
<?php
declare(strict_types=1);

const APP_ROOT = __DIR__ . "/..";

require APP_ROOT . "/vendor/autoload.php";
require APP_ROOT . "/app/Utils/functions.php";

use App\Core\CycleFactory;

$orm = CycleFactory::getORM();
$dbal = $orm->getFactory()->getDatabaseManager();

// Simple schema dump command
if (($argv[1] ?? null) === "schema:render") {
    $renderer = new \Cycle\Schema\Renderer\SchemaRenderer();
    $schema = $orm->getSchema();
    echo $renderer->render($schema) . PHP_EOL;
    exit(0);
}

echo "Cycle ORM ready. DB: " . $dbal->database("default")->getName() . PHP_EOL;
```

For full migration workflow, use `cycle/migrations` with a migrator config:

```php
use Cycle\Migrations\Migrator;
use Cycle\Migrations\Config\MigrationConfig;

$config = new MigrationConfig([
    "directory" => APP_ROOT . "/migrations/",
    "table" => "migrations",
    "safe" => true,
]);

$migrator = new Migrator($config, $dbal);
$migrator->configure();

// generate from diff
// $migrator->run()  // apply pending
```

See [Migrations](https://cycle-orm.dev/docs/database-migrations/current/en) and [Synchronizing Database Schema](https://cycle-orm.dev/docs/advanced-sync-schema/current/en).
You can keep Roolith migrations and Cycle migrations side by side in different directories.

### Debugging the Schema

Dump the compiled schema with `cycle/schema-renderer`:

```php
$schemaArray = CycleFactory::getORM()->getSchema();
$renderer = new \Cycle\Schema\Renderer\SchemaRenderer();
print_r($renderer->render($schemaArray));
```

Or add a temporary route:

```php
$router->get("/debug/cycle-schema", function () {
    $orm = \App\Core\CycleFactory::getORM();
    return "<pre>" . print_r($orm->getSchema(), true) . "</pre>";
});
```

## Keeping Both ORMs

If you run side by side, keep naming clear.

- `App\Models\User` extends `App\Models\Model` for `roolith/database` (uses `DatabaseFactory`).
- `App\Entities\Product` is a Cycle entity (uses `CycleFactory`).
- Or add `App\Models\CycleModel` as the Cycle base and keep `App\Models\Model` untouched.

Do not mix `Model::orm()` and `Cycle\ORM\ORM` in the same class.
Wrap cross-ORM writes in a shared DB transaction if you write to both in one request via the same PDO connection.

```php
$cycleOrm = CycleFactory::getORM();
$db = $cycleOrm->getFactory()->getDatabaseManager()->database("default");
$pdo = $db->getDriver()->getConnection()->getPDO();

$pdo->beginTransaction();
try {
    $em = new \Cycle\ORM\EntityManager($cycleOrm);
    $em->persist($product);
    $em->run();

    \App\Models\LegacyLog::raw()->table("logs")->insert(["message" => "created product"]);

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    throw $e;
}
```

## Notes

- Set `schemaCache` to `storage/cache/cycle-schema.php` and ensure the directory is writable and gitignored.
- In development keep `syncTables` true so schema changes apply automatically.
- In production set `syncTables` false, commit the cached schema or generate it during deploy, and use `cycle/migrations`.
- Cycle entities should not contain request or session logic, keep helpers like `User::current():28` in `app/Models` or `app/Misc` and load the user via the repository.
- Attributes require PHP 8.1 or newer for `cycle/annotated` 4.x, which matches Roolith 4.0 requirements when on PHP 8.1.
- On PHP 8.0 lock `cycle/annotated` to `^3.0`.
- Cycle's `EntityManager` is disposable, create a new one per request or per unit of work, do not reuse across requests in long-running apps.
- If you fully replace the ORM, update existing calls to `Model::orm()`, `Model::raw()`, `Model::all():84`, `where()`, `insert()`, `update()`, `delete()`, and `paginate()` to the Cycle `EntityManager` and repository equivalents above.
- Test the wiring with a simple route that returns `CycleFactory::getORM()->getRepository(\App\Entities\Product::class)->select()->count()` as a sanity check after bootstrap.
- For UUIDs and auto timestamps, install `cycle/entity-behavior` and `cycle/entity-behavior-uuid`, see [Entity Behaviors](https://cycle-orm.dev/docs/entity-behaviors-install/current/en).
