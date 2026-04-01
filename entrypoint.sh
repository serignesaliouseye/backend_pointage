#!/bin/bash
set -e

echo "========================================="
echo "🚀 Démarrage de l'application Pointage"
echo "========================================="

echo ""
echo "📦 Version PHP: $(php -v | head -1)"

echo ""
echo "🗄️  Exécution des migrations..."
php artisan migrate --force

echo ""
echo "🔗 Création du lien symbolique..."
php artisan storage:link

echo ""
echo "⚙️  Optimisation de Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "✅ Application prête !"
echo "🌐 Lancement du serveur..."
echo "========================================="

# Lancer Apache
apache2-foreground