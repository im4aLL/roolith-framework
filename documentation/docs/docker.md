# Docker

> WARNING: `docker compose down -v` deletes the `db_data` volume and wipes all database data. `docker-compose.yml` is a development file that bind-mounts the repo; for prod-like runs use `docker-compose.prod.yml` (COPY build plus a named volume for `public/uploads`).

The project ships with a Docker setup for local development.
It gives you PHP, MySQL and phpMyAdmin without installing anything on your machine.

## Services

| Service    | Container name     | URL / Host                 | Notes                 |
|------------|--------------------|-----------------------------|-----------------------|
| app        | roolith-app        | `http://localhost:8080`     | PHP 8.2 + Apache      |
| db         | roolith-db         | localhost:3306 (host `db`)  | MySQL 8.0             |
| phpmyadmin | roolith-phpmyadmin | `http://localhost:8081`     | MySQL web client (dev only) |

Database credentials (single source is `.env`, see `.env.example` `MYSQL_*`):

| Key      | Default value  | Env key               |
|----------|----------------|-----------------------|
| host     | `db`           | `DB_HOST=db` (app)    |
| database | `roolith_cms`  | `MYSQL_DATABASE`      |
| user     | `roolith`      | `MYSQL_USER`          |
| password | `secret`       | `MYSQL_PASSWORD`      |
| root pw  | `root`         | `MYSQL_ROOT_PASSWORD` |

`app` and `phpMyAdmin` wait for `db` to report healthy (`mysqladmin ping` plus `depends_on: service_healthy`). The Apache image sets `ServerTokens Prod`, `ServerSignature Off`, `TraceEnable Off`, plus expires and deflate for static assets; TLS is terminated outside the container.

## Requirements

- Docker Desktop (or Docker Engine + Compose plugin) installed and running.

## First Run

From the project root:

```bash
docker compose up -d --build
```

This builds the PHP/Apache image, starts all 3 services and creates the
`roolith_cms` database automatically.
First run takes a few minutes while Docker downloads the images.

Verify everything is running:

```bash
docker compose ps
```

Then open:

- App: `http://localhost:8080`
- phpMyAdmin: `http://localhost:8081`

## App Database Configuration

When you enable the database in `config/config.php`, use the service name `db` as host, not `localhost`. Copy `.env.example` to `.env` and keep `DB_*` in sync with `MYSQL_*`:

```
DB_HOST=db
DB_NAME=roolith_cms
DB_USER=roolith
DB_PASS=secret
```

## Dev vs prod compose

- `docker-compose.yml` (dev) bind-mounts the repo for live reload.
- `docker-compose.prod.yml` (prod-like) runs from the `COPY` in `Dockerfile` with no bind mount and persists uploads in `uploads_data`. Build assets first: `npm run build && docker compose -f docker-compose.prod.yml up -d --build`.

## Regular Development

Your project folder is mounted into the container, so PHP code changes are
visible immediately - just refresh the browser. No rebuild needed.

Daily commands (run from the project root):

```bash
docker compose up -d        # start
docker compose stop         # stop (keeps containers)
docker compose down         # stop and remove containers (keeps DB data)
docker compose ps           # status
docker compose logs -f app  # follow app logs
docker compose logs -f db   # follow mysql logs
```

If you edit the `Dockerfile`, rebuild:

```bash
docker compose up -d --build
```

## Container Access

```bash
docker compose exec app bash              # shell into the PHP container
docker compose exec app php roolith generate controller DemoController
docker compose exec db mysql -uroot -proot roolith_cms
```

Composer can be run on the host if installed, or inside the container:

```bash
docker compose exec app composer install
```

## Data Persistence

MySQL data lives in the `db_data` Docker volume.

- `docker compose down` keeps the data.
- `docker compose down -v` removes the volume and wipes all database data.

## Troubleshooting

**Port 3306 already in use** - if you run a local MySQL, change the db port
mapping in `docker-compose.yml` to e.g. `3307:3306` and run
`docker compose up -d`.

**Port 8080 or 8081 already in use** - change the left side of the port
mapping for `app` or `phpmyadmin` in `docker-compose.yml`.

**phpMyAdmin shows a connection error right after startup** - fixed by the `service_healthy` gate; on very slow disks wait a moment and reload.
