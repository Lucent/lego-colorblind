FROM php:apache

RUN a2enmod rewrite

COPY apache-conf/color.brick.design.conf /etc/apache2/sites-available/000-default.conf

RUN mkdir -p /var/www/app/cache && chown www-data:www-data /var/www/app/cache

EXPOSE 80
