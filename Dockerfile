FROM php:8.4-fpm

WORKDIR /app

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    nginx \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy application files
COPY . .

# Create .env file from environment variables BEFORE running composer
RUN echo "APP_ENV=${APP_ENV:-prod}" > .env && \
    echo "APP_DEBUG=0" >> .env && \
    echo "APP_SECRET=${APP_SECRET:-ChangeMe}" >> .env

# Install Composer dependencies with --no-scripts to prevent runtime database checks
RUN composer install --no-interaction --optimize-autoloader --no-dev --no-scripts

# 1. Force creation of missing directories so the permissions command won't crash
RUN mkdir -p /app/var /app/public/uploads

# 2. Set permissions safely now that the directories definitely exist
RUN chown -R www-data:www-data /app/var /app/public/uploads && \
    chmod -R 775 /app/var /app/public/uploads

# Configure Nginx
COPY nginx-main.conf /etc/nginx/nginx.conf
RUN rm -rf /etc/nginx/conf.d/*
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

HEALTHCHECK --interval=10s --timeout=3s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]