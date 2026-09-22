FROM composer:lts AS deps

WORKDIR /app

RUN --mount=type=bind,source=composer.json,target=composer.json \
    --mount=type=bind,source=composer.lock,target=composer.lock \
    --mount=type=cache,target=/tmp/cache \
    composer install --no-dev --no-interaction

################################################################################

FROM php:8.5-apache AS final

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Install mod_xsendfile extension
RUN apt-get update \
    && apt-get install -y --no-install-recommends libapache2-mod-xsendfile \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod xsendfile

COPY --from=deps /app/vendor/ /var/www/html/vendor

COPY . /var/www/html

# SQLite data directory
RUN mkdir -p /var/www/data \
    && chown -R www-data:www-data /var/www/data

# Apache configuration
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

USER www-data

ENV KITTYSHARE_FILE_SERVER=x-sendfile
ENV KITTYSHARE_DATABASE_PATH=/var/www/data/database.sqlite
ENV KITTYSHARE_ROOT=/data/files
