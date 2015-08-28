#!/bin/bash
set -e
set -o pipefail

VERSION=`phpenv version-name`

if [ "${VERSION}" = 'hhvm' ]; then
    PHPINI=/etc/hhvm/php.ini
else
    PHPINI=~/.phpenv/versions/$VERSION/etc/php.ini
    echo "yes" | pecl install igbinary
fi

if [ "$DB" = "apc" ]; then
    if  [ "${VERSION}" = "hhvm" ] || [ "$(expr "${VERSION}" "<" "5.5")" -eq 1 ]
    then
        echo "extension = apc.so" >> $PHPINI
    else
        echo "yes" | pecl install apcu-beta
    fi
    echo "apc.enable_cli = 1" >> $PHPINI
fi

if [ "$DB" = "redis" ]; then
    git clone --branch=master --depth=1 git://github.com/nicolasff/phpredis.git phpredis
    cd phpredis && phpize && ./configure && make && sudo make install && cd ..
    rm -fr phpredis
    echo "extension = redis.so" >> $PHPINI
fi

if [ "$DB" = "mongodb" ]; then
    echo "extension = mongo.so" >> $PHPINI
fi

if [ "$DB" = "mysql" ]; then
    mysql -e 'CREATE DATABASE IF NOT EXISTS apix_tests;'
fi

if [ "$DB" = "pgsql" ]; then
    psql -c 'DROP DATABASE IF EXISTS apix_tests;' -U postgres
    psql -c 'CREATE DATABASE apix_tests;' -U postgres
fi

if [ "$DB" = "memcached" ]; then
    echo "extension = memcached.so" >> $PHPINI
fi

# igbinary