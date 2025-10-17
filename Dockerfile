FROM php:8.2-apache

# Add build-time arguments for GitHub reference
ARG GIT_COMMIT=unknown
ARG GIT_REPO=unknown
ARG VERSION=latest

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install required PHP extensions and MySQL support
RUN docker-php-ext-install pdo pdo_mysql

# Install additional tools including cron and curl
RUN apt-get update && apt-get install -y \
    curl \
    cron \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod +x /var/www/html/cron_fetch_weather.php

# Create version file with build info (single line)
RUN echo '<?php return [ "version" => "'${VERSION}'", "git_commit" => "'${GIT_COMMIT}'", "git_repo" => "'${GIT_REPO}'", "build_date" => "'$(date -Iseconds)'" ]; ?>' > /var/www/html/version.php

# Add labels for Docker Hub
LABEL org.opencontainers.image.title="KSAT Weather Dashboard"
LABEL org.opencontainers.image.description="San Antonio Weather Monitoring System"
LABEL org.opencontainers.image.version="${VERSION}"
LABEL org.opencontainers.image.vendor="ldhagen"
LABEL org.opencontainers.image.source="https://github.com/ldhagen/KSAT_Dockerized_PHP_Weather"
LABEL org.opencontainers.image.revision="${GIT_COMMIT}"
LABEL org.opencontainers.image.licenses="MIT"
LABEL org.opencontainers.image.url="https://github.com/ldhagen/KSAT_Dockerized_PHP_Weather"
LABEL org.opencontainers.image.documentation="https://github.com/ldhagen/KSAT_Dockerized_PHP_Weather#readme"

EXPOSE 80

CMD ["apache2-foreground"]