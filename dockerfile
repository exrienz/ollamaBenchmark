FROM php:8.1-apache

# Install required extensions
RUN apt-get update && apt-get install -y libcurl4-openssl-dev && \
    docker-php-ext-install pdo pdo_mysql curl

# Enable mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Expose port 80
EXPOSE 80

# Ensure Apache restarts on failure
HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
    CMD curl -f http://localhost || exit 1

# Start Apache server
CMD ["apache2-foreground"]
