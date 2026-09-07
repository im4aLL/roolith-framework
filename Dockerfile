FROM php:8.2-apache

# Verify locally with: composer check-platform-reqs
RUN apt-get update && apt-get install -y libicu-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mysqli intl zip opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

RUN echo '<Directory /var/www/html>\n    AllowOverride All\n</Directory>' \
    > /etc/apache2/conf-available/allow-override.conf \
    && a2enconf allow-override
