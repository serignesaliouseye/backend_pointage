FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip curl \
    libpq-dev libzip-dev libpng-dev libonig-dev libicu-dev \
    && docker-php-ext-install pdo pdo_pgsql zip bcmath gd intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader

CMD php artisan serve --host=0.0.0.0 --port=$PORT