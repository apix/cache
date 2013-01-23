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
    public function __construct(\Memcached $Memcached, array $options=null)
    {
        // default options
        $this->options['serializer'] = 'php'; // none, php, json, igBinary.

        $this->options['prefix_idx'] = 'idx_'; // prefix cache index
        $this->options['prefix_key'] = 'key_'; // prefix cache index
        $this->options['prefix_tag'] = 'tag_'; // prefix cache index

        $this->options['namespace_key'] = 'apix_'; // namespace_key

        parent::__construct($Memcached, $options);

        // TODO: Memcached::SERIALIZER_PHP or Memcached::SERIALIZER_IGBINARY
        $this->adapter->setOption(\Memcached::OPT_COMPRESSION, false);


        $this->setSerializer($this->options['serializer']);

        $this->setNamespace($this->options['namespace_key']);
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
        return $this->loadIndex($tag, 'tag');
    }

    /**
     * Retrieves the cache item for the given id.
     *
     * @param  string     $id The cache id to retrieve.
     * @return mixed|null Returns the cached data or null.
     */
    public function get($id)
    {
        $data = $this->adapter->get($id);

        return $this->adapter->getResultCode() == \Memcached::RES_NOTFOUND
                ? null
                : $data;
    }

    /**
     * {@inheritdoc}
     */
    public function save($data, $key, array $tags=null, $ttl=null)
    {
        $ttl = $this->sanitiseTtl($ttl);

        $mKey = $this->mapKey($key);
        $success = $this->adapter->set($mKey, $data, $ttl);

        if ($success && $this->options['tag_enable'] && !empty($tags)) {

            foreach ($tags as $tag) {
                $this->upsertIndex($this->mapTag($tag), $mKey);
            }
            $this->upsertIndex($this->mapIdx($key), $tags);
        }

        return $success;
    }

    /**
     * Returns the ttl sanitased for this cache adapter.
     *
     * @http://php.net/manual/en/memcached.expiration.php
     *
     * @param  integer|null $ttl The time-to-live in seconds.
     * @return int
     */
    protected function sanitiseTtl($ttl)
    {
        return $ttl > 2592000 ? time()+$ttl : $ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function clean(array $tags)
    {
        $items = array();
        foreach ($tags as $tag) {
            $keys = $this->loadTag($tag);
            // TODO: use $keys = $this->adapter->getMulti($tags);
            if (null !== $keys) {
                array_walk_recursive(
                    $keys,
                    function($key) use (&$items) { $items[] = $key; }
                );
            }
            $items[] = $this->mapTag($tag);
        }

        $this->adapter->deleteMulti($items);

        return (boolean) $this->adapter->getResultCode() != \Memcached::RES_NOTFOUND;
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

            // mark key for deletion in tags
            $tags = $this->loadIndex($idx);
            if(is_array($tags)) {
                foreach ($tags as $tag) {
                    $this->upsertIndex($this->mapTag($tag), $_key, '-');
                }
                $items[] = $idx;
            }
        }

        $this->adapter->deleteMulti($items);

        return (boolean) $this->adapter->getResultCode() != \Memcached::RES_NOTFOUND;
    }

    /**
     * {@inheritdoc}
     */
    public function flush($all=false)
    {
        if (true === $all) {
            return $this->adapter->flush();
        }

        $nsKey = $this->options['namespace_key'];
        $this->adapter->setOption(\Memcached::OPT_PREFIX_KEY, $nsKey . '_');

        // mark for the old namespace for later deletion
        $this->upsertIndex($this->mapIdx($nsKey), $this->getNamespace(), '-');

        // increment the namespace!
        $success = $this->adapter->increment($nsKey);

        $this->setNamespace($nsKey);

        return (boolean) $success;
    }

    public function setNamespace($nsKey)
    {
        $this->adapter->setOption(\Memcached::OPT_PREFIX_KEY, $nsKey . '_');

        $nsVal = $this->adapter->get($nsKey);
        if(false === $nsVal) {
            $nsVal = 1;
            $this->adapter->set($nsKey, $nsVal);
        }

        $this->adapter->setOption(\Memcached::OPT_PREFIX_KEY, $nsKey . $nsVal . '_');
    }

    public function getNamespace()
    {
        return $this->adapter->getOption(\Memcached::OPT_PREFIX_KEY);
    }

    /**
     * Upserts some tags to an index key.
     *
     * @param  string  $idx  The name of the index.
     * @param  array   $tags
     * @return Returns True on success or False on failure.
     */
    public function upsertIndex($idx, $context, $op='+')
    {
        $s = new Serializer\StringerSet;
        $str = $s->serialize($context, $op);

        // upsert
        if (! $success = $this->adapter->append($idx, $str) ) {
            if ($op == '+') {
                $success = $this->adapter->add($idx, $str);
            }
        }

        return (boolean) $success;
    }

    public function loadIndex($key, $type=null)
    {
        if(null !== $type) {
            $key = $this->mapType($key, $type);
        }

        $str = $this->get($key);

        if (null === $str) {
            return null;
        }

        $s = new Serializer\StringerSet;
        $tagged = $s->unserialize($str);

        return empty($tagged['keys']) ? null : $tagged['keys'];
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

    public function mapType($key, $type)
    {
        switch($type)
        {
            case 'tag':
                return $this->mapTag($key);

            case 'idx':
                return $this->mapIdx($key);
        }

        return $this->mapTag($key);
    }

    /**
     * Purges expired items.
     *
     * @param  integer|null $add Extra time in second to add.
     * @return boolean      Returns True on success or False on failure.
     */
    public function purge($add=null)
    {
        // def items(mc, indexName, forceCompaction=False):
        // """Retrieve the current values from the set.

        // This may trigger a compaction if you ask it to or the encoding is
        // too dirty."""

        // flags, casid, data = mc.get(indexName)
        // dirtiness, keys = decodeSet(data)

        // if forceCompaction or dirtiness > DIRTINESS_THRESHOLD:
        //     compacted = encodeSet(keys)
        //     mc.cas(indexName, casid, compacted)
        // return keys
    }

}
