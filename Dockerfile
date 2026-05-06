FROM php:8.1-cli

RUN docker-php-ext-install mysqli

WORKDIR /var/www/html

COPY . .

RUN mkdir -p thumbnail video
RUN chmod -R 777 thumbnail video

CMD sh -c 'echo "PORT=$PORT" && php -S 0.0.0.0:${PORT:-8080} -t /var/www/html'
