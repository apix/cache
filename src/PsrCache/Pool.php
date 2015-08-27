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

namespace Apix\Cache\PsrCache;

use Apix\Cache\Adapter as CacheAdapter;
use Psr\Cache\CacheItemInterface as ItemInterface;
use Psr\Cache\CacheItemPoolInterface as ItemPoolInterface;

/**
 * Class Pool
 *
 * @package Apix\Cache\PsrCache
 */
class Pool implements ItemPoolInterface
{

    /**
     *
     * @var \Apix\Cache\Adapter
     */
    protected $cache_adapter;

    /**
     * Deferred cache items to be saved later.
     *
     * @var \Psr\Cache\CacheItemInterface[]   Collection of \Apix\PsrCache\Item.
     */
    protected $deferred = array();

    /**
     * Constructor.
     *
     * @param \Apix\Cache\Adapter $cache_adapter
     */
    public function __construct(CacheAdapter $cache_adapter)
    {
        $this->cache_adapter = $cache_adapter;

        $options = array(
            'prefix_key' => 'cask-key-',  // prefix cache keys
            'tag_enable' => false         // wether to enable tagging
        );
        $this->cache_adapter->setOptions($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key)
    {
        $value = $this->cache_adapter->loadKey($key);

        return new Item(
            $this->cache_adapter->removePrefixKey($key),
            $value,
            $this->cache_adapter->getTtl($key) ?: null,
            (bool) $value // indicates wether it is loaded from cache or not.
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = array())
    {
        $items = array();
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->cache_adapter->flush(true);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys)
    {
        foreach ($keys as $key) {
            $this->cache_adapter->delete($key);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function save(ItemInterface $item)
    {
        $ttl = $item->getTtlInSecond();

        $item->setHit(true);
        $success = $this->cache_adapter->save(
                        $item->get(),             // value to store
                        $item->getKey(),          // its key
                        null,                     // disable tags support
                        is_null($ttl) ? 0 : $ttl  // ttl in sec or null for ever
                    );
        $item->setHit($success);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(ItemInterface $item)
    {
        $this->deferred[] = $item;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        foreach ($this->deferred as $key => $item) {
            $this->save($item);
            if ( $item->isHit() ) {
                unset($this->deferred[$key]);
            }
        }

        return empty($this->deferred);
    }

    /**
     * Returns the cache adapter for this pool.
     *
     * @return CacheAdapter
     */
    public function getCacheAdapter()
    {
        return $this->cache_adapter;
    }

}
