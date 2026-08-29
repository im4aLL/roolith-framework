# Docker Development Guide

Local development environment for roolith-framework using Docker.

## What is inside

| Service    | Container name       | URL / Host                  | Notes                          |
|------------|----------------------|-----------------------------|--------------------------------|
| app        | roolith-app          | http://localhost:8080       | PHP 8.2 + Apache               |
| db         | roolith-db           | localhost:3306 (host `db`)  | MySQL 8.0                      |
| phpmyadmin | roolith-phpmyadmin   | http://localhost:8081       | MySQL web client               |

Database credentials:

| Key      | Value        |
|----------|--------------|
| host     | `db`         |
| database | `roolith_cms`|
| user     | `root`       |
| password | `root`       |

A second user `roolith` / `secret` also exists with full access to `roolith_cms`.

## Prerequisites

- Docker Desktop (or Docker Engine + Compose plugin) installed and running.

## First time setup

```
docker compose up -d --build
```

This builds the PHP/Apache image, starts all 3 services and creates the
`roolith_cms` database automatically. First run takes a few minutes while
Docker downloads the images.

Verify everything is running:

```
docker compose ps
```

Then open:

- App: http://localhost:8080
- phpMyAdmin: http://localhost:8081

## App database configuration

When you enable the database in `config/config.php`, use the service name
`db` as host, not `localhost`:

```php
"database" => [
    "host" => "db",
    "name" => "roolith_cms",
    "user" => "root",
    "pass" => "root",
],
```

## Regular development

Your project folder is mounted into the container, so PHP code changes are
visible immediately - just refresh the browser. No rebuild needed.

Daily commands (run from the project root):

```
docker compose up -d        # start
docker compose stop         # stop (keeps containers)
docker compose down         # stop and remove containers (keeps DB data)
docker compose ps           # status
docker compose logs -f app  # follow app logs
docker compose logs -f db   # follow mysql logs
```

If you edit the `Dockerfile`, rebuild:

```
docker compose up -d --build
```

## Handy container access

```
docker compose exec app bash              # shell into the PHP container
docker compose exec app php roolith generate controller DemoController
docker compose exec db mysql -uroot -proot roolith_cms
```

Composer can be run on the host if installed, or inside the container:

```
docker compose exec app composer install
```

## Data persistence

MySQL data lives in the `db_data` Docker volume:

- `docker compose down` keeps the data.
- `docker compose down -v` removes the volume and wipes all database data.

## Troubleshooting

**Port 3306 already in use** - if you run a local MySQL, change the db port
mapping in `docker-compose.yml` to e.g. `3307:3306` and run
`docker compose up -d`.

**Port 8080 or 8081 already in use** - change the left side of the port
mapping for `app` or `phpmyadmin` in `docker-compose.yml`.

**phpMyAdmin shows a connection error right after startup** - MySQL takes a
few seconds to boot on first run. Wait a moment and reload.
