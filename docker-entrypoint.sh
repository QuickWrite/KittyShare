#!/bin/sh

set -eu

# Migrates the SQLite schema exactly once at container startup
php /var/www/html/bin/migrate

# Run Apache Webserver
exec apache2-foreground "$@"
