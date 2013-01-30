APIx Cache, caching for PHP 5.3+   [![Build Status](https://travis-ci.org/frqnck/apix-cache.png?branch=master)](https://travis-ci.org/frqnck/apix-cache)
==============================

APIx Cache is a generic and thin cache wrapper with a simple interface to various different caching backends.

Some of its features:

* Provides cache **tagging** and **indexing** -- *natively* and/or through *emulation*.
* **100%** unit **tested** and compliant with PSR0, PSR1 and PSR2.
* Available as a **Composer** and as a **PEAR** package.

Cache backends
--------------
Currently, the following cache store handlers are supported:

* **[APC](http://php.net/book.apc.php)** *with tagging support*,
* **[Redis](http://redis.io)** using the [PhpRedis](https://github.com/nicolasff/phpredis) extension *with tagging support*,
* **[MongoDB](http://www.mongodb.org/)** using the [mongo](http://php.net/book.mongo.php) native PHP extension *with tagging support*,
* **[Memcached](http://memcached.org/)** using the [Memcached](http://php.net/book.memcached.php) extension *with indexing, tagging and namespacing support*,
* and relational databases usign **[PDO](http://php.net/book.pdo.php)** *with tagging support*:
 * Fully tested with **[SQLite](http://www.sqlite.org)**, **[PostgreSQL](http://www.postgresql.org)** and **[MySQL](http://www.mysql.com)**.
 * Assumed to work but not tested [4D](http://www.4d.com/), [Cubrid](http://www.cubrid.org), [MS SQL Server](http://www.microsoft.com/sqlserver/), [Sybase](http://www.sybase.com), [Firebird](http://www.firebirdsql.org), ODBC, [Interbase](http://www.embarcadero.com/products/interbase), [IBM DB2](www.ibm.com/software/data/db2/), [IDS](http://www-01.ibm.com/software/data/informix/) and [Oracle](http://www.oracle.com/database/).

Feel free to comment, send pull requests and patches...

Basic usage
-----------

```php
  <?php
      $cache = new Apix\Cache\Apc;

      // some arbitrary mixed data
      $data = array('foo'=>'bar');

      // save data to the cache as 'wibble_id' (and use the default ttl).
      $cache->save($data, 'wibble_id');

      // Save data to the cache as 'wobble_id',
      // tagging it along the way as 'baz' and 'flob',
      // and set the ttl to 300 seconds (5 minutes)
      $cache->save($data, 'wobble_id', array('baz', 'flob'), 300);

      // retrieve 'wibble_id' from the cache
      $data = $cache->loadKey('wibble_id');

      // retrieve the cache ids under the tag 'baz'
      $ids = $cache->loadTag('baz');


      // clear out all items with a 'baz' tag
      $cache->clean('baz');

      // remove the named item
      $cache->delete('wibble_id');

      // flush out the cache (of all -YOUR- stored items)
      $cache->flush();
```

Available options
-----------------

```php
<?php
  // default options, common to all backends
  $options = array(
      'prefix_key'  => 'apix-cache-key:', // prefix cache keys
      'prefix_tag'  => 'apix-cache-tag:', // prefix cache tags
      'tag_enable'  => true               // wether to enable tags support
  );
  $local_cache = new Apix\Cache\Apc($options);

  // additional (default) options, specific to Redis
  $options['atomicity']  = false;         // false is faster, true is guaranteed
  $options['serializer'] = 'igBinary';    // null, php, igBinary and json

  $redis_client = new \Redis;             // instantiate phpredis*
  $distributed_cache = new Apix\Cache\Redis($redis_client, $options);

  // additional (default) options, specific to Mongo
  $options['object_serializer'] = 'php';  // null, json, php, igBinary

  $mongo_client  = new \MongoClient;       // MongoDB native driver** instance
  $another_cache = new Apix\Cache\Mongo($mongo_client, $options);

  // additional (default) options, specific to PDO
  $options['db_table']   = 'cache';
  $options['serializer'] = 'php';         // null, php, igBinary, json.

  $db = new new \PDO('pgsql:dbname=apix_tests;host=127.0.0.1', 'postgres';
  $relational_cache = new Apix\Cache\Pdo\Sqlite($db, $options);
```

\* see [phpredis](https://github.com/nicolasff/phpredis) for more details and usage.
\*\* see [MongoDB](http://php.net/manual/en/book.mongo.php) for more details and usage.

Installation
------------------------

* If you are creating a component that relies on Apix Cache locally:

  * either update your **`composer.json`** file:

    ```json
    {
      "require": {
        "apix/cache": "1.1.*"
      }
    }
    ```

  * or update your **`package.xml`** file as follow:

    ```xml
    <dependencies>
      <required>
        <package>
          <name>apix_cache</name>
          <channel>pear.ouarz.net</channel>
          <min>1.0.0</min>
          <max>1.999.9999</max>
        </package>
      </required>
    </dependencies>
    ```
* For a system-wide installation using PEAR:

    ```
    sudo pear channel-discover pear.ouarz.net
    sudo pear install --alldeps ouarz/apix_cache
    ```
For more details see [pear.ouarz.net](http://pear.ouarz.net).

License
-------
APIx Cache is licensed under the New BSD license -- see the `LICENSE.txt` for the full license details.
