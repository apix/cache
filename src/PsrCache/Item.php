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

use Psr\Cache\CacheItemInterface as ItemInterface;
use Psr\Cache\CacheItemPoolInterface as ItemPoolInterface;

class Item implements ItemInterface
{
    const DEFAULT_EXPIRATION = 'now +1 year';

    /**
     * The pool that the item belongs to.
     * @var ItemPoolInterface
     */
    // protected $pool;

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
    protected $hit = false;

    /**
     * The expiration date.
     * @var \DateTime
     */
    protected $expiration;

    /**
     * Constructs a new Item.
     * You should never use this directly. It is used internally to create items
     * from the pool.
     * @param string             $key   The item key
     * @param mixed              $value The item value (unserialized)
     * @param integer|null $ttl
     * @param bool               $hit   Was this item retrived from cache?
     */
    public function __construct($key, $value = null, $ttl = null, $hit = false)
    {
        if (strpbrk($key, '{}()/\@:')) {
            throw new InvalidArgumentException(
                'Item key contains an invalide character.' . $key
            );
        }
        $this->key = $key;
        $this->value = $value;
        $this->hit = $hit;
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
        return $this->hit ? $this->value : null;
    }

    /**
     * {@inheritdoc}
     */
    public function set($value = null)
    {
        $this->value = $value;
        $this->hit = false; // TODO: check wether we should we do this?

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isHit()
    {
        return $this->hit;
    }

    /**
     * {@inheritdoc}
     */
    public function exists()
    {
        // TODO: review!
        return $this->hit;
    }

    /**
     * {@inheritdoc}
     */
    public function isRegenerating()
    {
        return false; // TODO
    }

    /**
     * {@inheritdoc}
     */
    public function setExpiration($ttl = null)
    {
        if ($ttl instanceof \DateTime) {
            $this->expiration = $ttl;
        } elseif (is_numeric($ttl)) {
            $this->expiration = new \DateTime('now +' . $ttl . ' seconds');
        } elseif (is_null($ttl)) {
             // stored permanently or for as long as the default value.
            $this->expiration = new \DateTime(self::DEFAULT_EXPIRATION);
        } else {
            throw new InvalidArgumentException(
                'Integer or \DateTime object expected.'
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

    /**
     * Returns the time to live in second.
     *
     * @return integer
     */
    public function getTtlInSecond()
    {
        return $this->expiration->format('U') - time();
    }

    /**
     * Sets the cache hit for this item.
     *
     * @param  boolean $hit
     * @return static
     *                     The invoked object.
     */
    public function setHit($hit)
    {
        $this->hit = (bool) $hit;

        return $this;
    }

}
