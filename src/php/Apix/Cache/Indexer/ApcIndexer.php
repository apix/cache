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

use Apix\Cache\Apc;

/**
 * Apc indexer.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 *
 * @TODO: namespacing?!
 * @see http://code.google.com/p/memcached/wiki/NewProgrammingTricks
 * @TODO: tag set?!
 * @see http://dustin.github.com/2011/02/17/memcached-set.html
 *
 */
class ApcIndexer extends AbstractIndexer
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
     * Constructor.
     *
     * @param array          $options   Array of options.
     * @param Apix\Cache\Apc $Memcached An instance of .
     */
    public function __construct(Apc $engine, $index)
    {
        $this->engine = $engine;
        $this->index = $index;
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
        foreach ((array) $elements as $element) {
            $tag = $this->engine->mapTag($element);
            $keys = apc_fetch($tag, $success);
            if (false === $success) {
                $this->items[$tag] = array($this->index);
            } else {
                $keys[] = $this->index;
                $this->items[$tag] = array_unique($keys);
            }
        }

        return !in_array(false, apc_store($this->items, null, $ttl));
    }

    /**
     * {@inheritdoc}
     */
    public function remove($elements)
    {
        $str = $this->serialize((array) $elements, '-');

        return (boolean) $this->getAdapter()->append($this->index, $str);
    }

    /**
     * Loads the indexed items from the backend.
     *
     * @param  array   $context The elements to remove from the index.
     * @return boolean Returns True on success or False on failure.
     */
    public function load()
    {
        return $this->engine->get($this->index);
    }

}
