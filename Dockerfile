FROM php:8.1-apache

RUN docker-php-ext-install mysqli

COPY . /var/www/html/

RUN mkdir -p /var/www/html/thumbnail /var/www/html/video
RUN chmod -R 777 /var/www/html/thumbnail /var/www/html/video

EXPOSE 80
