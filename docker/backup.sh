#!/bin/sh
# Sao lưu database + file hoá đơn vào ./backups (giữ 14 bản gần nhất).
# Đặt cron trên VPS:  15 2 * * * cd /opt/xuat-hoa-don && sh docker/backup.sh >> backups/backup.log 2>&1
set -e
cd "$(dirname "$0")/.."
. ./.env
STAMP=$(date +%Y%m%d-%H%M)
mkdir -p backups

docker compose exec -T db pg_dump -U "$DB_USERNAME" -d "$DB_DATABASE" | gzip > "backups/db-$STAMP.sql.gz"
docker compose exec -T app tar -czf - -C /var/www/html/storage app > "backups/files-$STAMP.tar.gz"

ls -1t backups/db-*.sql.gz | tail -n +15 | xargs -r rm --
ls -1t backups/files-*.tar.gz | tail -n +15 | xargs -r rm --
echo "Sao lưu xong: $STAMP"
