FROM php:8.4-apache

# Installer les extensions PHP
RUN apt-get update && apt-get install -y \
    git unzip curl \
    libpq-dev libzip-dev libpng-dev libonig-dev libicu-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli zip bcmath gd intl

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier le code source
COPY . .

# Installer les dépendances
RUN composer install --no-dev --optimize-autoloader

# Configurer les permissions
RUN chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

# Activer mod_rewrite
RUN a2enmod rewrite

# Copier le script d'entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Exposer le port
EXPOSE 8080

# Entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]