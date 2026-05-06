FROM php:8.1-apache

RUN docker-php-ext-install mysqli

COPY . /var/www/html/

RUN mkdir -p /var/www/html/thumbnail /var/www/html/video
RUN chmod -R 777 /var/www/html/thumbnail /var/www/html/video

RUN sed -i 's/80/\${PORT}/g' /etc/apache2/ports.conf
RUN sed -i 's/<VirtualHost \*:80>/<VirtualHost *:\${PORT}>/g' /etc/apache2/sites-available/000-default.conf

ENV PORT=80
EXPOSE ${PORT}

CMD ["apache2-foreground"]
