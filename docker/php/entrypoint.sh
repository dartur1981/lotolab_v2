#!/bin/bash
set -e

mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/data/historicos
mkdir -p /var/www/logs

chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/data /var/www/logs 2>/dev/null || true

exec docker-php-entrypoint "$@"
