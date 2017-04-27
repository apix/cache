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

class Pool implements ItemPoolInterface
{

    /**
     *
     * @var CacheAdapter
     */
    protected $cache_adapter;

    /**
     * Deferred cache items to be saved later.
     *
     * @var array   Collection of \Apix\PsrCache\Item.
     */
    protected $deferred = array();

    /**
     * Constructor.
     */
    public function __construct(CacheAdapter $cache_adapter)
    {
        $this->cache_adapter = $cache_adapter;

        $options = array(
            'tag_enable' => false // wether to enable tagging
        );
        $this->cache_adapter->setOptions($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key)
    {
        $key = Item::normalizedKey($key);

        if (isset($this->deferred[$key])) {
            return $this->deferred[$key];
        }

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
    public function hasItem($key)
    {
        return $this->getItem($key)->isHit();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->deferred = array();
        return $this->cache_adapter->flush(true);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys)
    {
        $checks = array();
        foreach ($keys as $key) {
            // Only delete from cache if it actually exists
            if($this->getItem($key)->isHit()) {
                $checks[] = $this->cache_adapter->delete($key);
            }
            unset($this->deferred[$key]);
        }
        return (bool) !in_array(false, $checks, true);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem($key)
    {
        return $this->deleteItems(array($key));
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

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(ItemInterface $item)
    {
        $this->deferred[$item->getKey()] = $item;

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

    /**
     * Commit the deferred items ~ acts as the last resort garbage collector.
     */
    public function __destruct()
    {
        $this->commit();
    }

}
