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
 * @author Franck Cassedanne <franck at ouarz.net>
 *
 * @TODO: namespacing?!
 * @see http://code.google.com/p/memcached/wiki/NewProgrammingTricks
 * @TODO: tag set?!
 * @see http://dustin.github.com/2011/02/17/memcached-set.html
 *
 */
class Memcached extends AbstractCache
{

    /**
     * Constructor.
     *
     * @param \Memcached $Memcached A Memcached instance.
     * @param array      $options   Array of options.
     */
    public function __construct(\Memcached $memcached, array $options=null)
    {
        // default options
        $this->options['prefix_key'] = 'key_'; // prefix cache keys
        $this->options['prefix_tag'] = 'tag_'; // prefix cache tags
        $this->options['prefix_idx'] = 'idx_'; // prefix cache indexes
        $this->options['prefix_nsp'] = 'nsp_'; // prefix cache namespaces

        $this->options['serializer'] = 'php'; // none, php, json, igBinary.

        parent::__construct($memcached, $options);

        if ($this->options['tag_enable']) {
            $memcached->setOption(\Memcached::OPT_COMPRESSION, false);
        } else {
            $memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
        }

        // TODO: Memcached::SERIALIZER_PHP or Memcached::SERIALIZER_IGBINARY

        $this->setSerializer($this->options['serializer']);

        $this->setNamespace($this->options['prefix_nsp']);
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
    public function save($data, $key, array $tags=null, $ttl=null)
    {
        $ttl = $this->sanitiseTtl($ttl);

        $mKey = $this->mapKey($key);

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

        $this->adapter->deleteMulti($items);

        return (boolean) $this->adapter->getResultCode() != \Memcached::RES_FAILURE;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $_key = $this->mapKey($key);
        $items = array( $_key );

        if ($this->options['tag_enable']) {
            $idx = $this->mapIdx($key);

            // load the index of the key
            $tags = $this->getIndex($idx)->load();

            if (is_array($tags)) {
                // mark the key as deleted in the tags.
                foreach ($tags as $tag) {
                    $this->getIndex($this->mapTag($tag))->remove($_key);
                }
                // delete that index
                $items[] = $idx;
            }
        }
        $this->adapter->deleteMulti($items);

        return (boolean) $this->adapter->getResultCode() != \Memcached::RES_FAILURE;
    }

    /**
     * {@inheritdoc}
     */
    public function flush($all=false)
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
     * Purges expired items.
     *
     * @return boolean Returns True on success or False on failure.
     */
    public function purge()
    {
        // purge expired indexes
        // $this->adapter->cas(indexName, $cas_token, $tring);
    }

    /**
     * {@inheritdoc}
     */
    public function setSerializer($serializer)
    {
        switch ($serializer) {
            case 'igBinary':
                // @codeCoverageIgnoreStart
                if (function_exists('igbinary_serialize')) {
                    $opt = \Memcached::SERIALIZER_IGBINARY;
                }
                // @codeCoverageIgnoreEnd
                break;

            case 'json':
                $opt = \Memcached::SERIALIZER_JSON;
                break;

            case 'php':
                $opt = \Memcached::SERIALIZER_PHP;
                break;

            default:
                $opt = null;
        }

        if (null !== $opt) {
            $this->getAdapter()->setOption(\Memcached::OPT_SERIALIZER, $opt);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSerializer()
    {
        return $this->getAdapter()->getOption(\Memcached::OPT_SERIALIZER);
    }

    /**
     * Retrieves the cache item for the given id.
     *
     * @param  string     $id        The cache id to retrieve.
     * @param  float      $cas_token The variable to store the CAS token in.
     * @return mixed|null Returns the cached data or null.
     */
    public function get($id, &$cas_token=null)
    {
        $data = $this->adapter->get($id, null, $cas_token);

        return $this->adapter->getResultCode() == \Memcached::RES_SUCCESS
                ? $data
                : null;
    }

    /**
     * Returns the ttl sanitased for this cache adapter.
     *
     * @http://php.net/manual/en/memcached.expiration.php
     *
     * @param  integer|null $ttl The time-to-live in seconds.
     * @return int
     */
    public function sanitiseTtl($ttl)
    {
        return $ttl > 2592000 ? time()+$ttl : $ttl;
    }

    /**
     * Retrieves the cache item for the given id.
     *
     * @param  string     $id The cache id to retrieve.
     * @return mixed|null Returns the cached data or null.
     */
    public function getIndex($idx)
    {
        return new MemcachedIndex($this, $idx);
    }

    /**
     * Sets the namespace prefix.
      *
     * For memcache purpose, this is set as 'ns'+integer.
     *
     * @param string $prefix
     */
    public function setNamespace($ns, $renew=false, $suffix='_')
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

        $ns .= $counter . $suffix;
        $this->adapter->setOption(\Memcached::OPT_PREFIX_KEY, $ns);

        return $counter;
    }

    /**
     * Sets the namespace.
     *
     * @param string $prefix
     */
    public function getNamespace()
    {
        return $this->adapter->getOption(\Memcached::OPT_PREFIX_KEY);
    }

    /**
     * Returns a prefixed and sanitased cache id.
     *
     * @param  string $key The base key to prefix.
     * @return string
     */
    public function mapIdx($key)
    {
        return $this->sanitise($this->options['prefix_idx'] . $key);
    }

    /**
     * Increments the value of the given key.
     *
     * @param  string  $key The key to increment.
     * @return Returns the new item's value on success or FALSE on failure.
     */
    public function increment($key)
    {
        // Increment/decrement will initialize the value (if not available)
        // only when OPT_BINARY_PROTOCOL is set to true!
        if (true === \Memcached::OPT_BINARY_PROTOCOL) {
            $counter = $this->adapter->increment($key, 1);
        } else {
            $counter = $this->adapter->get($key);
            if (false === $counter) {
                $counter = 1;
                $this->adapter->set($key, $counter);
            } else {
                $counter = $this->adapter->increment($key);
            }
        }

        return $counter;
    }

}
