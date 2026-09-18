FROM php:8.5-fpm-alpine

# CA raíz corporativa (inspección TLS local): necesaria para que curl/composer
# puedan verificar certificados dentro del contenedor.
COPY kaspersky-ca.crt /usr/local/share/ca-certificates/kaspersky-ca.crt
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
# hace falta configurarlo. Sin él, cada request relee y recompila todos los
# .php a través del bind mount (en Docker Desktop sobre Windows eso suma
# segundos por request). validate_timestamps queda activo para que los
# cambios locales se vean sin reiniciar el contenedor.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=256'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.revalidate_freq=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
