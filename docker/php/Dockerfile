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

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
