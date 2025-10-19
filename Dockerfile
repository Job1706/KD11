FROM php:8.2-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite \
 && printf "<Directory /var/www/html>\n    AllowOverride All\n</Directory>\n" >> /etc/apache2/apache2.conf
COPY ./src/ /var/www/html/
EXPOSE 80
