# Custom ORM (Cycle)

Roolith ships with [roolith/database](https://github.com/im4aLL/roolith-database) and a thin `App\Models\Model` wrapper, see [Models](/models). You only need this page if you want to replace it with [Cycle ORM](https://github.com/cycle/orm) ([docs](https://cycle-orm.dev/docs/intro-install/current/en)).

> Do you need this? For most apps, no. Keep the built-in query builder for simple CRUD. Switch to Cycle if you want DataMapper entities with attributes, compiled schema, and clean `EntityManager` writes.

## How the swap works

Cycle is a DataMapper: entities are plain PHP objects, the schema is compiled from attributes, and writes go through `Cycle\ORM\EntityManager`. Three things change, in this order:

1. `config/config.php` gains a `cycle` key with connection details.
2. A new `app/Core/CycleFactory.php` owns the single `ORM` instance, mirroring `DatabaseFactory`.
3. `app/Core/System.php` boots it on each request and resets it afterwards, and your models become entities instead of query builders.

Your controllers keep the same shape: load something, pass it to `$this->view()`. Only the data-access calls change.

Decide one thing up front:

- **Side by side (recommended for existing apps):** keep `database` for old models, add `cycle` for new entities.
- **Full replacement (for new apps):** set `database` to `null` and use only Cycle.

## Step 1 - Install Cycle

```bash
composer require cycle/orm cycle/database cycle/annotated spiral/tokenizer symfony/finder
```

MySQL needs `pdo_mysql`, Postgres `pdo_pgsql`, SQLite `pdo_sqlite`. You will likely want `cycle/migrations` later for production.

## Step 2 - Add connection config

Add a `cycle` key to `config/config.php`. Keep `database` for side by side, set it to `null` for full replacement:

```php
<?php
return [
    "baseUrl" => "http://localhost:8080/",
    // side by side: keep your existing array here
    // full replacement: set "database" => null,
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
        "entityPaths" => [APP_ROOT . "/app/Entities"],
        "schemaCache" => APP_ROOT . "/storage/cache/cycle-schema.php",
        "syncTables" => !isProductionEnvironment(),
    ],
];
```

Keep `syncTables` on while developing so tables follow your attributes automatically. Turn it off in production and use migrations instead. Other drivers follow the same shape, see [Connect to Database](https://cycle-orm.dev/docs/database-connect/current/en).

## Step 3 - Add one factory

Create `app/Core/CycleFactory.php`. Copy this once, you rarely touch it again. It builds the `DatabaseManager`, compiles the schema from attributes, and returns the `ORM` singleton:

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
        self::getORM();

        return self::$dbal;
    }

    private static function createORM(): ORM
    {
        $cfg = Config::get("cycle");

        if (!$cfg) {
            throw new \RuntimeException("Missing `cycle` config in config/config.php");
        }

        $dbal = new DatabaseManager(new DatabaseConfig(
            self::buildDbalConfig($cfg)
        ));

        self::$dbal = $dbal;

        $entityPaths = $cfg["entityPaths"] ?? [APP_ROOT . "/app/Entities"];
        $schemaCache = $cfg["schemaCache"] ?? APP_ROOT . "/storage/cache/cycle-schema.php";
        $syncTables = $cfg["syncTables"] ?? !isProductionEnvironment();

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
                if (isset($conn["connection"]) && str_starts_with($conn["connection"], "sqlite:")) {
                    $connections[$name] = new \Cycle\Database\Config\SQLiteDriverConfig(
                        connection: new \Cycle\Database\Config\SQLite\DsnConnectionConfig(dsn: $conn["connection"]),
                        queryCache: $conn["queryCache"] ?? true
                    );
                } elseif (($conn["database"] ?? null) === ":memory:") {
                    $connections[$name] = new \Cycle\Database\Config\SQLiteDriverConfig(
                        connection: new \Cycle\Database\Config\SQLite\MemoryConnectionConfig(),
                        queryCache: $conn["queryCache"] ?? true
                    );
                } else {
                    $connections[$name] = new \Cycle\Database\Config\SQLiteDriverConfig(
                        connection: new \Cycle\Database\Config\SQLite\FileConnectionConfig(
                            database: $conn["database"] ?? APP_ROOT . "/storage/database.sqlite"
                        ),
                        queryCache: $conn["queryCache"] ?? true
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

Controllers and models now call `CycleFactory::getORM()` where they used to call `DatabaseFactory::getInstance()`.

## Step 4 - Boot it in `System.php`

You only add a connect and a reset. Everything else in `System` (`Env`, config validation, session, security headers) stays unchanged.

In `bootstrap()`, after the existing setup:

```php
$cycleConfig = Config::get("cycle");
if ($cycleConfig) {
    $this->orm = CycleFactory::getORM();
    // trigger connection early to surface config errors
    $this->orm->getFactory()->getDatabaseManager()->database(
        $cycleConfig["default"] ?? "default"
    )->getDriver()->getConnection()->getPDO();
}
```

In `complete()`, before temp cleanup:

```php
CycleFactory::reset();
```

Add a `protected ?ORM $orm = null;` property to hold it. For side by side, keep the existing `$db` property and legacy connect block untouched. For full replacement, set `"database" => null` so the old path skips connecting.

Cycle's `EntityManager` is disposable per request, so do not store it in `System`. Create a new one in controllers with `new EntityManager($orm)`.

Sanity check with a temporary route:

```php
return CycleFactory::getORM()->getRepository(\App\Entities\Product::class)->select()->count();
```

## Step 5 - Define an entity

Create `app/Entities/Product.php`:

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

Relations are attributes too:

```php
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity]
class User
{
    #[Column(type: "primary")]
    private int $id;

    #[HasMany(target: Post::class)]
    private array $posts = [];
}

#[Entity]
class Post
{
    #[Column(type: "primary")]
    private int $id;

    #[BelongsTo(target: User::class)]
    private ?User $user = null;
}
```

See [Relations](https://cycle-orm.dev/docs/relation-has-many/current/en) when you need more.

## Step 6 - Use it in a controller

Reads go through the repository, writes through `persist()->run()`. This replaces `User::orm()->where(...)->get()`:

```php
<?php
namespace App\Controllers;

use App\Core\CycleFactory;
use App\Entities\Product;
use Cycle\ORM\EntityManager;

class ProductController extends Controller
{
    // Create
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

    // Read
    public function show(int $id)
    {
        $orm = CycleFactory::getORM();
        $product = $orm->getRepository(Product::class)->findByPK($id);

        return $this->view("products/show", ["product" => $product]);
    }

    // Update
    public function update(int $id)
    {
        $orm = CycleFactory::getORM();
        $em = new EntityManager($orm);

        $product = $orm->getRepository(Product::class)->findByPK($id);
        $product->setName("Habib Hadi");

        $em->persist($product);
        $em->run();

        return $this->view("products/show", ["product" => $product]);
    }

    // Delete
    public function destroy(int $id)
    {
        $orm = CycleFactory::getORM();
        $em = new EntityManager($orm);

        $product = $orm->getRepository(Product::class)->findByPK($id);

        if ($product) {
            $em->delete($product);
            $em->run();
        }
    }
}
```

Queries beyond primary key:

```php
$orm = CycleFactory::getORM();
$repo = $orm->getRepository(Product::class);

// filtered read
$active = $repo->findAll(["status" => "active"]);

// select with conditions, ordering, and relation loading
$products = $repo->select()
    ->where("price", ">", 10)
    ->orderBy("name", "ASC")
    ->limit(10)
    ->load("user")
    ->fetchAll();

// pagination: limit plus offset plus a count
$page = max(1, (int) ($_GET["page"] ?? 1));
$perPage = 15;
$total = $repo->select()->count();
$items = $repo->select()->orderBy("id")->limit($perPage)->offset(($page - 1) * $perPage)->fetchAll();
```

Repositories are read-only by design, see [Select](https://cycle-orm.dev/docs/basic-select/current/en). For custom finders, point `#[Entity(repository: MyRepo::class)]` at your own class.

## Optional - A `Model` base for familiar helpers

Skip this if calling the repository directly feels fine. If you miss `Product::find(1)` and `Product::all()`, add a small base class. For side by side, name it `CycleModel` and leave `Model.php` untouched:

```php
<?php
namespace App\Models;

use App\Core\CycleFactory;
use Cycle\ORM\ORM;

abstract class CycleModel
{
    protected static string $entityClass = "";

    protected static function findByPK(mixed $id): ?object
    {
        $class = static::$entityClass ?: static::class;

        return CycleFactory::getORM()->getRepository($class)->findByPK($id);
    }

    protected static function findAll(array $scope = []): iterable
    {
        $class = static::$entityClass ?: static::class;

        return CycleFactory::getORM()->getRepository($class)->findAll($scope);
    }
}
```

```php
class Product extends CycleModel
{
    protected static string $entityClass = \App\Entities\Product::class;
}
```

## Going further

- **Schema in development:** with `syncTables` on, tables follow your attributes on each bootstrap. After changing entities, delete `storage/cache/cycle-schema.php` if reads look stale.
- **Migrations in production:** set `syncTables` to false, commit the cached schema during deploy, and use [Migrations](https://cycle-orm.dev/docs/database-migrations/current/en) with `cycle/migrations`. Keep Roolith migrations in `database/migrations` and Cycle migrations in a separate directory.
- **Debugging the schema:** install `cycle/schema-renderer` and dump with `SchemaRenderer`, or add a temporary `/debug/cycle-schema` route that prints `CycleFactory::getORM()->getSchema()`.
- **Same pattern, other ORMs:** the factory plus `bootstrap()` / `complete()` wiring is identical for Doctrine or Eloquent. The factory returns the manager, the base model exposes it. See [Custom ORM (Doctrine)](/custom-orm).
- **Keep entities clean:** no request or session logic inside entities. Keep helpers like `User::current()` in `app/Models` or `app/Misc` and load the user via the repository.
- **Fully replacing?** Update old calls to `Model::orm()`, `Model::raw()`, `where()`, `insert()`, `update()`, and `delete()` to the `EntityManager` and repository equivalents above.
