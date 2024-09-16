FROM php:8.2-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y libmcrypt-dev openssl zip unzip git
RUN docker-php-ext-install pdo pdo_mysql
COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install
RUN php artisan migrate --force

CMD ["php-fpm"]
