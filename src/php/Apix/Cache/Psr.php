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

use Apix\Psr\Pool;

/**
 * Psr Cache.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class Psr
{

    /**
     * @var AbstractCache
     */
    protected $cache_pool;

    /**
     * Constructor.
     */
    public function __construct(AbstractCache $cache, array $options=null)
    {
        $this->cache = $cache;
    }

    public function setCachePool(Pool $pool)
    {
        $this->cache_pool = $pool;
    }

    /**
     * Get the cache pool
     *
     * @return Pool
     */
    public function getCachePool()
    {
        return $this->cache_pool;
    }

    /**
     * Shorthand to get an item from the cache pool
     *
     * @param $key
     * @return ItemInterface
     */
    public function getCacheItem($key)
    {
        return $this->cache_pool->getItem($key);
    }

// 

    /**
     * Set the cache adapter.
     * @param AbstractCache $cache
     */
    public function setCache(AbstractCache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get the adapter for this pool.
     * @return AbstractCache
     */
    public function getCache()
    {
        return $this->cache;
    }

}
