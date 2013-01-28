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
 *
 * @TODO: namespacing?!
 * @see http://code.google.com/p/memcached/wiki/NewProgrammingTricks
 * @TODO: tag set?!
 * @see http://dustin.github.com/2011/02/17/memcached-set.html
 *
 */
class MemcachedIndexer extends AbstractIndexer
{

    const DIRTINESS_THRESHOLD = 100;

    /**
     * Holds the name of the index.
     * @var array
     */
    protected $index;

    /**
     * Holds the index items.
     * @var array
     */
    protected $items = null;

    /**
     * Holds the .
     * @var Serializer
     */
    protected $serializer;

    /**
     * Constructor.
     *
     * @param array                $options   Array of options.
     * @param Apix\Cache\Memcached $Memcached A Memcached instance.
     */
    public function __construct(Memcached $engine, $index)
    {
        $this->engine = $engine;
        $this->index = $index;

        $this->serializer = new Serializer\Stringset;
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

        $success = $this->getAdapter()->append($this->index, $str);

        if (false === $success) {
            $success = $this->getAdapter()->add($this->index, $str);
        }

        return (boolean) $success;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($elements)
    {
        $str = $this->serializer->serialize((array) $elements, '-');

        return (boolean) $this->getAdapter()->append($this->index, $str);
    }

    /**
     * Returns the indexed items.
     *
     * @param  array   $context The elements to remove from the index.
     * @return Returns True on success or Null on failure.
     */
    public function load()
    {
        $str = $this->engine->get($this->index, $cas_token);

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
     * Purge the index.
     *
     * @return [type] [description]
     */
    protected function purge($cas_token)
    {
        $str = $this->serializer->serialize($this->items);

        return $this->getAdapter()->cas($cas_token, $this->index, $str);
    }

}
