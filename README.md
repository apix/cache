Apix Cache - Caching for PHP5.3+
================================

Apix Cache is a generic cache wrapper with a simple interface to various different caching backends.

Some of its features:

* Provides cache tagging -- natively and through emulation.
* 100% unit tested and compliant with PSR0, PSR1 and PSR2.
* Available as a PEAR and as a Composer package.

Cache backends
--------------
Currently, the following cache store are available:
* APC
* Redis (via PhpRedis)
* more to come...

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
      $data = $cache->load('wibble_id');

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
      'tag_enable'  => true,              // wether to enable tags support

  );
  $local_cache = new Apix\Cache\Apc($options);

  // additional options, specific to Redis
  $options['atomicity']  = false;         // false is faster, true is guaranteed
  $options['serializer'] = igBinary;      // none, php, igBinary

  $redis_instance = new \Redis;           // instantiate phpredis*
  $distributed_cache = new Apix\Cache\Redis($redis_instance, $options);
```

\* see [phpredis](https://github.com/nicolasff/phpredis) for more details and usage.

Installation
------------------------

* If you are creating a component that relies on Apix Cache locally:

  * either update your **`composer.json`** file:

    ```json
    {
      "require": {
        "apix/cache": "1.0.*"
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
Apix Cache is licensed under the New BSD license -- see the `LICENSE.txt` for the full license details.