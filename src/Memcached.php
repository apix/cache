<?php
/**
 *
 * This file is part of the Apix Project.
 *
 * (c) Franck Cassedanne <franck at ouarz.net>
 *
 * @license     http://opensource.org/licenses/BSD-3-Clause  New BSD License
 *
 */

namespace Apix\Cache;

/**
 * Memcached cache wrapper.
 *
 * @see http://code.google.com/p/memcached/wiki/NewProgrammingTricks
 * @see http://dustin.github.com/2011/02/17/memcached-set.html
 *
 * @package Apix\Cache
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class Memcached extends AbstractCache
{
    /**
     * Holds an injected adapter.
     * @var \Memcached
     */
    protected $adapter = null;

    /**
     * Holds the array of TTLs.
     * @var array
     */
    protected $ttls = array();

    /**
     * Constructor.
     *
     * @param \Memcached $memcached A Memcached instance.
     * @param array      $options   Array of options.
     */
    public function __construct(\Memcached $memcached, array $options = null)
    {
        // default options
        $this->options['prefix_key'] = 'key_';  // prefix cache keys
        $this->options['prefix_tag'] = 'tag_';  // prefix cache tags
        $this->options['prefix_idx'] = 'idx_';  // prefix cache indexes
        $this->options['prefix_nsp'] = 'nsp_';  // prefix cache namespaces

        // 'auto' is igbinary or msgpack if available, php otherwise.
        $this->options['serializer'] = 'auto';   // auto, php, json, json_array
                                                 // igBinary and msgpack

        parent::__construct($memcached, $options);

        $memcached->setOption(\Memcached::OPT_COMPRESSION, false);

        if ($this->options['tag_enable']) {
            $memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, false);
            $this->setSerializer($this->options['serializer']);
            $this->setNamespace($this->options['prefix_nsp']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadKey($key)
    {
        return $this->get($this->mapKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function loadTag($tag)
    {
        return $this->getIndex($this->mapTag($tag))->load();
    }

    /**
     * {@inheritdoc}
     */
    public function save($data, $key, array $tags = null, $ttl = null)
    {
        $ttl = $this->sanitiseTtl($ttl);

        $mKey = $this->mapKey($key);

        $data = array('data' => $data, 'ttl' => $ttl);
        $this->ttls[$mKey] = $ttl;

        // add the item
        $success = $this->adapter->set($mKey, $data, $ttl);

        if ($success && $this->options['tag_enable'] && !empty($tags)) {

            // add all the tags to the index key.
            $this->getIndex($this->mapIdx($key))->add($tags);

            // append the key to each tag.
            foreach ($tags as $tag) {
                $this->getIndex($this->mapTag($tag))->add($mKey);
            }
        }

        return $success;
    }

    /**
     * Alias to `Memcached::deleteMulti` or loop `Memcached::delete`.
     *
     * @param array $items The items to be deleted.
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    protected function deleteMulti($items)
    {
        if (method_exists($this->adapter, 'deleteMulti')) {
            $this->adapter->deleteMulti($items);

            return (boolean) $this->adapter->getResultCode() != \Memcached::RES_FAILURE;
        }

        // Fix environments (some HHVM versions) that don't handle deleteMulti.
        // @see https://github.com/facebook/hhvm/issues/4602
        // @codeCoverageIgnoreStart
        $success = true;
        foreach ($items as $item) {
            $success = $this->adapter->delete($item) && $success;
        }

        return $success;
        // @codeCoverageIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    public function clean(array $tags)
    {
        $items = array();
        foreach ($tags as $tag) {
            $keys = $this->loadTag($tag);
            if (null !== $keys) {
                foreach ($keys as $key) {
                    $items[] = $key;
                    // $items[] = $this->mapIdx($key);
                }
            }
            // add the tag to deletion
            $items[] = $this->mapTag($tag);

            // add the index key for deletion
            // $items[] = $this->mapTag($tag);
        }

        return $this->deleteMulti($items);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $_key = $this->mapKey($key);
        $items = array($_key);

        if ($this->options['tag_enable']) {
            $idx_key = $this->mapIdx($key);

            // load the tags from the index key
            $tags = $this->getIndex($idx_key)->load();

            if (is_array($tags)) {
                // mark the key as deleted in the tags.
                foreach ($tags as $tag) {
                    $this->getIndex($this->mapTag($tag))->remove($_key);
                }
                // delete that index key
                $items[] = $idx_key;
            }
        }

        return $this->deleteMulti($items);
    }

    /**
     * {@inheritdoc}
     */
    public function flush($all = false)
    {
        if (true === $all) {
            return $this->adapter->flush();
        }
        $nsKey = $this->options['prefix_nsp'];

        // set a new namespace
        $success = $this->setNamespace($nsKey, true);

        return (boolean) $success;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $serializer
     */
    public function setSerializer($serializer)
    {
        switch ($serializer) {

            case 'php':
                $opt = \Memcached::SERIALIZER_PHP;
            break;

            // @codeCoverageIgnoreStart
            case 'igBinary':
                if (!\Memcached::HAVE_IGBINARY) {
                    continue;
                }
                $opt = \Memcached::SERIALIZER_IGBINARY;
            break;

            case 'json':
                if (!\Memcached::HAVE_JSON) {
                    continue;
                }
                $opt = \Memcached::SERIALIZER_JSON;
            break;

            case 'json_array':
                if (!\Memcached::HAVE_JSON_ARRAY) {
                    continue;
                }
                $opt = \Memcached::SERIALIZER_JSON_ARRAY;
            break;

            case 'msgpack':
                if (!\Memcached::HAVE_MSGPACK) {
                    continue;
                }
                $opt = \Memcached::SERIALIZER_MSGPACK;
            break;
            // @codeCoverageIgnoreEnd

            default:
        }

        if (isset($opt)) {
            $this->adapter->setOption(\Memcached::OPT_SERIALIZER, $opt);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSerializer()
    {
        return $this->adapter->getOption(\Memcached::OPT_SERIALIZER);
    }

    /**
     * Retrieves the cache item for the given id.
     *
     * @param string $id        The cache id to retrieve.
     * @param float  $cas_token The variable to store the CAS token in.
     *
     * @return mixed|null Returns the cached data or null.
     */
    public function get($id, &$cas_token = null)
    {
        $data = $this->adapter->get($id, null, $cas_token);
        if ($this->adapter->getResultCode() == \Memcached::RES_SUCCESS) {
            $this->ttls[$id] = isset($data['ttl']) ? $data['ttl'] : 0;

            return isset($data['data']) ? $data['data'] : $data;
        }

        return;
    }

    /**
     * Returns the ttl sanitased for this cache adapter.
     *
     * The number of seconds may not exceed 60*60*24*30 = 2,592,000 (30 days).
     *
     * @see http://php.net/manual/en/memcached.expiration.php
     *
     * @param int|null $ttl The time-to-live in seconds.
     *
     * @return int
     */
    public function sanitiseTtl($ttl)
    {
        return $ttl > 2592000 ? time() + $ttl : $ttl;
    }

    /**
     * Returns the named indexer.
     *
     * @param string $name The name of the index.
     *
     * @return Indexer\Adapter
     */
    public function getIndex($name)
    {
        return new Indexer\MemcachedIndexer($name, $this);
    }

    /**
     * Sets the namespace prefix.
     * Specific to memcache; this sets as 'ns'+integer (incremented).
     *
     * @param string $ns
     * @param bool   $renew
     * @param string $suffix
     *
     * @return int
     */
    public function setNamespace($ns, $renew = false, $suffix = '_')
    {
        // temporally set the namespace to null
        $this->adapter->setOption(\Memcached::OPT_PREFIX_KEY, null);

        // mark the current namespace for future deletion
        $this->getIndex($this->mapIdx($ns))->remove($this->getNamespace());

        if ($renew) {
            // increment the namespace counter
            $counter = $this->increment($ns);
        } else {
            $counter = $this->adapter->get($ns);
            if (false === $counter) {
                $counter = 1;
                $this->adapter->set($ns, $counter);
            }
        }

        $ns .= $counter.$suffix;
        $this->adapter->setOption(\Memcached::OPT_PREFIX_KEY, $ns);

        return $counter;
    }

    /**
     * Returns the namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->adapter->getOption(\Memcached::OPT_PREFIX_KEY);
    }

    /**
     * Returns a prefixed and sanitased cache id.
     *
     * @param string $key The base key to prefix.
     *
     * @return string
     */
    public function mapIdx($key)
    {
        return $this->sanitise($this->options['prefix_idx'].$key);
    }

    /**
     * Increments the value of the given key.
     *
     * @param string $key The key to increment.
     *
     * @return int|bool Returns the new item's value on success or FALSE on failure.
     */
    public function increment($key)
    {
        // if (true === \Memcached::OPT_BINARY_PROTOCOL) {
        //     // Increment will initialize the value (if not available)
        //     // only when OPT_BINARY_PROTOCOL is set to true!
        //     return $this->adapter->increment($key, 1);
        // }
        $counter = $this->adapter->get($key);
        if (false === $counter) {
            $counter = 1;
            $this->adapter->set($key, $counter);
        } else {
            $counter = $this->adapter->increment($key);
        }

        return $counter;
    }

    /**
     * {@inheritdoc}
     *
     * The number of seconds may not exceed 60*60*24*30 = 2,592,000 (30 days).
     * 
     * @param string $key       The cache key to retrieve.
     * @param float  $cas_token The variable to store the CAS token in.
     *
     * @see http://php.net/manual/en/memcached.expiration.php
     */
    public function getTtl($key, &$cas_token = null)
    {
        $mKey = $this->mapKey($key);

        if (!isset($this->ttls[$mKey])) {
            $data = $this->adapter->get($mKey, null, $cas_token);
            $this->ttls[$mKey] =
                 $this->adapter->getResultCode() == \Memcached::RES_SUCCESS
                    ? (isset($data['ttl']) ? $data['ttl'] : 0)
                    : false;
        }

        return $this->ttls[$mKey];
    }
}
