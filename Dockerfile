FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    supervisor \
    libpng-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    oniguruma-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_mysql \
        bcmath \
        pcntl \
        opcache \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

COPY . .

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
