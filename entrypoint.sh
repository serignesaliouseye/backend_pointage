#!/bin/bash
set -e

echo "========================================="
echo "🚀 Démarrage de l'application Pointage"
echo "========================================="
echo ""
echo "📦 Version PHP: $(php -v | head -1)"

# ✅ Générer APP_KEY si manquante
if [ -z "$APP_KEY" ]; then
    echo "🔑 Génération de APP_KEY..."
    php artisan key:generate --force
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

# ✅ Lancer Apache
exec apache2-foreground