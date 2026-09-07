#!/bin/bash
set -e

echo "Starting deployment setup..."

# Ensure var & public directory permissions for www-data
chown -R www-data:www-data /var/www/html/var /var/www/html/public 2>/dev/null || true

# Warmup cache at container startup if needed
php bin/console cache:warmup --env=prod 2>/dev/null || true

# Run database migrations only if DATABASE_URL is defined
if [ -n "$DATABASE_URL" ]; then
    echo "Running database schema update..."
    php bin/console doctrine:schema:update --force --env=prod || echo "Schema update skipped or failed gracefully."
else
    echo "No DATABASE_URL configured. Running in stateless mode."
fi

echo "Setup complete. Starting Apache server..."
exec "$@"
