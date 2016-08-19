#!/bin/bash
set -e
set -o pipefail

VERSION=`phpenv version-name`

if [ "${VERSION}" = "hhvm" ]; then
    PHPINI=/etc/hhvm/php.ini
    HPHP_TOOLS=`pwd`/hhvm/hphp/tools
else
    PHPINI=~/.phpenv/versions/$VERSION/etc/php.ini

    # update PECL
    pecl channel-update pecl.php.net

    # install igbinary
    echo "yes" | pecl install igbinary

    # install msgpack
    if [ "$(expr "${VERSION}" "<" "7.0")" -eq 1 ]
    then
        echo "yes" | pecl install msgpack-0.5.7
    else
        echo "yes" | pecl install msgpack-2.0.1
    fi
fi

# enable xdebug
if [[ $TRAVIS_PHP_VERSION =~ ^hhvm ]]
then
    echo 'xdebug.enable = On' >> $PHPINI
fi

if [ "$DB" = "apc" ]; then
    if [ "${VERSION}" = "hhvm" ] || [ "$(expr "${VERSION}" "<" "5.5")" -eq 1 ]
    then
        echo "extension = apc.so" >> $PHPINI
    elif [ "$(expr "${VERSION}" "<" "7.0")" -eq 1 ]
    then
        echo "yes" | pecl install apcu-4.0.10
    else
        echo "yes" | pecl install apcu-5.1.2
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
    if [ "${VERSION}" != "hhvm" ] && [ "$(expr "${VERSION}" "<" "7.0")" -eq 1 ]
    then
        echo "extension = mongo.so" >> $PHPINI
    else
        if [ "${VERSION}" = "hhvm" ]
        then
            git clone https://github.com/mongodb/mongo-hhvm-driver.git --branch 1.1.3
            cd mongo-hhvm-driver
            git submodule sync && git submodule update --init --recursive
            $HPHP_TOOLS/hphpize/hphpize && cmake . && make configlib && make -j 2 && make install
            echo "hhvm.dynamic_extensions[mongodb] = mongodb.so" >> $PHPINI
        fi
        echo "extension = mongodb.so" >> $PHPINI
        composer require "mongodb/mongodb=^1.0.0"
    fi
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
