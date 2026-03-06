#!/usr/bin/env bash
set -e

echo "🚀 Iniciando configuración de Laravel..."

echo "Running migrations..."
php artisan migrate --force || echo "⚠️ Migraciones fallaron, continuando..."

echo "Caching config and routes..."
php artisan config:cache || echo "⚠️ Config cache falló, continuando..."
php artisan route:cache   || echo "⚠️ Route cache falló, continuando..."
php artisan view:cache    || echo "⚠️ View cache falló, continuando..."

echo "Creating storage link..."
php artisan storage:link || true

echo "✅ ¡Contenedor listo y corriendo!"
