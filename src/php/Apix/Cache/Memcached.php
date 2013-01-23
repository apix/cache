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
     * @param \Memcached $Memcached   A Memcached instance.
     * @param array  $options Array of options.
     */
    public function __construct(\Memcached $Memcached, array $options=null)
    {
        // default options
        $this->options['db_name'] = 'apix';
        $this->options['collection_name'] = 'cache';
        $this->options['serializer'] = 'php'; // none, php, json, igBinary.
        $this->options['prefix_idx'] = 'idx_'; // prefix cache index
        $this->options['prefix_key'] = 'key_'; // prefix cache index
        $this->options['prefix_tag'] = 'tag_'; // prefix cache index

        // TODO: Memcached::SERIALIZER_PHP or Memcached::SERIALIZER_IGBINARY
        $Memcached->setOption(\Memcached::OPT_COMPRESSION, false);
        parent::__construct($Memcached, $options);

        $this->setSerializer($this->options['serializer']);
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
        $str = $this->get($this->mapTag($tag));

        if(null !== $str) {
            $s = new Serializer\StringerSet;
            $tagged = $s->unserialize($str);
            return $tagged['keys'];
        }

        return null;
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
     * Upserts a string to an id.
     *
     * @param string $id The id od the upsert.
     * @param array $str
     * @return Returns True on success or False on failure.
     */
    public function append($id, $str, $op='+')
    {
        // upsert
        if(! $success = $this->adapter->append($id, $str) ) {
            if($op == '+') {
                $success = $this->adapter->add($id, $str);
            }
        }

        return (boolean) $success;
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
        $key = $this->mapKey($key);

        if ($this->options['tag_enable']) {
            $tags = $this->get($this->mapIdx($key));

            // mark for deletion
            $this->upsertIndex($key, $tags, '-');
        }
        $this->adapter->delete($this->mapIdx($key));

        $this->adapter->delete($key);

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

        // namespace?
        // $items = array_merge(
        //     $this->adapter->keys($this->mapTag('*')),
        //     $this->adapter->keys($this->mapKey('*'))
        // );

        // return $this->adapter->deleteMulti($items);
    }

    /**
     * Upserts some tags to an index key.
     *
     * @param string $idx The name of the index.
     * @param array $tags
     * @return Returns True on success or False on failure.
     */
    public function upsertIndex($idx, $context, $op='+')
    {
        $s = new Serializer\StringerSet;

        return $this->append($idx, $s->serialize($context), $op);
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

}
