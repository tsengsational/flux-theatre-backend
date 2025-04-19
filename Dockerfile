FROM wordpress:latest

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set PHP configuration
COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html 