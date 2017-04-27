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

class TaggablePool extends Pool
{

    /**
     * The tags associated with this pool (just an optimisation hack).
     * @var array|null
     */
    private $_tags = null;

    /**
     * Constructor.
     */
    public function __construct(CacheAdapter $cache_adapter)
    {
        $this->cache_adapter = $cache_adapter;

        $options = array(
            'tag_enable' => true // wether to enable tagging
        );
        $this->cache_adapter->setOptions($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key)
    {
        $value = $this->cache_adapter->loadKey($key);

        $item = new TaggableItem(
            $this->cache_adapter->removePrefixKey($key),
            $value,
            $this->cache_adapter->getTtl($key) ?: null,
            (bool) $value // indicates wether it is loaded from cache or not.
        );

        // Set this pool tags rather than the actual cached item tags.
        $item->setTags($this->_tags);

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function save(ItemInterface $item)
    {
        $ttl = $item->getTtlInSecond();
        $this->_tags = $item->getTags();

        $item->setHit(true);
        $success = $this->cache_adapter->save(
                        $item->get(),             // value to store
                        $item->getKey(),          // its key
                        $this->_tags,              // this pool tags
                        is_null($ttl) ? 0 : $ttl  // ttl in sec or null for ever
                    );
        $item->setHit($success);

        return $success;
    }

    /**
     * Retrieves the cache keys for the given tag.
     *
     * @param  string $tag The cache tag to retrieve.
     * @return array  Returns an array of cache keys.
     */
    public function getItemsByTag($tag)
    {
        $keys = $this->cache_adapter->loadTag($tag);
        $items = array();
        if ($keys) {
            foreach ($keys as $key) {
                $k = $this->cache_adapter->removePrefixKey($key);
                $items[$k] = $this->getItem($k);
            }
        }

        return $items;
    }

    /**
     * Removes all the cached entries associated with the given tag names.
     *
     * @param  array $tags The array of tags to remove.
     * @return bool
     */
    public function clearByTags(array $tags)
    {
        return $this->cache_adapter->clean($tags);
    }

}
