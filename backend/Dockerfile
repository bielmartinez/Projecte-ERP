FROM php:8.2-fpm-alpine

RUN apk add --no-cache nginx postgresql-dev icu-dev \
    && docker-php-ext-install pdo pdo_pgsql intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chmod -R 777 writable/

COPY docker/nginx.conf /etc/nginx/nginx.conf

EXPOSE 8080

CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]