#!/bin/bash
set -e
set -o pipefail

VERSION=`phpenv version-name`

if [ "${VERSION}" = 'hhvm' ]; then
    PHPINI=/etc/hhvm/php.ini
else
    PHPINI=~/.phpenv/versions/$VERSION/etc/php.ini
fi

# Install APC/APCu
if [ "$DB" = "apc" ]; then
    if  [ "${VERSION}" = "hhvm" ] || [ "$(expr "${VERSION}" "<" "5.5")" -eq 1 ]
    then
        echo "extension = apc.so" >> $PHPINI
    else
        echo "yes" | pecl install apcu-beta
    fi
    echo "apc.enable_cli = 1" >> $PHPINI
fi

composer self-update
composer install --dev --prefer-source --no-interaction 