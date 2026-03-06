FROM richarvey/nginx-php-fpm:latest
WORKDIR /var/www/html

# Instalar Node.js 22
RUN apk add --no-cache --repository=https://dl-cdn.alpinelinux.org/alpine/edge/community nodejs npm

# Copiar dependencias y código
COPY composer.json composer.lock package.json package-lock.json ./

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction && npm ci

COPY . .

# Compilar assets y preparar Laravel
RUN composer dump-autoload --optimize \
    && php artisan filament:assets \
    && php artisan livewire:publish --assets \
    && npm run build

# Copiar y dar permisos al script de inicio
COPY scripts/install.sh /var/www/html/scripts/install.sh
RUN chmod +x /var/www/html/scripts/install.sh

# Configuración de la imagen richarvey/nginx-php-fpm
ENV WEBROOT=/var/www/html/public
ENV RUN_SCRIPTS=1
ENV SCRIPT_PATH=/var/www/html/scripts/install.sh

EXPOSE 80
CMD ["/start.sh"]
