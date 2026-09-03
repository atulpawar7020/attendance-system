# PHP 8.2 with Built-in Server (No Apache) – Pxxl Compatible
FROM php:8.2-cli

# Install required PHP extensions (mysqli, pdo_mysql)
RUN docker-php-ext-install mysqli pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . .

# Expose port 80 (Pxxl requires this)
EXPOSE 80

# ✅ Start PHP Built-in Server on port 80
# All PHP files will be served directly, exactly like your XAMPP setup.
CMD ["php", "-S", "0.0.0.0:80", "-t", "."]