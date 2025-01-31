# Use the official PHP Apache image
FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory inside the container
WORKDIR /var/www/html

# Copy application files to the container
COPY . /var/www/html/

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html

# Expose the default Apache port
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
