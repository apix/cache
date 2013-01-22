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

        $this->separator = '^|^';

        // TODO: Memcached::SERIALIZER_PHP or Memcached::SERIALIZER_IGBINARY
        $Memcached->setOption(\Memcached::OPT_COMPRESSION, false);
        parent::__construct($Memcached, $options);

        $this->setSerializer($this->options['serializer']);
    }

    /**
     * {@inheritdoc}
     */
    public function load($key, $type='key')
    {
        return $type == 'key' ? $this->loadKey($key) : $this->loadTag($key);
    }

    /**
     * Retrieves the cache for the given key.
     *
     * @param  string     $key The cache key to retrieve.
     * @return mixed|null Returns the cached data or null.
     */
    public function loadKey($key)
    {
        return $this->get($this->mapKey($key));
    }

    /**
     * Retrieves the cache keys for the given tag.
     *
     * @param  string     $tag The cache tag to retrieve.
     * @return array|null Returns an array of cache keys or null.
     */
    public function loadTag($tag)
    {
        return $this->get($this->mapTag($tag));
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
        $code = $this->adapter->getResultCode();

        return  $code == \Memcached::RES_NOTFOUND ? null : $data;
    }

    /**
     * {@inheritdoc}
     */
    public function save($data, $key, array $tags=null, $ttl=null)
    {
        $key = $this->mapKey($key);
        $success = $this->adapter->set($key, $data, $ttl);

        if ($success && $this->options['tag_enable'] && !empty($tags)) {
            foreach ($tags as $tag) {
                $this->upsert($this->mapTag($tag), $key);
            }
        }

        return $success;
    }

    /**
     * Upserts a string to an id.
     *
     * @return Returns True on success or False on failure.
     */
    public function upsert($id, $str)
    {
        // keys can appear more than once in a group list - no atomic way to do write
        // heavy sets in memcached, though see this: http://dustin.github.com/2011/02/17/memcached-set.html
        // for an interesting discussion

        $str = $this->separator . $str;
        return $this->adapter->add($id, $str)
                ? true
                : $this->adapter->append($id, $str);
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
            if (false !== $keys) {
                $keys = array_unique(explode($this->separator, $keys));
                array_walk_recursive(
                    $keys,
                    function($key) use (&$items) { $items[] = $key; }
                );
            }
            $items[] = $this->mapTag($tag);
        }

        return $this->adapter->deleteMulti($items);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $key = $this->mapKey($key);

        if ($this->options['tag_enable']) {
            // TODO: remove all $key contain in the tags!
        }

        return $this->adapter->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function flush($all=false)
    {
        // if (true === $all) {
            return $this->adapter->flush();
        // }
        // $items = array_merge(
        //     $this->adapter->keys($this->mapTag('*')),
        //     $this->adapter->keys($this->mapKey('*'))
        // );

        // return $this->adapter->deleteMulti($items);
    }

}
