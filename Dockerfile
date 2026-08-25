FROM composer:2 AS vendor
WORKDIR /app
COPY . .
RUN rm -f bootstrap/cache/*.php \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

FROM php:8.2-apache
RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite
WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/notemind-entrypoint
RUN chmod +x /usr/local/bin/notemind-entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache
ENTRYPOINT ["notemind-entrypoint"]
CMD ["apache2-foreground"]
