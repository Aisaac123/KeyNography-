#!/usr/bin/env bash
set -e

echo "🚀 Iniciando configuración de Laravel..."

echo "Setting permissions..."
chown -R nginx:nginx /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "Running migrations..."
php artisan migrate --force || echo "⚠️ Migraciones fallaron, continuando..."

echo "Caching config and routes..."
php artisan config:cache || echo "⚠️ Config cache falló, continuando..."
php artisan route:cache || echo "⚠️ Route cache falló, continuando..."
php artisan view:cache || echo "⚠️ View cache falló, continuando..."

echo "✅ ¡Contenedor listo y corriendo!"
