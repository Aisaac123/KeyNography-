#!/usr/bin/env bash
set -e

echo "🚀 Iniciando configuración de Laravel..."

echo "Esperando a MySQL..."
until php artisan db:monitor > /dev/null 2>&1; do
    echo "⏳ MySQL no está listo, reintentando en 3s..."
    sleep 3
done
echo "✅ MySQL listo"

echo "Running migrations..."
php artisan migrate --force || echo "⚠️ Migraciones fallaron, continuando..."

# Seed solo una vez
if [ ! -f /var/www/html/storage/.seeded ]; then
    echo "Running seeders..."
    php artisan db:seed --force && touch /var/www/html/storage/.seeded || echo "⚠️ Seed falló, continuando..."
else
    echo "⏭️ Seeders ya corrieron, saltando..."
fi

echo "Caching config and routes..."
php artisan config:cache  || true
php artisan route:cache   || true
php artisan view:cache    || true

echo "Creating storage link..."
php artisan storage:link || true

echo "✅ ¡Listo!"
