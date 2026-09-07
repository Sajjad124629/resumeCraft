FROM php:8.4-apache

# Enable Apache mod_rewrite for Symfony routing
RUN a2enmod rewrite

# Update Apache DocumentRoot to point to Symfony's public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Install required system packages, PHP extensions, and Bun
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql pdo_pgsql intl zip opcache mbstring \
    && curl -fsSL https://bun.sh/install | bash \
    && mv /root/.bun/bin/bun /usr/local/bin/bun \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy the entire project to the container
COPY . .

# Set environment variables for production build
ENV APP_ENV=prod
ENV APP_SECRET=a8f4c2e6b91d5f30827e419b3c5a7d6e
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1

# Install PHP dependencies (production mode)
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Install frontend dependencies and build assets on EVERY commit deploy
RUN bun install
RUN bun run build

# Create var & public directories and set permissions
RUN mkdir -p /var/www/html/var /var/www/html/public && \
    chown -R www-data:www-data /var/www/html/var /var/www/html/public && \
    chmod -R 777 /var/www/html/var

# Clear and warmup cache safely for production build
RUN php bin/console cache:clear --env=prod --no-debug || true

# Make the entrypoint script executable
RUN chmod +x docker-entrypoint.sh

# Render container port
EXPOSE 80

# Use our custom entrypoint script
ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
