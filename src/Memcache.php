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
 * Memcache cache wrapper.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 *
 */
class Memcache extends AbstractCache
{
    /**
     * Holds an injected adapter.
     * @var \Memcache
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
     * @param \Memcache $memcache A Memcache instance.
     * @param array     $options  Array of options.
     */
    public function __construct(\Memcache $memcache, array $options = null)
    {
        // default options
        $this->options['prefix_key'] = 'key_';  // prefix cache keys
        $this->options['prefix_tag'] = 'tag_';  // prefix cache tags
        $this->options['prefix_idx'] = 'idx_';  // prefix cache indexes
        $this->options['prefix_nsp'] = 'nsp_';  // prefix cache namespaces

        $this->options['serializer'] = 'php';   // none, php, json, igBinary.

        parent::__construct($memcache, $options);

        if ($this->options['tag_enable']) {
            $this->setSerializer($this->options['serializer']);
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
                }
            }

            // add the tag to deletion
            $items[] = $this->mapTag($tag);
        }

        $success = true;

        foreach ($items as $item) {
            $success = $this->adapter->delete($item) && $success;
        }

        return $success;
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

        $success = true;

        foreach ($items as $item) {
            $success = $this->adapter->delete($item) && $success;
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function flush($all = false)
    {
        return $this->adapter->flush();
    }

    /**
     * Retrieves the cache item for the given id.
     *
     * @param  string     $id        The cache id to retrieve.
     * @return mixed|null Returns the cached data or null.
     */
    public function get($id)
    {
        $data = $this->adapter->get($id);

        if (false !== $data) {
            $this->ttls[$id] = isset($data['ttl']) ? $data['ttl'] : 0;

            return isset($data['data']) ? $data['data'] : $data;
        }

        return null;
    }

    /**
     * Returns the ttl sanitased for this cache adapter.
     *
     * The number of seconds may not exceed 60*60*24*30 = 2,592,000 (30 days).
     * @see http://php.net/manual/en/Memcache.expiration.php
     *
     * @param  integer|null $ttl The time-to-live in seconds.
     * @return int
     */
    public function sanitiseTtl($ttl)
    {
        return $ttl > 2592000 ? time() + $ttl : $ttl;
    }

    /**
     * Returns the named indexer.
     *
     * @param  string          $name The name of the index.
     * @return Indexer\Adapter
     */
    public function getIndex($name)
    {
        return new Indexer\MemcacheIndexer($name, $this);
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
     *
     * @return int|bool Returns the new item's value on success or FALSE on failure.
     */
    public function increment($key)
    {
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
     */
    public function getTtl($key)
    {
        $mKey = $this->mapKey($key);

        if (!isset($this->ttls[$mKey])) {
            $data = $this->adapter->get($mKey);

            $this->ttls[$mKey] = (isset($data['ttl']) ? $data['ttl'] : 0);
        }

        return $this->ttls[$mKey];
    }

    /**
     * Gets the injected adapter.
     *
     * @return \Memcache
     */
    public function getAdapter()
    {
        return $this->adapter;
    }
}
