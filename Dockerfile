FROM php:8.4-fpm

ARG user=laravel
ARG uid=1000

RUN apt-get update && apt-get install -y \
    nginx \
    git curl zip unzip \
    libpng-dev libonig-dev libxml2-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && chown -R $user:$user /home/$user

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
