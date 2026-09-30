# backend-symfony/Dockerfile
FROM docker.io/library/php:8.4-fpm-alpine

# Instalar dependencias del sistema y librerías necesarias
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    libpq-dev \
    icu-dev \
    libzip-dev

# Instalar y habilitar las extensiones de PHP para Symfony y PostgreSQL
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    intl \
    zip \
    opcache

# Copiar Composer desde su imagen oficial
COPY --from=docker.io/library/composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

EXPOSE 9000

# Instalar dependencias de Composer al arrancar e iniciar PHP-FPM
CMD ["sh", "-c", "composer install --no-interaction && php-fpm"]