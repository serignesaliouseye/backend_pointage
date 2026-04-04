#!/bin/bash
set -e

echo "========================================="
echo "🚀 Démarrage de l'application Pointage"
echo "========================================="
echo ""
echo "📦 Version PHP: $(php -v | head -1)"

APP_DIR=/var/www/html
ENV_FILE=$APP_DIR/.env

# ✅ Créer .env si inexistant
if [ ! -f "$ENV_FILE" ]; then
    echo "📝 Création du fichier .env..."
    cp $APP_DIR/.env.example $ENV_FILE
    echo "✅ .env créé"
fi

echo ""
echo "⚙️ Configuration des variables..."

# ======================
# APP CONFIG
# ======================
sed -i "s|APP_ENV=.*|APP_ENV=${APP_ENV:-production}|g" $ENV_FILE
sed -i "s|APP_DEBUG=.*|APP_DEBUG=${APP_DEBUG:-false}|g" $ENV_FILE
sed -i "s|APP_URL=.*|APP_URL=${APP_URL}|g" $ENV_FILE

# ======================
# APP KEY - ✅ Corrigé : utilise la variable d'env si disponible
# ======================
echo ""
if [ ! -z "$APP_KEY" ]; then
    echo "🔑 APP_KEY trouvée dans les variables d'environnement..."
    sed -i "s|APP_KEY=.*|APP_KEY=${APP_KEY}|g" $ENV_FILE
    echo "🔑 APP_KEY configurée"
elif ! grep -q "^APP_KEY=base64" $ENV_FILE; then
    echo "🔑 Génération de APP_KEY..."
    php artisan key:generate --force
else
    echo "🔑 APP_KEY déjà configurée"
fi

# ======================
# DATABASE CONFIG
# ======================
echo ""
echo "📝 Configuration de la base de données..."
sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=pgsql|g" $ENV_FILE
sed -i "s|DB_HOST=.*|DB_HOST=${DB_HOST}|g" $ENV_FILE
sed -i "s|DB_PORT=.*|DB_PORT=${DB_PORT:-5432}|g" $ENV_FILE
sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE}|g" $ENV_FILE
sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME}|g" $ENV_FILE
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|g" $ENV_FILE

# SSL obligatoire pour Aiven
if grep -q "PGSSLMODE" $ENV_FILE; then
    sed -i "s|PGSSLMODE=.*|PGSSLMODE=${PGSSLMODE:-require}|g" $ENV_FILE
else
    echo "PGSSLMODE=${PGSSLMODE:-require}" >> $ENV_FILE
fi

# ======================
# DEBUG DB CONFIG
# ======================
echo ""
echo "🔍 Vérification configuration DB:"
grep "^DB_" $ENV_FILE
grep "^PGSSLMODE" $ENV_FILE

# ======================
# CACHE CLEAR
# ======================
echo ""
echo "🧹 Nettoyage du cache Laravel..."
php artisan config:clear
php artisan cache:clear

# ======================
# TEST CONNEXION DB
# ======================
echo ""
echo "🔌 Test de connexion à la base de données..."
php artisan tinker --execute="
try {
    DB::connection()->getPdo();
    echo 'Connexion DB OK';
} catch (\Exception \$e) {
    echo 'Erreur DB: ' . \$e->getMessage();
    exit(1);
}
"

# ======================
# MIGRATIONS
# ======================
echo ""
echo "🗄️ Exécution des migrations..."
php artisan migrate --force

# ======================
# SEEDER - ✅ Corrigé : ne tourne qu'une seule fois
# ======================
echo ""
echo "🌱 Vérification du seeder..."
USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null | tail -1)

if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
    echo "🌱 Exécution du seeder..."
    php artisan db:seed --force
else
    echo "✅ Base de données déjà peuplée ($USER_COUNT utilisateurs)"
fi

# ======================
# STORAGE LINK
# ======================
echo ""
echo "🔗 Création du lien storage..."
php artisan storage:link || true

# ======================
# OPTIMISATION
# ======================
echo ""
echo "⚡ Optimisation Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "========================================="
echo "✅ Application prête !"
echo "🌐 Lancement du serveur Apache..."
echo "========================================="

exec apache2-foreground