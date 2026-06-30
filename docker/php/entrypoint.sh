#!/bin/sh
set -e

# PHP-FPM runs as www-data; artisan via `docker compose exec` often runs as root
# and leaves storage/bootstrap/cache unwritable for web requests.
for dir in storage bootstrap/cache; do
  if [ -d "/var/www/backend/$dir" ]; then
    chown -R www-data:www-data "/var/www/backend/$dir" 2>/dev/null || true
    chmod -R ug+rwx "/var/www/backend/$dir" 2>/dev/null || true
  fi
done

exec docker-php-entrypoint php-fpm
