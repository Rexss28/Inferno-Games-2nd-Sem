#!/bin/bash
set -e

echo "=== Starting Container ==="

# Start PHP-FPM in the background
echo "Starting PHP-FPM..."
php-fpm -D

# Wait for PHP-FPM to actually start up
sleep 3

# Check if PHP-FPM is running by looking at active system processes natively
if ! kill -0 $(cat /var/run/php-fpm.pid 2>/dev/null) 2>/dev/null && ! ps aux | grep "[p]hp-fpm" > /dev/null; then
    echo "ERROR: PHP-FPM failed to start"
    exit 1
fi

echo "PHP-FPM is running cleanly"

# Clear and optimize the Symfony cache at runtime
if [ "$APP_ENV" = "prod" ]; then
    echo "Clearing production cache..."
    php bin/console cache:clear --env=prod --no-debug

    echo "Warming up optimized production cache..."
    php bin/console cache:warmup --env=prod --no-debug
fi

echo "Starting Nginx..."
nginx -g "daemon off;"