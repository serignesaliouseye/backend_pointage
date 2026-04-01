#!/bin/bash
set -e

echo "========================================="
echo "🚀 Démarrage de l'application Pointage"
echo "========================================="
echo ""
echo "📦 Version PHP: $(php -v | head -1)"

# ✅ Crée le .env AVANT tout
if [ ! -f /var/www/html/.env ]; then
    echo "📝 Création du fichier .env..."
    cp /var/www/html/.env.example /var/www/html/.env
    echo "✅ .env créé"
fi

# ✅ Injecter les variables d'environnement dans .env
echo "⚙️  Configuration des variables..."

# App
[ ! -z "$APP_KEY" ] && sed -i "s|APP_KEY=.*|APP_KEY=${APP_KEY}|g" /var/www/html/.env
[ ! -z "$APP_ENV" ] && sed -i "s|APP_ENV=.*|APP_ENV=${APP_ENV}|g" /var/www/html/.env
[ ! -z "$APP_DEBUG" ] && sed -i "s|APP_DEBUG=.*|APP_DEBUG=${APP_DEBUG}|g" /var/www/html/.env
[ ! -z "$APP_URL" ] && sed -i "s|APP_URL=.*|APP_URL=${APP_URL}|g" /var/www/html/.env

# Database
[ ! -z "$DB_CONNECTION" ] && sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=${DB_CONNECTION}|g" /var/www/html/.env
[ ! -z "$DB_HOST" ] && sed -i "s|DB_HOST=.*|DB_HOST=${DB_HOST}|g" /var/www/html/.env
[ ! -z "$DB_PORT" ] && sed -i "s|DB_PORT=.*|DB_PORT=${DB_PORT}|g" /var/www/html/.env
[ ! -z "$DB_DATABASE" ] && sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE}|g" /var/www/html/.env
[ ! -z "$DB_USERNAME" ] && sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME}|g" /var/www/html/.env
[ ! -z "$DB_PASSWORD" ] && sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|g" /var/www/html/.env

# ✅ Générer APP_KEY si manquante
if grep -q "APP_KEY=$" /var/www/html/.env || grep -q "APP_KEY=your" /var/www/html/.env; then
    echo "🔑 Génération de APP_KEY..."
    php artisan key:generate --force
else
    echo "🔑 APP_KEY déjà configurée"
fi

echo ""
echo "🗄️  Exécution des migrations..."
php artisan migrate --force

echo ""
echo "🔗 Création du lien symbolique..."
php artisan storage:link || true

echo ""
echo "⚙️  Optimisation de Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "✅ Application prête !"
echo "🌐 Lancement du serveur Apache..."
echo "========================================="

exec apache2-foreground