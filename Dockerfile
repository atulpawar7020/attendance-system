# PHP 8.2 with Apache
FROM php:8.2-apache

# Enable required PHP extensions (mysqli, pdo_mysql, etc.)
RUN docker-php-ext-install mysqli pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy all project files to container
COPY . .

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose port 80 (Apache default)
EXPOSE 80