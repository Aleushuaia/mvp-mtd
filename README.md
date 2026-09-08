# MVP

Entorno de desarrollo local para una aplicación web simple: Laravel 13 + PHP 8.5 + PostgreSQL 18 + Nginx + Blade + Bootstrap, orquestado con Docker Compose.

## Requisitos

* Docker Desktop
* Docker Compose (incluido en Docker Desktop)

## Iniciar

```bash
docker compose build
docker compose up -d
```

## Detener

```bash
docker compose down
```

Los datos de PostgreSQL persisten en `./volumes/postgres` y el código Laravel vive en `./app`, así que un `docker compose down` seguido de `docker compose up -d` no pierde nada. No usar `docker compose down -v`.

## Acceder a la aplicación

```
http://localhost:8080
```

## Comandos Artisan / Composer

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan about
docker compose exec app composer install
```

## Acceder a PostgreSQL

```bash
docker compose exec postgres psql -U mvp -d mvp
```

Desde el host, la base está expuesta en `localhost:5432` con las credenciales definidas en `.env` (`POSTGRES_DB`, `POSTGRES_USER`, `POSTGRES_PASSWORD`).
