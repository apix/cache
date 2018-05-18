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
 * APCu Cache(User-Cache) wrapper with emulated tag support.
 *
 * @package Apix\Cache
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class Apcu extends Apc
{

    /**
     * Retrieves the cache item for the given id.
     *
     * @param  string     $id      The cache id to retrieve.
     * @param  boolean    $success The variable to store the success value.
     * @return mixed|null Returns the cached data or null.
     */
    public function get($id, $success = null)
    {
        $cached = apcu_fetch($id, $success);

        return false === $success ? null : $cached;
    }

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
                $keys = apcu_fetch($tag, $success);
                if (false === $success) {
                    $store[$tag] = array($key);
                } else {
                    $keys[] = $key;
                    $store[$tag] = array_unique($keys);
                }
            }
        }

        return !in_array(false, apcu_store($store, null, $ttl));
    }

    /**
     * {@inheritdoc}
     *
     * APC does not support natively cache-tags so we simulate them.
     */
    public function delete($key)
    {
        $key = $this->mapKey($key);

        if (($success = apcu_delete($key)) && $this->options['tag_enable']) {
            $iterator = $this->getIterator(
                '/^' . preg_quote($this->options['prefix_tag']) . '/',
                \APC_ITER_VALUE
            );
            foreach ($iterator as $tag => $keys) {
                if (false !== ($k = array_search($key, $keys['value']))) {
                    unset($keys['value'][$k]);
                    if (empty($keys['value'])) {
                        apcu_delete($tag);
                    } else {
                        apcu_store($tag, $keys['value']);
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
            $keys = apcu_fetch($tag, $success);
            if ($success) {
                foreach ($keys as $key) {
                    $rmed[] = apcu_delete($key);
                }
                $rmed[] = apcu_delete($tag);
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
            return apcu_clear_cache();
        }

        $iterator = $this->getIterator(
            '/^' . preg_quote($this->options['prefix_key'])
            .'|' . preg_quote($this->options['prefix_tag']) . '/',
            \APC_ITER_KEY
        );

        $rmed = array();
        foreach ($iterator as $key => $data) {
            $rmed[] = apcu_delete($key);
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
        return class_exists('\APCUIterator')
            ? new \APCUIterator($search, $format, 100, \APC_LIST_ACTIVE)
            : new \APCIterator('user', $search, $format, 100, \APC_LIST_ACTIVE);
    }
}
