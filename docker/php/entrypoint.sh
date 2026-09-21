#!/bin/sh
set -e

# El bind mount de ./app hereda el dueño del host (root en un VPS recién
# clonado). php-fpm corre sus workers como www-data (ver www.conf), así
# que sin esto Laravel no puede escribir storage/logs, storage/framework/*
# ni bootstrap/cache -> 500 sin nada en el log (falla antes de poder
# loguear el error). Se corre en cada arranque de "app" y "queue-worker"
# (mismo entrypoint, mismo bind mount) para no depender de acordarse de
# hacerlo a mano tras un git clone nuevo.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

exec "$@"
