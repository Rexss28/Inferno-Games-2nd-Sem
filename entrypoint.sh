#!/bin/bash
set -e

echo "=== Starting Container ==="

# Wait for PHP-FPM to be ready
echo "Starting PHP-FPM..."
php-fpm -D

# Wait for PHP-FPM to actually start
sleep 3

# Check if PHP-FPM is running
if ! pgrep php-fpm > /dev/null; then
    echo "ERROR: PHP-FPM failed to start"
    exit 1
fi

echo "PHP-FPM is running"

# Run migrations if in production
if [ "$APP_ENV" = "prod" ]; then
    echo "Running migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction || true
    echo "Clearing cache..."
    php bin/console cache:clear --env=prod --no-debug
    echo "Warming cache..."
    php bin/console cache:warmup --env=prod
fi

echo "Starting Nginx..."
nginx -g "daemon off;"