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
 * APC Cache and APCu (User-Cache) wrapper with emulated tag support.
 *
 * @package Apix\Cache
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class Apc extends AbstractCache
{

    /**
     * Constructor.
     */
    public function __construct(array $options = array())
    {
        parent::__construct(null, $options);
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
        return $this->get($this->mapTag($tag));
        // TODO: return $this->getIndex($this->mapTag($tag))->load();
    }

    /**
     * Retrieves the cache item for the given id.
     *
     * @param  string     $id      The cache id to retrieve.
     * @param  boolean    $success The variable to store the success value.
     * @return mixed|null Returns the cached data or null.
     */
    public function get($id, $success = null)
    {
        $cached = apc_fetch($id, $success);

        return false === $success ? null : $cached;
    }

    /**
     * Returns the named indexer.
     *
     * @param  string          $name The name of the index.
     * @return Indexer\Adapter
     */
    // public function getIndex($name)
    // {
    //     return new Indexer\ApcIndexer($name, $this);
    // }

    /**
     * {@inheritdoc}
     *
     * APC does not support natively cache-tags so we simulate them.
     */
    public function save($data, $key, array $tags = null, $ttl = null)
    {
        $key = $this->mapKey($key);
        $store = array($key => $data);

        if ($this->options['tag_enable'] && !empty($tags)) {

            // add all the tags to the index key.
            // TODO: $this->getIndex($key)->add($tags);

            foreach ($tags as $tag) {
                $tag = $this->mapTag($tag);
                $keys = apc_fetch($tag, $success);
                if (false === $success) {
                    $store[$tag] = array($key);
                } else {
                    $keys[] = $key;
                    $store[$tag] = array_unique($keys);
                }
            }
        }

        return !in_array(false, apc_store($store, null, $ttl));
    }

    /**
     * {@inheritdoc}
     *
     * APC does not support natively cache-tags so we simulate them.
     */
    public function delete($key)
    {
        $key = $this->mapKey($key);

        if ( ($success = apc_delete($key)) && $this->options['tag_enable']) {

            $iterator = $this->getIterator(
                '/^' . preg_quote($this->options['prefix_tag']) . '/',
                \APC_ITER_VALUE
            );
            foreach ($iterator as $tag => $keys) {
                if ( false !== ($k = array_search($key, $keys['value'])) ) {
                    unset($keys['value'][$k]);
                    if (empty($keys['value'])) {
                        apc_delete($tag);
                    } else {
                        apc_store($tag, $keys['value']);
                    }
                }
                continue;
            }
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     *
     * APC does not support natively cache-tags so we simulate them.
     */
    public function clean(array $tags)
    {
        $rmed = array();
        foreach ($tags as $tag) {
            $tag = $this->mapTag($tag);
            $keys = apc_fetch($tag, $success);
            if ($success) {
                foreach ($keys as $key) {
                    $rmed[] = apc_delete($key);
                }
                $rmed[] = apc_delete($tag);
            } else {
                $rmed[] = false;
            }
        }

        return !in_array(false, $rmed);
    }

    /**
     * {@inheritdoc}
     *
     * APC does not support natively cache-tags so we simulate them.
     */
    public function flush($all = false)
    {
        if (true === $all) {
            return apc_clear_cache('user');
        }

        $iterator = $this->getIterator(
            '/^' . preg_quote($this->options['prefix_key'])
            .'|' . preg_quote($this->options['prefix_tag']) . '/',
            \APC_ITER_KEY
        );

        $rmed = array();
        foreach ($iterator as $key => $data) {
            $rmed[] = apc_delete($key);
        }

        return empty($rmed) || in_array(false, $rmed) ? false : true;
    }

    /**
     * Returns an APC iterator.
     *
     * @param string $search
     * @param integer $format
     * @return \APCIterator
     */
    protected function getIterator($search = null, $format = \APC_ITER_ALL)
    {
        return new \APCIterator('user', $search, $format, 100, \APC_LIST_ACTIVE);
    }

    /**
     * Returns some internal informations about a APC cached item.
     *
     * @param string $key
     * @return array|false
     */
    public function getInternalInfos($key, $format = \APC_ITER_KEY|\APC_ITER_VALUE)
    {
        $iterator = $this->getIterator(
            '/^' . preg_quote($this->options['prefix_key']) . '/',
            $format
        );

        $key = $this->mapKey($key);
        foreach ($iterator as $k => $v) {
            if ($k != $key)
                continue;

            return $v;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getTtl($key)
    {
        $info = $this->getInternalInfos($key, \APC_ITER_TTL);
        if ( $info && isset($info['ttl']) ) {
            return $info['ttl'];
            // return $info['ttl'] > 0 ? $info['creation_time']+$info['ttl'] : 0;
        }

        return false;
    }

}
