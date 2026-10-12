#!/bin/sh
set -eu

mkdir -p /var/www/html/storage/logs/nginx
touch /var/www/html/storage/logs/nginx/error.log
chown -R 1000:1000 /var/www/html/storage/logs/nginx

if [ "$#" -eq 0 ]; then
	set -- nginx -g 'daemon off;'
fi

exec /docker-entrypoint.sh "$@"
