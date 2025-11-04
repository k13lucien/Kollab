#!/bin/bash

echo "Starting Laravel application..."

# Wait for database to be ready
echo "Waiting for database..."
sleep 10

# Generate application key if not set
echo "Generating application key..."
php artisan key:generate --force || echo "Key generation failed, continuing..."

# Start supervisor
echo "Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
