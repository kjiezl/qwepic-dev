# syntax=docker/dockerfile:1.6

FROM php:8.3-apache

# Install system dependencies and PHP extensions required by Symfony
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        libicu-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libpng-dev \
        libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd intl pdo_mysql opcache xml \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Configure Apache to serve the Symfony public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/default-ssl.conf \
    && printf "<Directory /var/www/html/public>\n    Options Indexes FollowSymLinks\n    AllowOverride All\n    Require all granted\n</Directory>\n" > /etc/apache2/conf-available/symfony.conf \
    && a2enconf symfony

# Expose Composer cache and vendor directories as volumes for faster builds (optional)
VOLUME ["/var/www/html/vendor", "/var/www/html/var"]

CMD ["apache2-foreground"]
