# PHP 8.2 with Apache
FROM php:8.2-apache

# Enable required PHP extensions
RUN docker-php-ext-install mysqli pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . .

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# ✅ यह लाइन जोड़ें – कंटेनर स्टार्ट करने के लिए
CMD ["apache2-foreground"]