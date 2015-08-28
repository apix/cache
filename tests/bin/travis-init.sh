#!/bin/bash
set -e
set -o pipefail

PHP_INI_FILE=$(php -r 'echo php_ini_loaded_file();')

# Install APC/APCu
if [ "$DB" = "apc" ]; then
    if  [ "$TRAVIS_PHP_VERSION" = "hhvm" ] || [ "$(expr "$TRAVIS_PHP_VERSION" "<" "5.5")" -eq 1 ]; then
        echo "extension = apc.so" >> $PHP_INI_FILE
    else
        echo "yes" | pecl install apcu-beta
        echo "extension = apcu.so" >> $PHP_INI_FILE
    fi
fi
echo "apc.enable_cli = 1" >> $PHP_INI_FILE

composer self-update
composer install --dev --prefer-source --no-interaction 