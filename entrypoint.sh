#!/bin/bash
set -e

echo "=== Starting Container ==="

# Start PHP-FPM in the background
echo "Starting PHP-FPM..."
php-fpm -D

# Give it 3 seconds to fully initialize
sleep 3

echo "PHP-FPM started successfully"

# Clear and optimize the Symfony cache at runtime using live credentials
if [ "$APP_ENV" = "prod" ]; then
    echo "Clearing production cache..."
    php bin/console cache:clear --env=prod --no-debug

    echo "Warming up optimized production cache..."
    php bin/console cache:warmup --env=prod --no-debug

    # --- ADD THIS LINE HERE ---
    # Fix cache permissions created by root during the warmup above
    chown -R www-data:www-data /app/var
fi
# Mark all migrations as done (skip schema mismatch)
php bin/console doctrine:migrations:version --add --all

echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "Starting Nginx..."
nginx -g "daemon off;"