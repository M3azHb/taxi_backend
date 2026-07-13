FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git nano \
    && docker-php-ext-install pdo pdo_mysql zip opcache \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers

RUN echo "upload_max_filesize=50M\npost_max_size=55M\nmax_execution_time=300\nmemory_limit=256M" \
    > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "opcache.enable=1\nopcache.memory_consumption=128\nopcache.max_accelerated_files=10000\nopcache.validate_timestamps=0" \
    > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN git config --global --add safe.directory /var/www/html

COPY . .

RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache \
    && chmod -R 777 storage/framework bootstrap/cache

COPY apache.conf /etc/apache2/sites-available/000-default.conf

RUN composer install --no-dev --optimize-autoloader || \
    (rm -f composer.lock && composer update --no-dev --optimize-autoloader)

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
