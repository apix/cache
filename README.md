APIx Cache, cache-tagging for PHP [![Build Status](https://travis-ci.org/frqnck/apix-cache.png?branch=master)](https://travis-ci.org/frqnck/apix-cache)
================================
[![Latest Stable Version](https://poser.pugx.org/apix/cache/v/stable.svg)](https://packagist.org/packages/apix/cache)  [![Build Status](https://scrutinizer-ci.com/g/frqnck/apix-cache/badges/build.png?b=master)](https://scrutinizer-ci.com/g/frqnck/apix-cache/build-status/master)  [![Code Quality](https://scrutinizer-ci.com/g/frqnck/apix-cache/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/frqnck/apix-cache/?branch=master)  [![Code Coverage](https://scrutinizer-ci.com/g/frqnck/apix-cache/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/frqnck/apix-cache/?branch=master)  [![License](https://poser.pugx.org/apix/cache/license.svg)](https://packagist.org/packages/apix/cache)

APIx Cache is a generic and thin cache wrapper with a simple interface to various different caching backends and emphasising cache **tagging** and **indexing**.

> Cache-tagging allows to find/update all data items with one or more given tags. Providing, for instance, a batch delete of all obsolete entries matching a speficic tag such as a version string.

* **PSR-6** (Cache) standard is provided thru a factory wrapper class.
* Fully unit **tested** and compliant with PSR-1, PSR-2, PSR-4 and PSR-6.
* Continuously integrated
  * with **PHP** ~~5.3~~, **5.4**, **5.5**, **5.6** and **7.0**,
  * and against APC, Redis, MongoDB, Sqlite, MySQL, PgSQL and Memcached...
* Available as a **[Composer](https://packagist.org/packages/apix/cache)** ~~and as a [PEAR](http://pear.ouarz.net)~~ package.

---

Cache backends
--------------
Currently, the following cache store are supplied:

* **[APC](http://php.net/book.apc.php)** (and **[APCu](http://pecl.php.net/package/APCu)**) *with tagging support*,
* **[Redis](http://redis.io)** using the [PhpRedis](https://github.com/nicolasff/phpredis) extension *with tagging support*,
* **[MongoDB](http://www.mongodb.org/)** using the [mongo](http://php.net/book.mongo.php) native PHP extension *with tagging support*,
* **[Memcached](http://memcached.org/)** using the [Memcached](http://php.net/book.memcached.php) extension *with indexing, tagging and namespacing support*,
* and relational databases usign **[PDO](http://php.net/book.pdo.php)** *with tagging support*:
 * Dedicated drivers (fully tested) for **[SQLite](http://www.sqlite.org)**, **[PostgreSQL](http://www.postgresql.org)** and **[MySQL](http://www.mysql.com)** (works with MariaDB and Percona).
 * A generic `Sql1999` driver for [4D](http://www.4d.com/), [Cubrid](http://www.cubrid.org), [SQL Server](http://www.microsoft.com/sqlserver), [Sybase](http://www.sybase.com), [Firebird](http://www.firebirdsql.org), [ODBC](https://en.wikipedia.org/wiki/Open_Database_Connectivity), [Interbase](http://www.embarcadero.com/products/interbase), [IBM DB2](www.ibm.com/software/data/db2/), [IDS](http://www-01.ibm.com/software/data/informix/), [Oracle](http://www.oracle.com/database)...
* Runtime (in-memory array storage).
* **[Filesystem](#filesystem-specific)** (directories based, and files based) *with tagging support*.

Feel free to comment, send pull requests and patches...

Factory usage (PSR-Cache wrapper)
-------------

```php
  use Apix\Cache;

  $backend = new \Redis();
  # $backend = new \PDO('...');      // Any supported client object e.g. '\Redis', '\MongoClient', ...
  # $backend = new Cache\Files(...); // or one that implements \Apix\Cache\Adapter
  # $backend = 'apc';                // or an adapter name (string) e.g. "APC", "Runtime"
  # $backend = new MyArrayObject();  // or even a plain array() or \ArrayObject.

  $pool = Cache\Factory::getPool($backend);               // without tagging support
  # $pool = Cache\Factory::getTaggablePool($backend);     // with tagging!
  
  $item = $pool->getItem('wibble_id');
  
  if ( !$item->isHit() ) {
    $data = compute_expensive_stuff();
    $item->set($data);
    $pool->save($item);
  }

  return $item->get();
```

Basic usage (APIx native)
-----------

```php
  use Apix\Cache;

  $cache = new Cache\Apc;

  // try to retrieve 'wibble_id' from the cache
  if ( !$data = $cache->load('wibble_id') ) {
    
    // otherwise, get some data from the origin
    // example of arbitrary mixed data
    $data = array('foo' => 'bar');

    // and save it to the cache
    $cache->save($data, 'wibble_id');
  }
```
You can also use the folowing in your use cases: 
```php
  // save $data to the cache as 'wobble_id',
  // tagging it along the way as 'baz' and 'flob',
  // and set the ttl to 300 seconds (5 minutes)
  $cache->save($data, 'wobble_id', array('baz', 'flob'), 300);

  // retrieve all the cache ids under the tag 'baz'
  $ids = $cache->loadTag('baz');

  // clear out all items with a 'baz' tag
  $cache->clean('baz');

  // remove the named item
  $cache->delete('wibble_id');

  // flush out the cache (of all -your- stored items)
  $cache->flush();
```

Advanced usage (APIx native)
--------------
###  Options shared by all the backends
```php
  use Apix\Cache;
  
  // default options
  $options = array(
      'prefix_key'  => 'apix-cache-key:', // prefix cache keys
      'prefix_tag'  => 'apix-cache-tag:', // prefix cache tags
      'tag_enable'  => true               // wether to enable tags support
  );

  // start APC as a local cache
  $local_cache = new Cache\Apc($options);
```
### Redis specific
```php
  // additional (default) options
  $options['atomicity']  = false;    // false is faster, true is guaranteed
  $options['serializer'] = 'php';    // null, php, igbinary and json

  $redis_client = new \Redis;        // instantiate phpredis*
  $distributed_cache = new Cache\Redis($redis_client, $options);
```
\* see [PhpRedis](https://github.com/nicolasff/phpredis) for instantiation usage.

### MongoDB specific 
```php
  // additional (default) options
  $options['object_serializer'] = 'php';  // null, json, php, igBinary
  $options['db_name'] = 'apix';           // name of the mongo db
  $options['collection_name'] = 'cache';  // name of the mongo collection

  $mongo  = new \MongoClient;             // MongoDB native driver** instance
  $cache = new Cache\Mongo($mongo, $options);
```
\*\* see [MongoDB](http://php.net/manual/en/book.mongo.php) for instantiation usage.

### Memcached specific
```php
  // additional (default) options, specific to Memcached
  $options['prefix_key'] = 'key_';  // prefix cache keys
  $options['prefix_tag'] = 'tag_';  // prefix cache tags
  $options['prefix_idx'] = 'idx_';  // prefix cache indexes
  $options['prefix_nsp'] = 'nsp_';  // prefix cache namespaces
  $options['serializer'] = 'php';   // null, php, json, igbinary.

  $memcached  = new \Memcached;     // a Memcached*** instance
  $shared_cache = new Cache\Memcached($memcached, $options);
```
\*\*\* see [Memcached](http://php.net/manual/en/book.memcached.php) for instantiation details.
### PDO specific
```php
  // additional (default) options, specific to the PDO backends
  $options['db_table']   = 'cache';       // table to hold the cache
  $options['serializer'] = 'php';         // null, php, igbinary, json
  $options['preflight']  = true;          // wether to preflight the DB
  $options['timestamp']  = 'Y-m-d H:i:s'; // the timestamp DB format

  // with SQLITE
  $dbh = new \PDO('sqlite:/tmp/apix_tests.sqlite3');
  $relational_cache = new Cache\Pdo\Sqlite($dbh, $options);

  // with MYSQL, MariaDB and Percona
  $dbh = new \PDO('mysql:host=xxx;port=xxx;dbname=xxx', 'user', 'pass');
  $mysql_cache = new Cache\Pdo\Mysql($dbh, $options);

  // with PGSQL
  $dbh = new \PDO('pgsql:dbname=xxx;host=xxx', 'xxx', 'xxx');
  $postgres_cache = new Cache\Pdo\Pgsql($dbh, $options);

  // with a SQL:1999 compliant DB, e.g. Oracle
  $dbh = new \PDO('oci:dbname=xxx', 'xxx', 'xxx');
  $sql1999_cache = new Cache\Pdo\Sql1999($dbh, $options);
```

Note if `preflight` is set to `true` (the default), the required tables, if missing, will be created on-the-fly.
Once these tables exist, set `preflight` to `false` in order to avoid some additional checks... 

### Filesystem specific
```php
  // additional (default) options
  $options['directory'] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'apix-cache'; // Directory where cache is created
  $options['locking'] = true;                                                      // File locking (recommended)
  
  $files_cache = new Cache\Files($options);
  // or
  $directory_cache = new Cache\Directory($options);
```

Installation
------------------------

Install the current major version using Composer with (recommended)
```
$ composer require apix/cache:1.2.*
```

Or install the latest stable version with
```
$ composer require apix/cache
```

License
-------
APIx Cache is licensed under the New BSD license -- see the `LICENSE.txt` for the full license details.
