#!/bin/bash
set -e

# Create SQLite database if it doesn't exist
if [ ! -f /var/www/storage/database.sqlite ]; then
    touch /var/www/storage/database.sqlite
    chown www-data:www-data /var/www/storage/database.sqlite
fi

# Set permissions
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Run migrations
php artisan migrate --force || true

# Cache config (after env is loaded)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
