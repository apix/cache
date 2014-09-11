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

namespace Apix\Cache\Psr;

use Apix\Cache\Adapter,
    Psr\Cache\PoolInterface,
    Psr\Cache\ItemInterface;

class Pool implements PoolInterface
{

    /**
     *
     * @var Adapter
     */
    protected $cache;

    /**
     * Deferred cache items to be saved later.
     *
     * @var array   Collection of \Apix\Psr\Item.
     */
    protected $deferred = array();

    /**
     * Constructor.
     */
    // public function __construct(AbstractCache $cache, array $options=null)
    // {
    //     $this->cache = $cache;
    // }

    public function getCache()
    {
        return $this->cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key)
    {
        $item = $this->cache->get($key);

        return new Item($this, $key, $item, $isHit, $ttl); // TODO here!!
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = array())
    {
        $items = array();
        foreach($keys as $key) {
            $items[$key] = $this->getItem($key);
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->cache->flush(true);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys);
    {
        $items = array();
        foreach($keys as $key) {
            $this->cache->delete($key);
        }   

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function save(ItemInterface $item)
    {
        // TODO here!!!
        // $this->write([$item]);

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
        $success = $this->write($this->deferred);
        if ($success) {
            $this->deferred = array();
        }
        
        return $success;
    }

    /* Add tags!? */
}
