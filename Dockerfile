FROM php:apache

RUN mkdir -p /var/www/cache/parts && chown -R www-data:www-data /var/www/cache

EXPOSE 80
