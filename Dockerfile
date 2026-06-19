FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    unzip \
    curl \
    && docker-php-ext-install \
    intl \
    zip \
    pdo \
    pdo_mysql \
    mbstring \
    gd \
    bcmath \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --optimize-autoloader

COPY . .

RUN composer run-script post-autoload-dump && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
