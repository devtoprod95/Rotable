#!/bin/sh
set -e

composer install --no-interaction --prefer-dist --optimize-autoloader

exec php-fpm
