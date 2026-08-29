FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql mysqli \
    && a2enmod rewrite

RUN echo '<Directory /var/www/html>\n    AllowOverride All\n</Directory>' \
    > /etc/apache2/conf-available/allow-override.conf \
    && a2enconf allow-override
