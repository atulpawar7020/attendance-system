FROM php:8.2-cli

RUN docker-php-ext-install mysqli pdo_mysql

WORKDIR /var/www/html

COPY . .

EXPOSE 80

CMD ["php", "-S", "0.0.0.0:80", "-t", ".", "-d", "display_errors=1", "-d", "error_reporting=E_ALL"]