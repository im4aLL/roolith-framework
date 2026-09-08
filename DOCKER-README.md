# Docker Development Guide

> WARNING: `docker compose down -v` deletes the `db_data` volume and wipes all database data. Plain `docker compose down` keeps the data. `docker-compose.yml` is a development file that bind-mounts the repo; for prod-like runs without a bind mount use `docker-compose.prod.yml` (COPY build plus a named volume for `public/uploads`).

Local development environment for roolith-framework using Docker.

## What is inside (dev)

| Service    | Container name       | URL / Host                  | Notes                          |
|------------|----------------------|-----------------------------|--------------------------------|
| app        | roolith-app          | http://localhost:8080       | PHP 8.2 + Apache               |
| db         | roolith-db           | localhost:3306 (host `db`)  | MySQL 8.0                      |
| phpmyadmin | roolith-phpmyadmin   | http://localhost:8081       | MySQL web client (dev only)    |

Database credentials (single source is `.env`, see `.env.example` `MYSQL_*`):

| Key      | Default value  | Env key               |
|----------|----------------|-----------------------|
| host     | `db`           | `DB_HOST=db` (app)    |
| database | `roolith_cms`  | `MYSQL_DATABASE`      |
| user     | `roolith`      | `MYSQL_USER`          |
| password | `secret`       | `MYSQL_PASSWORD`      |
| root pw  | `root`         | `MYSQL_ROOT_PASSWORD` |

`docker-compose.yml` reads `MYSQL_*` via `${VAR:-default}`, so `docker compose config` works without a `.env` file and `docker compose up` waits for `db` to report healthy (`mysqladmin ping` plus `depends_on: service_healthy`) before starting `app` and `phpMyAdmin`. The legacy `root` / `root` login still works for local tools; app code should use `DB_HOST=db` with the `MYSQL_*` values above.

## Prerequisites

- Docker Desktop (or Docker Engine + Compose plugin) installed and running.

## First time setup

```
docker compose up -d --build
```

This builds the PHP/Apache image, starts all 3 services and creates the `roolith_cms` database automatically. First run takes a few minutes while Docker downloads the images. `app` and `phpMyAdmin` wait for `db` to become healthy, so no manual retry is needed.

Verify everything is running:

```
docker compose ps
docker compose logs -f db  # watch the mysqladmin healthcheck turn healthy
```

Then open:

- App: http://localhost:8080
- phpMyAdmin: http://localhost:8081

## App database configuration

When you enable the database in `config/config.php`, use the service name `db` as host, not `localhost`. Copy `.env.example` to `.env` and set:

```
DB_HOST=db
DB_NAME=roolith_cms
DB_USER=roolith
DB_PASS=secret
```

Keep `DB_*` in sync with `MYSQL_*` in the same `.env` file (single source: `MYSQL_USER=roolith` / `MYSQL_PASSWORD=secret`, see `.env.example`). The legacy `root` / `root` login still works for local tools; app code should use the `roolith` / `secret` pair above.

## Dev vs prod compose

- `docker-compose.yml` (dev): bind-mounts `./:/var/www/html` for live reload. Convenient but hides permission and persistence issues; never use the bind mount in production.
- `docker-compose.prod.yml` (prod-like): no bind mount, runs from the `COPY` in `Dockerfile`, persists uploads in the `uploads_data` volume (`/var/www/html/public/uploads`), and omits `phpMyAdmin`. Build assets first, then:

```
npm run build
docker compose -f docker-compose.prod.yml up -d --build
```

## Regular development

Your project folder is mounted into the container, so PHP code changes are visible immediately - just refresh the browser. No rebuild needed.

Daily commands (run from the project root):

```
docker compose up -d        # start (waits for db healthy)
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

## Apache hardening and HTTPS

The image enables `rewrite, headers, expires, deflate`, sets `ServerTokens Prod`, `ServerSignature Off`, `TraceEnable Off`, `FileETag None`, and adds one-year expires plus deflate for CSS/JS/SVG. App security headers (CSP, nosniff, frame, HSTS) are sent by PHP. TLS is terminated outside the container; terminate HTTPS at your load balancer or reverse proxy and forward plain HTTP to port 80.

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

Prod uploads live in the `uploads_data` volume (see `docker-compose.prod.yml`):

- `docker compose -f docker-compose.prod.yml down` keeps uploads plus DB data.
- `docker compose -f docker-compose.prod.yml down -v` wipes both.

## Troubleshooting

**Image build fails with "Missing Vite prod manifest"** - the `Dockerfile` refuses to build an image without hashed frontend assets. Run `npm run build` once, then rebuild (`docker compose up -d --build`).

**Port 3306 already in use** - if you run a local MySQL, change the db port mapping in `docker-compose.yml` to e.g. `3307:3306` and run `docker compose up -d`.

**Port 8080 or 8081 already in use** - change the left side of the port mapping for `app` or `phpmyadmin` in `docker-compose.yml`.

**phpMyAdmin shows a connection error right after startup** - fixed by the `service_healthy` gate, but on very slow disks MySQL can still take extra seconds; wait a moment and reload.
