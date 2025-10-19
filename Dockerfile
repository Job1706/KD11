FROM php:8.2-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite for pretty URLs
RUN a2enmod rewrite \
 && printf "<Directory /var/www/html>\n    AllowOverride All\n    Options Indexes FollowSymLinks\n    Require all granted\n</Directory>\n" >> /etc/apache2/apache2.conf

# Copy application source (mounted as volume in compose for dev)
COPY ./src/ /var/www/html/

EXPOSE 80
CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
