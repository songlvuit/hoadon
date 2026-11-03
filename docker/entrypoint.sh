#!/bin/sh
set -e
cd /var/www/html

# Thư mục lưu file (volume) có thể trống ở lần chạy đầu
mkdir -p storage/app/private storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache

if [ -z "$APP_KEY" ]; then
  echo "LỖI: chưa đặt APP_KEY trong .env. Tạo khoá: docker compose run --rm --entrypoint php app artisan key:generate --show" >&2
  exit 1
fi

echo "Chờ PostgreSQL sẵn sàng..."
until pg_isready -h "${DB_HOST:-db}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-hoadon}" >/dev/null 2>&1; do
  sleep 2
done

php artisan migrate --force
php artisan db:seed --force
php artisan optimize
php artisan filament:optimize

exec "$@"
