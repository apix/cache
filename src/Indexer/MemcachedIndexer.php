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

namespace Apix\Cache\Indexer;

use Apix\Cache\Memcached,
    Apix\Cache\Serializer;

/**
 * Memcached index.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class MemcachedIndexer extends AbstractIndexer
{

    const DIRTINESS_THRESHOLD = 100;

    /**
     * Holds an instane of a serializer.
     * @var Serializer
     */
    protected $serializer;

    /**
     * Constructor.
     *
     * @param array                $options   Array of options.
     * @param Apix\Cache\Memcached $Memcached A Memcached instance.
     */
    public function __construct($name, Memcached $engine)
    {
        $this->name = $name;
        $this->engine = $engine;

        $this->serializer = new Serializer\Stringset();
    }

    /**
     * Gets the adapter.
     *
     * @return \Memcached
     */
    public function getAdapter()
    {
        return $this->engine->getAdapter();
    }

    /**
     * {@inheritdoc}
     */
    public function add($elements)
    {
        $str = $this->serializer->serialize((array) $elements);

        $success = $this->getAdapter()->append($this->name, $str);

        if (false === $success) {
            $success = $this->getAdapter()->add($this->name, $str);
        }

        return (boolean) $success;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($elements)
    {
        $str = $this->serializer->serialize((array) $elements, '-');

        return (boolean) $this->getAdapter()->append($this->name, $str);
    }

    /**
     * Returns the indexed items.
     *
     * @param  array   $context The elements to remove from the index.
     * @return boolean Returns True on success or Null on failure.
     */
    public function load()
    {
        // &$cas_token holds the unique 64-bit float token generated
        // by memcache for the named item @see \Memcached::get
        $str = $this->engine->get($this->name, $cas_token);

        if (null === $str) {
            return null;
        }
        $this->items = $this->serializer->unserialize($str);

        if ($this->serializer->getDirtiness() > self::DIRTINESS_THRESHOLD) {
            $this->purge($cas_token);
        }

        return $this->items;
    }

    /**
     * Purge atomically the index.
     *
     * @return float $cas_token The Memcache CAS token.
     */
    protected function purge($cas_token)
    {
        $str = $this->serializer->serialize($this->items);

        return $this->getAdapter()->cas($cas_token, $this->name, $str);
    }

}
