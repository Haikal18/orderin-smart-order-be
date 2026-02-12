
FROM php:8.4-fpm

WORKDIR /var/www/html


RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*


RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    bcmath \
    exif \
    pcntl


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


COPY --chown=www-data:www-data . .


RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist


RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache


RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache


USER www-data


EXPOSE 9000


CMD ["php-fpm"]
