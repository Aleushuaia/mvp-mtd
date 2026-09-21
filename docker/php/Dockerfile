FROM php:8.5-fpm-alpine

# CA extra para buildear detrás de un proxy con inspección TLS (p. ej. red
# corporativa): docker/php/certs/ está vacía en el repo (gitignored salvo
# .gitkeep). Si tu red necesita confiar en un CA propio para que
# curl/composer bajen paquetes, poné el .crt ahí antes de buildear. En un
# clon nuevo (VPS) la carpeta llega vacía y no se hornea ningún CA de
# terceros en la imagen.
COPY certs/ /usr/local/share/ca-certificates/
RUN apk add --no-cache ca-certificates && update-ca-certificates

# Dependencias del sistema necesarias para compilar extensiones y para Laravel
RUN apk add --no-cache \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        bcmath \
        zip \
        intl \
    && apk del $PHPIZE_DEPS

# OPcache: viene compilado de forma estática en la imagen base (ya no puede
# instalarse como extensión compartida vía docker-php-ext-install), sólo
# hace falta configurarlo. Con validate_timestamps=1 cada request hace un
# stat() por cada archivo .php incluido (framework + vendor + app) a través
# del bind mount de Docker Desktop sobre Windows. Medido en este entorno:
# 3-11s por request. revalidate_freq no alcanza a mitigarlo porque cada
# request ya dura más que la ventana de throttling, así que nunca "enfría".
# validate_timestamps=0 lo elimina por completo (medido: 0.4-0.7s por
# request). Mismo criterio que config/view.php (check_cache_timestamps):
# se compila una vez y sólo se refresca reiniciando el contenedor
# (`docker compose restart app`) o corriendo `php artisan optimize:clear`
# + reinicio después de cambiar código PHP.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=256'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
