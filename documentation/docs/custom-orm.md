# Using a Custom ORM (Doctrine)

Roolith ships with [roolith/database](https://github.com/im4aLL/roolith-database) and a thin `App\Models\Model` wrapper, see [Models](/models). You only need this page if you want to replace it with [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/current/tutorials/getting-started.html).

> Do you need this? For most apps, no. Keep the built-in query builder for simple CRUD. Switch to Doctrine if you want entities with relations, DQL, and migrations managed from attribute mappings.

## How the swap works

Three things change, in this order:

1. `config/config.php` gains a `doctrine` key with connection details.
2. A new `app/Core/DoctrineFactory.php` owns the single `EntityManager`, mirroring `DatabaseFactory`.
3. `app/Core/System.php` boots it on each request and closes it afterwards, and your models become entities instead of query builders.

Your controllers keep the same shape: load something, pass it to `$this->view()`. Only the data-access calls change.

Decide one thing up front:

- **Side by side (recommended for existing apps):** keep `database` for old models, add `doctrine` for new entities.
- **Full replacement (for new apps):** set `database` to `null` and use only Doctrine.

## Step 1 - Install Doctrine

```bash
composer require doctrine/orm symfony/cache
```

MySQL needs `pdo_mysql`. SQLite in tests needs `pdo_sqlite`.

## Step 2 - Add connection config

Add a `doctrine` key to `config/config.php`. Keep `database` for side by side, set it to `null` for full replacement:

```php
<?php
return [
    "baseUrl" => "http://localhost:8080/",
    // side by side: keep your existing array here
    // full replacement: set "database" => null,
    "database" => null,

    "doctrine" => [
        "driver" => "pdo_mysql",
        "host" => "localhost",
        "port" => 3306,
        "dbname" => "roolith_cms",
        "user" => "root",
        "password" => "",
        "charset" => "utf8mb4",
        "entityPaths" => [APP_ROOT . "/app/Entities"],
        "proxyDir" => APP_ROOT . "/storage/proxies",
        "isDevMode" => !isProductionEnvironment(),
    ],
];
```

`isProductionEnvironment()` lives in `app/Utils/functions.php`. Keep `isDevMode` on while developing so metadata rebuilds each request. Turn it off in production and make `proxyDir` writable.

## Step 3 - Add one factory

Create `app/Core/DoctrineFactory.php`. Copy this once, you rarely touch it again:

```php
<?php
namespace App\Core;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Roolith\Configuration\Config;

class DoctrineFactory
{
    private static ?EntityManager $em = null;

    private function __construct() {}

    public static function getInstance(): EntityManager
    {
        if (self::$em === null) {
            $cfg = Config::get("doctrine");

            if (!$cfg) {
                throw new \RuntimeException("Missing `doctrine` config in config/config.php");
            }

            $isDevMode = $cfg["isDevMode"] ?? !isProductionEnvironment();
            $paths = $cfg["entityPaths"] ?? [APP_ROOT . "/app/Entities"];
            $proxyDir = $cfg["proxyDir"] ?? APP_ROOT . "/storage/proxies";

            if (!is_dir($proxyDir)) {
                mkdir($proxyDir, 0777, true);
            }

            $ormConfig = ORMSetup::createAttributeMetadataConfiguration(
                paths: $paths,
                isDevMode: $isDevMode,
                proxyDir: $proxyDir
            );

            $connection = DriverManager::getConnection([
                "driver" => $cfg["driver"] ?? "pdo_mysql",
                "host" => $cfg["host"] ?? "localhost",
                "port" => $cfg["port"] ?? 3306,
                "dbname" => $cfg["dbname"] ?? "",
                "user" => $cfg["user"] ?? "root",
                "password" => $cfg["password"] ?? "",
                "charset" => $cfg["charset"] ?? "utf8mb4",
            ], $ormConfig);

            self::$em = new EntityManager($connection, $ormConfig);
        }

        return self::$em;
    }

    public static function reset(): void
    {
        if (self::$em !== null && self::$em->isOpen()) {
            self::$em->close();
        }

        self::$em = null;
    }
}
```

On Doctrine 2.14 and below, use `Doctrine\ORM\Tools\Setup::createAttributeMetadataConfiguration` instead of `ORMSetup`. The rest is identical.

Controllers and models now call `DoctrineFactory::getInstance()` where they used to call `DatabaseFactory::getInstance()`.

## Step 4 - Boot it in `System.php`

You only add a connect and a disconnect. Everything else in `System` (`Env`, config validation, session, security headers) stays unchanged.

In `bootstrap()`, after the existing setup:

```php
$doctrineConfig = Config::get("doctrine");
if ($doctrineConfig) {
    $this->em = DoctrineFactory::getInstance();
    $this->em->getConnection()->connect();
}
```

In `complete()`, before temp cleanup:

```php
if ($this->em !== null && $this->em->isOpen()) {
    $this->em->flush();
    $this->em->close();
}
DoctrineFactory::reset();
```

Add a `protected ?EntityManager $em = null;` property to hold it. For side by side, keep the existing `$db` property and legacy connect block untouched. For full replacement, set `"database" => null` so the old path skips connecting.

Sanity check with a temporary route:

```php
return DoctrineFactory::getInstance()->getConnection()->isConnected();
```

## Step 5 - Define an entity

Create `app/Entities/Product.php`:

```php
<?php
namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity]
#[ORM\Table(name: "products")]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): self
    {
        $this->price = $price;

        return $this;
    }
}
```

Relations are attributes too. See [Association Mapping](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/association-mapping.html) when you need them:

```php
#[ORM\ManyToOne(targetEntity: User::class)]
#[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
private User $user;
```

## Step 6 - Use it in a controller

The pattern replaces `User::orm()->where(...)->get()`. Create reads through the manager, updates just mutate and flush:

```php
<?php
namespace App\Controllers;

use App\Core\DoctrineFactory;
use App\Entities\Product;

class ProductController extends Controller
{
    // Create
    public function store()
    {
        $em = DoctrineFactory::getInstance();

        $product = new Product();
        $product->setName("Widget");
        $product->setPrice("19.99");

        $em->persist($product);
        $em->flush();

        return $this->view("products/show", ["product" => $product]);
    }

    // Read
    public function show(int $id)
    {
        $em = DoctrineFactory::getInstance();
        $product = $em->find(Product::class, $id);

        return $this->view("products/show", ["product" => $product]);
    }

    // Update: no explicit update() call, just mutate and flush
    public function update(int $id)
    {
        $em = DoctrineFactory::getInstance();
        $product = $em->find(Product::class, $id);
        $product->setName("Habib Hadi");
        $em->flush();

        return $this->view("products/show", ["product" => $product]);
    }

    // Delete
    public function destroy(int $id)
    {
        $em = DoctrineFactory::getInstance();
        $product = $em->find(Product::class, $id);

        if ($product) {
            $em->remove($product);
            $em->flush();
        }
    }
}
```

Queries beyond `find()`:

```php
$em = DoctrineFactory::getInstance();

// DQL
$products = $em->createQuery("SELECT p FROM App\Entities\Product p WHERE p.price > :price")
    ->setParameter("price", 10)
    ->getResult();

// QueryBuilder
$products = $em->createQueryBuilder()
    ->select("p")->from(Product::class, "p")
    ->where("p.name LIKE :name")->setParameter("name", "%Hadi%")
    ->getQuery()->getResult();

// Pagination
$query = $em->createQuery("SELECT p FROM App\Entities\Product p ORDER BY p.id ASC")
    ->setFirstResult(0)->setMaxResults(10);
$paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query, fetchJoinCollection: true);
```

## Optional - A `Model` base for familiar helpers

Skip this if calling the `EntityManager` directly feels fine. If you miss `Product::find(1)` and `Product::all()`, add a small base class. For side by side, name it `DoctrineModel` and leave `Model.php` untouched:

```php
<?php
namespace App\Models;

use App\Core\DoctrineFactory;
use Doctrine\ORM\EntityManager;

abstract class DoctrineModel
{
    protected static string $entityClass = "";

    protected static function em(): EntityManager
    {
        return DoctrineFactory::getInstance();
    }

    public static function find(mixed $id): ?object
    {
        $class = static::$entityClass ?: static::class;

        return static::em()->find($class, $id);
    }

    public static function all(): array
    {
        $class = static::$entityClass ?: static::class;

        return static::em()->getRepository($class)->findAll();
    }
}
```

```php
class Product extends DoctrineModel
{
    protected static string $entityClass = \App\Entities\Product::class;
}
```

## Going further

- **Schema:** add a `bin/doctrine` console script with `Doctrine\ORM\Tools\Console\ConsoleRunner::run()`, then `orm:schema-tool:create`, `orm:schema-tool:update --force --dump-sql`, and `orm:validate-schema`. In production prefer [Doctrine Migrations](https://www.doctrine-project.org/projects/doctrine-migrations/en/current/index.html) (`migrations:diff`, `migrations:migrate`).
- **Caching:** in production set `isDevMode` to false, warm up proxies with `orm:generate-proxies`, and configure a metadata cache like Redis.
- **Same pattern, other ORMs:** the factory plus `bootstrap()` / `complete()` wiring is identical for Eloquent or Cycle. The factory returns the manager, the base model exposes it. See [Custom ORM (Cycle)](/cycle-orm).
- **Keep entities clean:** no request or session logic inside entities. Keep helpers like `User::current()` in `app/Models` or `app/Misc` and load the user via the `EntityManager`.
- **Fully replacing?** Update old calls to `Model::orm()`, `Model::raw()`, `where()`, `insert()`, `update()`, and `delete()` to the `EntityManager` equivalents above.
