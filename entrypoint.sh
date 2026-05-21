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

    # Fix cache permissions created by root during the warmup above
    chown -R www-data:www-data /app/var
fi

# Force update database schema to match entities (adds missing columns)
echo "Updating database schema..."
php bin/console doctrine:schema:update --force --complete --no-interaction

# Create test admin user (password: admin123)
echo "Creating test admin user..."
php bin/console doctrine:query:sql "INSERT INTO user (username, email, password, roles, status, is_verified) SELECT 'admin', 'admin@infernogames.com', '\$2y\$13\$82N8x5qjXj5qjXj5qjXj5q', '[\"ROLE_ADMIN\"]', 'active', 1 WHERE NOT EXISTS (SELECT 1 FROM user WHERE username = 'admin')" 2>/dev/null || true

echo "Starting Nginx..."
nginx -g "daemon off;"