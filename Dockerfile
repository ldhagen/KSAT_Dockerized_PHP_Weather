# Use official PHP image with Apache
FROM php:8.2-apache

# Install required packages
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    cron \
    curl \
    && docker-php-ext-install gd mysqli zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
 && chmod +x /var/www/html/cron-scripts/cron_fetch_weather.php

# Copy and register crontab
COPY cron-scripts/ksat_cron /etc/cron.d/ksat_cron
RUN chmod 0644 /etc/cron.d/ksat_cron \
 && crontab /etc/cron.d/ksat_cron

# Start cron and Apache
CMD service cron start && apache2-foreground

