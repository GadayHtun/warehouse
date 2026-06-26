#!/bin/bash
set -e

echo "🚀 Starting warehouse app..."
echo "PORT: ${PORT:-10000}"

# Substitute PORT in nginx config
envsubst '${PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf
echo "✅ Nginx config generated (PORT: ${PORT:-10000})"

# Verify nginx config
nginx -t 2>&1 || echo "⚠️ Nginx config test failed"

# Create SQLite database if it doesn't exist
if [ ! -f /var/www/storage/database.sqlite ]; then
    touch /var/www/storage/database.sqlite
    chown www-data:www-data /var/www/storage/database.sqlite
    echo "✅ Created SQLite database"
fi

# Set permissions
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Run migrations
php artisan migrate --force || echo "⚠️ Migrations may have failed"

# Seed database
php artisan db:seed --force --class=DatabaseSeeder 2>/dev/null || echo "⚠️ Seeding may have failed"

# Cache config (after env is loaded)
php artisan config:cache 2>/dev/null || echo "⚠️ Config cache failed"
php artisan route:cache 2>/dev/null || echo "⚠️ Route cache failed"
php artisan view:cache 2>/dev/null || echo "⚠️ View cache failed"

echo "✅ Starting services..."

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
