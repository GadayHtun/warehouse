# Warehouse

A Laravel inventory management application running on Docker Compose.

## Architecture

| Service       | Container           | Image              | Port      |
|---------------|---------------------|--------------------|-----------|
| PHP-FPM       | `warehouse-app`     | custom (PHP 8.4)   | 9000      |
| Nginx         | `warehouse-nginx`   | `nginx:alpine`     | **8080**  |
| MySQL         | `warehouse-mysql`   | `mysql:8.0`        | 3307      |
| Redis         | `warehouse-redis`   | `redis:7-alpine`   | 6380      |
| Queue Worker  | `warehouse-queue`   | custom (PHP 8.4)   | —         |

- **App** — PHP 8.4-FPM with Composer, runs Laravel. Serves PHP requests to Nginx via FastCGI.
- **Nginx** — Reverse proxy. Exposes port `8080` on the host. Routes requests to `app:9000`.
- **MySQL** — Persistent database. Exposed on host port `3307` for external tool access.
- **Redis** — Cache / session / queue backend. Exposed on host port `6380`.
- **Queue Worker** — Runs `php artisan queue:work` to process queued jobs.

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/install/) (v2+)
- phpMyAdmin, TablePlus, DBeaver, or another MySQL client (optional — for inspecting the database locally)

## Quick Start

### 1. Clone & configure

```bash
git clone <repo-url> warehouse
cd warehouse
```

Copy the environment file and generate an application key:

```bash
cp .env.example .env
```

Make sure `.env` points to the Docker services:

```dotenv
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=warehouse
DB_USERNAME=warehouse
DB_PASSWORD=warehouse_secret

REDIS_HOST=redis
REDIS_PORT=6379
```

### 2. Build & start

```bash
docker compose up -d --build
```

On first run this builds the PHP image (installs Composer dependencies and caches Laravel config) and pulls the service images.

### 3. Run migrations

```bash
docker compose exec app php artisan migrate
```

Optionally seed the database:

```bash
docker compose exec app php artisan db:seed
```

### 4. Access the app

Open **http://localhost:8080** in your browser.

## Everyday Commands

```bash
# Start / stop
docker compose up -d          # start all services in the background
docker compose down           # stop and remove containers (data volumes persist)

# View logs
docker compose logs -f app       # tail the PHP-FPM log
docker compose logs -f nginx     # tail the nginx access/error log
docker compose logs -f queue     # tail the queue worker output

# Run artisan commands
docker compose exec app php artisan make:model Product
docker compose exec app php artisan route:list
docker compose exec app php artisan tinker

# Run Composer
docker compose exec app composer require some/package
docker compose exec app composer install

# Restart the queue worker after code changes
docker compose restart queue-worker

# Access the database from the host
# Connect to 127.0.0.1:3307 with credentials:
#   database: warehouse
#   username: warehouse
#   password: warehouse_secret
```

## Persistent Data

Named volumes store MySQL and Redis data so they survive `docker compose down`:

- `mysql-data` — MySQL database files
- `redis-data` — Redis RDB/AOF snapshots

To wipe data and start fresh:

```bash
docker compose down -v
```

## Service Health Checks

Both **MySQL** and **Redis** are configured with health checks. The `app` and `queue-worker` services wait for them to report healthy before starting, preventing race conditions on first boot.

## Building for Production

The Dockerfile is configured for development. For production, adjust:

- Set `APP_ENV=production` and `APP_DEBUG=false` in the environment
- Remove development-only composer packages with `composer install --no-dev`
- Use a dedicated cache driver (Redis) instead of `file`
- Set `QUEUE_CONNECTION=redis` for production queue processing

## Troubleshooting

**App won't start — "Connection refused"**
Wait for MySQL to pass its health check (up to ~50 seconds on first boot). Re-run `docker compose logs mysql` to confirm it's healthy.

**Permission errors on storage/**
```bash
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

**Port 8080 already in use**
Change the nginx port mapping in `docker-compose.yml`:
```yaml
ports:
  - "8081:80"
```

**Reset everything**
```bash
docker compose down -v
docker compose up -d --build
docker compose exec app php artisan migrate
```

**Run these two commands: login error**

```bash
docker compose down
docker compose up -d

docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed --class=UserSeeder
```

