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

use Psr/Cache/PoolInterface,
    Psr/Cache/ItemInterface;

/**
 * @brief Represents an item that may or may not exist in the cache.
 */
class Item extends ItemInterface
{

    /**
     * The pool that the item belongs to.
     * @var PoolInterface
     */
    protected $pool;

    /**
     * The cache key for the item.
     * @var string
     */
    protected $key;

    /**
     * The raw (unserialized) cached value.
     * @var mixed
     */
    protected $value;

    /**
     * Wether the item has been saved to the cache yet.
     * @var bool
     */
    protected $isHit = false;

    /**
     * The expiration date.
     * @var \DateTime
     */
    protected $expiration;

    /**
     * The time to live in second.
     * @var integer
     */
    protected $ttl;

    /**
     * The tags associated with this entry.
     * @var array
     */
    protected $tags = array();


    /**
     * @brief Construct a new Item.
     * You should never use this directly. It is used internally to create items
     * from the pool.
     * @param PoolInterface $pool The pool that created this item.
     * @param string $key The key.
     * @param mixed $value The unserialized value, retaining the original type.
     * @param bool $isHit Was this item retrived from cache?
     */
    public function __construct(PoolInterface $pool, $key, $value, $isHit, $ttl)
    {
        $this->pool = $pool;
        $this->cache = $pool->getCacheAdapter();

        $this->key = $key;

        $this->value = $value;
        $this->isHit = $isHit;
        $this->setExpiration($ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        if ( !$this->isHit() ) {
            $value = $this->cache->loadKey($this->key);

            $this->value = $value;
            $this->isHit = true;
            $this->setExpiration($ttl); // ??!
        }

        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function set($value = null, $ttl = null)
    {
        $this->value = $value;
        $this->isHit = $isHit;
        $this->setExpiration($ttl);

        return $this->cache->save($this->key, $this->value, $this->tags, $this->ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function isHit()
    {
        return $this->isHit;
    }

    /**
     * Removes the current key from the cache.
     *
     * @return ItemInterface
     *   The current item.
     */
    public function delete()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function exists()
    {
        return $this->isHit();
    }

    /**
     * {@inheritdoc}
     */
    public function isRegenerating()
    {
        /**
         * This method is used to tell future calls to this item if re-regeneration of
         * this item's data is in progress or not.
         *
         * This can be used to prevent the dogpile effect to stop lots of requests re-generating
         * the fresh data over and over.
         *
         * @return boolean
         */
    }

    /**
     * {@inheritdoc}
     * @param integer|\DateTime $ttl
     */
    public function setExpiration($ttl = null)
    {
        if ($ttl instanceof \DateTime) {
            $this->expiration = $ttl;
        } elseif (is_numeric($ttl)) {
            $this->expiration = new \DateTime('now +' . $ttl . ' seconds');
        } elseif (is_null($ttl)) {

             // *  TODO - If null is passed, a default value MAY be used. If none is set,
             // *     the value should be stored permanently or for as long as the
             // *     implementation allows.

            $this->expiration = new \DateTime('now +1 year');
        } else {
            throw new InvalidArgumentException(
                'Integer or a \DateTime object expected.'
            );
        }
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

}
