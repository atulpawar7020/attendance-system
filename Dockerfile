# PHP 8.2 with Apache
FROM php:8.2-apache

# Install PHP extensions (mysqli, pdo_mysql, etc.)
RUN docker-php-ext-install mysqli pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . .

# ✅ FIX: Set ownership to www-data (not changing group)
# Pxxl allows root to run Apache, but not setgid to group 33.
# So we keep default user/group and skip setgid.
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# ✅ FIX: Run Apache with root user (avoids setgid error)
# Pxxl runtime allows root user to start Apache
# The parent process (Pxxl) handles permissions.
# We just ensure Apache runs in foreground.
CMD ["apache2-foreground"]