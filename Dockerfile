FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libzip-dev zip unzip cron curl \
    && docker-php-ext-install gd mysqli zip \
    && a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . /var/www/html/

# Ensure permissions
RUN chown -R www-data:www-data /var/www/html

# Make cron script executable if it exists
RUN if [ -f /var/www/html/cron-scripts/cron_fetch_weather.php ]; then \
      chmod +x /var/www/html/cron-scripts/cron_fetch_weather.php; \
    fi

# Register crontab if it exists
RUN if [ -f /var/www/html/cron-scripts/ksat_cron ]; then \
      cp /var/www/html/cron-scripts/ksat_cron /etc/cron.d/ksat_cron && \
      chmod 0644 /etc/cron.d/ksat_cron && \
      crontab /etc/cron.d/ksat_cron; \
    fi

# Start cron and Apache
CMD service cron start && apache2-foreground

