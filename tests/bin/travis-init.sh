#!/bin/bash
set -e
set -o pipefail

VERSION=`phpenv version-name`

function pecl_install()
{
    local _PKG=$1
    local _VERSION=$2

    pecl shell-test $_PKG || (echo "yes" | pecl install $_PKG-$_VERSION)
}

if [ "${VERSION}" = "hhvm" ]; then
    PHPINI=/etc/hhvm/php.ini
else
    PHPINI=~/.phpenv/versions/$VERSION/etc/php.ini

    # update PECL
    pecl channel-update pecl.php.net

    # install igbinary
    pecl_install igbinary 2.0.1

    # install msgpack
    if [ "$(expr "${VERSION}" "<" "7.0")" -eq 1 ]
    then
        pecl_install msgpack 0.5.7
    else
        pecl_install msgpack 2.0.2
    fi
fi

# enable xdebug
if [[ $TRAVIS_PHP_VERSION =~ ^hhvm ]]
then
    echo 'xdebug.enable = On' >> $PHPINI
fi

#
# APC
#
if [ "$DB" = "apc" ]; then
    if [ "${VERSION}" = "hhvm" ] || [ "$(expr "${VERSION}" "<" "5.5")" -eq 1 ]
    then
        echo "extension = apc.so" >> $PHPINI
    elif [ "$(expr "${VERSION}" "<" "7.0")" -eq 1 ]
    then
        pecl_install apcu 4.0.11
    else
        pecl_install apcu 5.1.8
    fi
    echo "apc.enable_cli = 1" >> $PHPINI
fi

#
# Redis
#
if [ "$DB" = "redis" ]; then
    git clone --branch=master --depth=1 git://github.com/nicolasff/phpredis.git phpredis
    cd phpredis && phpize && ./configure && make && sudo make install && cd ..
    rm -fr phpredis
    echo "extension = redis.so" >> $PHPINI
fi

#
# Mongo DB
#
if [ "$DB" = "mongodb" ]; then
    if [ "${VERSION}" != "hhvm" ] && [ "$(expr "${VERSION}" "<" "7.0")" -eq 1 ]
    then
        echo "extension = mongo.so" >> $PHPINI
    else
        if [ "${VERSION}" = "hhvm" ]
        then
            # Instal HHVM
            sudo apt-get install -y cmake
            git clone git://github.com/facebook/hhvm.git
            cd hhvm && git checkout 1da451b && cd -  # Tag:3.0.1
            export HPHP_HOME=`pwd`/hhvm

            # Install mongo-hhvm-driver
            git clone https://github.com/mongodb/mongo-hhvm-driver.git --branch 1.1.3
            cd mongo-hhvm-driver
            git submodule sync && git submodule update --init --recursive
            $HPHP_HOME/hphp/tools/hphpize/hphpize
            cmake . && make configlib && make -j 2 && make install
            echo "hhvm.dynamic_extensions[mongodb] = mongodb.so" >> $PHPINI
        fi
        echo "extension = mongodb.so" >> $PHPINI
        composer require "mongodb/mongodb=^1.0.0"
    fi
fi

#
# MySQL
#
if [ "$DB" = "mysql" ]; then
    mysql -e 'CREATE DATABASE IF NOT EXISTS apix_tests;'
fi

#
# Postgres
#
if [ "$DB" = "pgsql" ]; then
    psql -c 'DROP DATABASE IF EXISTS apix_tests;' -U postgres
    psql -c 'CREATE DATABASE apix_tests;' -U postgres
fi

#
# Memcached
#
if [ "$DB" = "memcached" ]; then
    echo "extension = memcached.so" >> $PHPINI
fi
