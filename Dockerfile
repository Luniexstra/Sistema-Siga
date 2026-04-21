FROM php:8.2-cli

# Dependencias del sistema y extensiones necesarias para Laravel + PostgreSQL
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
        bcmath \
        mbstring \
        pdo_pgsql \
        xml \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copiar archivos de Composer primero para aprovechar cache de Docker
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copiar el resto del proyecto
COPY . .

RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan package:discover --ansi

# Script de arranque
RUN chmod +x docker/start.sh

EXPOSE 10000

CMD ["sh", "docker/start.sh"]
