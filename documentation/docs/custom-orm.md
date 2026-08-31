# Using a Custom ORM (Doctrine)

Roolith ships with [roolith/database](https://github.com/im4aLL/roolith-database) and a thin `App\Models\Model` wrapper.
You can keep it, run side by side, or replace it entirely with a full ORM such as [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/current/tutorials/getting-started.html).
This recipe shows the replacement using Doctrine ORM 3 with PHP 8 attributes.

It touches the three places where Roolith wires the database, verified in this codebase.

- `app/Models/Model.php:1` - base model that returns `DatabaseFactory::getInstance()` and exposes `orm()` / `raw()`.
- `app/Core/DatabaseFactory.php:1` - singleton that creates `Roolith\Store\Database`.
- `app/Core/System.php:39` - `bootstrap()` reads `config/config.php` key `database` and calls `connectToDatabase()`, `complete()` calls `disconnectFromDatabase()`.

You will replace or augment these three files.
Pick full replacement if you want only Doctrine, or side by side if you want to keep the query builder for migrations or legacy code.

## Installation

Install Doctrine ORM and a cache implementation.
Doctrine 3 needs `symfony/cache` for metadata caching.

```bash
composer require doctrine/orm symfony/cache
```

If you use MySQL, `pdo_mysql` must be enabled.
For SQLite in tests, `pdo_sqlite` is enough.

## Configuration

Doctrine needs connection params and metadata paths.
Add a `doctrine` key to `config/config.php` and keep or null out the legacy `database` key.

Full replacement - disable Roolith database:

```php
<?php
return [
    "baseUrl" => "http://localhost:8080/",
    "viteDevServer" => "",
    "database" => null,

    "doctrine" => [
        "driver" => "pdo_mysql",
        "host" => "localhost",
        "port" => 3306,
        "dbname" => "roolith_cms",
        "user" => "root",
        "password" => "",
        "charset" => "utf8mb4",
        // where your entities live
        "entityPaths" => [APP_ROOT . "/app/Entities"],
        "proxyDir" => APP_ROOT . "/storage/proxies",
        "isDevMode" => !isProductionEnvironment(),
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
"doctrine" => [
    "driver" => "pdo_mysql",
    "host" => "localhost",
    "port" => 3306,
    "dbname" => "roolith_cms",
    "user" => "root",
    "password" => "",
    "entityPaths" => [APP_ROOT . "/app/Entities"],
    "proxyDir" => APP_ROOT . "/storage/proxies",
    "isDevMode" => !isProductionEnvironment(),
],
```

`isProductionEnvironment()` is defined in `app/Utils/functions.php`.
When `isDevMode` is true, Doctrine rebuilds metadata and proxies on every request.
In production set it to false and ensure `proxyDir` is writable.

## Creating an EntityManager Factory

Mirror `DatabaseFactory` but for Doctrine.
Create `app/Core/DoctrineFactory.php`.

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
            self::$em = self::createEntityManager();
        }

        return self::$em;
    }

    private static function createEntityManager(): EntityManager
    {
        $doctrineConfig = Config::get("doctrine");

        if (!$doctrineConfig) {
            throw new \RuntimeException("Missing `doctrine` config in config/config.php");
        }

        $isDevMode = $doctrineConfig["isDevMode"] ?? !isProductionEnvironment();
        $paths = $doctrineConfig["entityPaths"] ?? [APP_ROOT . "/app/Entities"];
        $proxyDir = $doctrineConfig["proxyDir"] ?? APP_ROOT . "/storage/proxies";

        if (!is_dir($proxyDir)) {
            mkdir($proxyDir, 0777, true);
        }

        $ormConfig = ORMSetup::createAttributeMetadataConfiguration(
            paths: $paths,
            isDevMode: $isDevMode,
            proxyDir: $proxyDir
        );

        $connectionParams = [
            "driver" => $doctrineConfig["driver"] ?? "pdo_mysql",
            "host" => $doctrineConfig["host"] ?? "localhost",
            "port" => $doctrineConfig["port"] ?? 3306,
            "dbname" => $doctrineConfig["dbname"] ?? $doctrineConfig["name"] ?? "",
            "user" => $doctrineConfig["user"] ?? "root",
            "password" => $doctrineConfig["password"] ?? $doctrineConfig["pass"] ?? "",
            "charset" => $doctrineConfig["charset"] ?? "utf8mb4",
        ];

        // For a single DSN string, pass it as `url` or handle via DriverManager::getConnection string form.
        $connection = DriverManager::getConnection($connectionParams, $ormConfig);

        return new EntityManager($connection, $ormConfig);
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

### Why a Factory

`System::bootstrap()` needs a single place to connect and `System::complete()` needs a single place to close.
The factory gives you `getInstance()` like `DatabaseFactory::getInstance():15` does today.
Controllers and models then call `DoctrineFactory::getInstance()` instead of `DatabaseFactory::getInstance()`.

For Doctrine 2.14 and below, replace `ORMSetup::createAttributeMetadataConfiguration` with `Doctrine\ORM\Tools\Setup::createAttributeMetadataConfiguration`.
The rest is identical.

## Wiring It Into System.php

### Option A - Full Replacement

Disable the legacy connection and bootstrap Doctrine instead.
Edit `app/Core/System.php`.

```php
<?php
namespace App\Core;

use App\Core\Exceptions\Exception;
use Doctrine\ORM\EntityManager;
use Roolith\Configuration\Config;
use Roolith\Configuration\Exception\InvalidArgumentException;
use Roolith\Route\Interfaces\RouterInterface;

class System
{
    protected ?EntityManager $em = null;

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

        // Doctrine replaces the DatabaseFactory connection
        try {
            $doctrineConfig = Config::get("doctrine");
            if ($doctrineConfig) {
                $this->em = DoctrineFactory::getInstance();
                // trigger connection early to surface config errors
                $this->em->getConnection()->connect();
            }
        } catch (InvalidArgumentException $e) {
            throw new Exception($e->getMessage());
        } catch (\Exception $e) {
            throw new Exception("Unable to connect with Doctrine: " . $e->getMessage());
        }

        return $this;
    }

    public function complete(): static
    {
        if ($this->em !== null && $this->em->isOpen()) {
            $this->em->flush();
            $this->em->close();
        }

        DoctrineFactory::reset();
        Storage::removeTemp();

        return $this;
    }

    // processRequest(), router(), preProcessor(), registerCustomError() stay unchanged
}
```

Set `"database" => null` in `config/config.php` so the old `connectToDatabase():105` path is not needed.
If you keep the method for reference, make it a no-op or remove it.

### Option B - Side by Side

Keep Roolith database for existing models and add Doctrine for new ones.
Keep `protected ?DatabaseInterface $db` and add `protected ?EntityManager $em`.

```php
protected ?DatabaseInterface $db = null;
protected ?EntityManager $em = null;

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

    // Doctrine
    try {
        $doctrineConfig = Config::get("doctrine");
        if ($doctrineConfig) {
            $this->em = DoctrineFactory::getInstance();
            $this->em->getConnection()->connect();
        }
    } catch (\Exception $e) {
        throw new Exception("Unable to connect with Doctrine: " . $e->getMessage());
    }

    return $this;
}

public function complete(): static
{
    $this->db?->disconnect();

    if ($this->em !== null && $this->em->isOpen()) {
        $this->em->flush();
        $this->em->close();
    }
    DoctrineFactory::reset();
    Storage::removeTemp();

    return $this;
}
```

Pick one option and keep `System` consistent.
Do not flush inside `complete()` if you prefer explicit flushes in controllers, in that case just close.

## Replacing the Base Model

`app/Models/Model.php:9` today wraps `DatabaseFactory` and exposes `orm():49`, `raw():102`, `all():84`.
With Doctrine, models are entities, not query builders.
Provide a small abstract base that exposes the `EntityManager` and repository helpers.

Replace `app/Models/Model.php` with a Doctrine-aware base, or keep it and add `app/Models/DoctrineModel.php` for new code.
The example below replaces it and keeps a compatibility shim.

```php
<?php
namespace App\Models;

use App\Core\DoctrineFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

abstract class Model
{
    /**
     * Get the Doctrine EntityManager
     */
    protected static function em(): EntityManager
    {
        return DoctrineFactory::getInstance();
    }

    /**
     * Get repository for the called class
     *
     * Override $entityClass in child if entity class differs from model class
     */
    protected static string $entityClass = "";

    protected static function repository(): EntityRepository
    {
        $class = static::$entityClass ?: static::class;

        return static::em()->getRepository($class);
    }

    /**
     * Find by primary key
     */
    public static function find(mixed $id): ?object
    {
        return static::repository()->find($id);
    }

    /**
     * Find all
     */
    public static function all(): array
    {
        return static::repository()->findAll();
    }

    /**
     * Find by criteria
     */
    public static function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return static::repository()->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Find one by criteria
     */
    public static function findOneBy(array $criteria): ?object
    {
        return static::repository()->findOneBy($criteria);
    }

    /**
     * Persist and flush an entity
     */
    protected static function persist(object $entity): void
    {
        static::em()->persist($entity);
        static::em()->flush();
    }

    /**
     * Remove and flush an entity
     */
    protected static function remove(object $entity): void
    {
        static::em()->remove($entity);
        static::em()->flush();
    }
}
```

For side by side, do not replace `Model.php`.
Create `app/Models/DoctrineModel.php` with the same content and extend it only for Doctrine entities.
Keep `User extends Model` working on `roolith/database` and make `Product extends DoctrineModel` for Doctrine.

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

Otherwise put the helper methods directly on the entity repository or use a dedicated repository class.

## Defining Entities

Create `app/Entities/Product.php`.
This mirrors the Doctrine Getting Started `Product` example.

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

Add associations as needed:

```php
#[ORM\ManyToOne(targetEntity: User::class, inversedBy: "products")]
#[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
private User $user;

#[ORM\OneToMany(targetEntity: Product::class, mappedBy: "user", cascade: ["persist", "remove"])]
private \Doctrine\Common\Collections\Collection $products;
```

Initialize collections in the constructor:

```php
public function __construct()
{
    $this->products = new \Doctrine\Common\Collections\ArrayCollection();
}
```

Follow [Association Mapping](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/association-mapping.html) for owning and inverse sides.

## Using Entities in Controllers

Controllers call the base model or the EntityManager directly.
No `User::orm()->where(...)->get()` is needed.

### Create

```php
<?php
namespace App\Controllers;

use App\Core\DoctrineFactory;
use App\Entities\Product;

class ProductController extends Controller
{
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
}
```

### Read

```php
use App\Entities\Product;
use App\Core\DoctrineFactory;

$em = DoctrineFactory::getInstance();

// by primary key
$product = $em->find(Product::class, 1);

// via base model helpers if you replaced Model.php
$product = Product::find(1);
$all = Product::all();
$cheap = Product::findBy(["price" => "19.99"], ["name" => "ASC"], 10);

// DQL
$dql = "SELECT p FROM App\Entities\Product p WHERE p.price > :price ORDER BY p.name ASC";
$query = $em->createQuery($dql)->setParameter("price", 10);
$products = $query->getResult();

// QueryBuilder
$qb = $em->createQueryBuilder();
$products = $qb->select("p")
    ->from(Product::class, "p")
    ->where("p.name LIKE :name")
    ->setParameter("name", "%Hadi%")
    ->getQuery()
    ->getResult();

// array hydration for APIs
$rows = $em->createQuery("SELECT p FROM App\Entities\Product p")->getArrayResult();
```

### Update

```php
$em = DoctrineFactory::getInstance();
$product = $em->find(Product::class, 1);
$product->setName("Habib Hadi");
$em->flush();
```

Doctrine tracks changes, no explicit `update()` call is needed.
Just mutate the entity and flush.

### Delete

```php
$em = DoctrineFactory::getInstance();
$product = $em->find(Product::class, 4);
if ($product) {
    $em->remove($product);
    $em->flush();
}
```

### Pagination

Doctrine ships with `Doctrine\ORM\Tools\Pagination\Paginator`.

```php
use Doctrine\ORM\Tools\Pagination\Paginator;

$dql = "SELECT p FROM App\Entities\Product p ORDER BY p.id ASC";
$query = $em->createQuery($dql)
    ->setFirstResult(0)
    ->setMaxResults(10);

$paginator = new Paginator($query, fetchJoinCollection: true);
$total = count($paginator);

foreach ($paginator as $product) {
    echo $product->getName();
}
```

## Generating the Database Schema

Doctrine can create or update the schema from your attribute mappings.
Create a console script `bin/doctrine` (make it executable).

```php
#!/usr/bin/env php
<?php
require_once __DIR__ . "/../vendor/autoload.php";

const APP_ROOT = __DIR__ . "/..";
require_once APP_ROOT . "/app/Utils/functions.php";

use App\Core\DoctrineFactory;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;

$entityManager = DoctrineFactory::getInstance();

ConsoleRunner::run(new SingleManagerProvider($entityManager));
```

Then run:

```bash
chmod +x bin/doctrine
php bin/doctrine orm:schema-tool:create
php bin/doctrine orm:schema-tool:update --force --dump-sql
php bin/doctrine orm:validate-schema
```

For production, use [Doctrine Migrations](https://www.doctrine-project.org/projects/doctrine-migrations/en/current/index.html) instead of `schema-tool:update`.

```bash
composer require doctrine/migrations
php bin/doctrine migrations:diff
php bin/doctrine migrations:migrate
```

You can keep Roolith migrations in `app/Migrations` and Doctrine migrations in `migrations/` if you run side by side.

## Keeping Both ORMs

If you run side by side, keep naming clear.

- `App\Models\User` extends `App\Models\Model` for `roolith/database` (uses `DatabaseFactory`).
- `App\Entities\Product` is a Doctrine entity (uses `DoctrineFactory`).
- Or add `App\Models\DoctrineModel` as the Doctrine base and keep `App\Models\Model` untouched.

Do not mix `Model::orm()` and `EntityManager` in the same class.
Wrap cross-ORM transactions manually if you write to both in one request.

```php
$em = DoctrineFactory::getInstance();
$em->getConnection()->beginTransaction();
try {
    // Doctrine write
    $em->persist($product);
    $em->flush();

    // Roolith write
    \App\Models\LegacyUser::raw()->table("logs")->insert(["message" => "created product"]);

    $em->getConnection()->commit();
} catch (\Throwable $e) {
    $em->getConnection()->rollBack();
    throw $e;
}
```

## Using Other ORMs

The same three-file pattern applies to Eloquent, Cycle, or any query builder.

- Create a factory that returns the ORM manager or connection.
- Bootstrap it in `System::bootstrap():39` and tear it down in `System::complete():62`.
- Replace or extend `Model.php:9` so models expose the new manager instead of `DatabaseInterface`.

For Eloquent, the factory would return `Illuminate\Database\Capsule\Manager` and the base model would extend `Illuminate\Database\Eloquent\Model`.
The wiring in `System.php` stays the same.

## Notes

- Set `proxyDir` to `storage/proxies` and ensure it is writable and gitignored.
- In development, keep `isDevMode` true so metadata is re-read each request.
- In production, set `isDevMode` false, warm up proxies with `php bin/doctrine orm:generate-proxies`, and configure a metadata cache like Redis.
- Attributes require PHP 8.0 or newer, which matches Roolith 4.0 requirements.
- Doctrine entities should not contain request or session logic, keep business helpers like `User::current():28` in `app/Models` or `app/Misc` and load the user via the EntityManager.
- If you fully replace the ORM, update existing models and controllers that call `Model::orm()`, `Model::raw()`, `Model::all():84`, `where()`, `insert()`, `update()`, `delete()`, and `paginate()` to the EntityManager equivalents shown above.
- Test the wiring with a simple route that returns `DoctrineFactory::getInstance()->getConnection()->isConnected()` as a sanity check after bootstrap.
