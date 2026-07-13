#!/bin/sh
set -e

echo "Waiting for the database..."
until php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; do
    sleep 2
done

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Warming cache..."
php bin/console cache:clear
php bin/console cache:warmup
chown -R www-data:www-data var

exec "$@"
