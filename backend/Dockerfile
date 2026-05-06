FROM php:8.2-fpm-alpine

# Extensions PHP que necessita CodeIgniter + PostgreSQL
RUN apk add --no-cache nginx postgresql-dev icu-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql intl

# Instal·lar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar el projecte
COPY . .

# Instal·lar dependències PHP (sense les de dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Crear les carpetes que CI4 necessita i donar permisos
RUN mkdir -p writable/logs writable/cache writable/session writable/uploads writable/debugbar \
    && chmod -R 777 writable/

# Configuració de Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

EXPOSE 8080

CMD ["sh", "-c", "php spark migrate --all 2>&1; php-fpm -D && nginx -g 'daemon off;'"]