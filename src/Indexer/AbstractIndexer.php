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

/**
 * Base class provides the cache wrappers structure.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
abstract class AbstractIndexer implements Adapter
{
    /**
     * Holds the name of the index.
     * @var string
     */
    protected $name;

    /**
     * Holds the index items.
     * @var array
     */
    protected $items = array();

    /**
     * Returns the index name.
     *
     * @return string a string.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the items index.
     *
     * @return array Returns an array of items or null failure.
     */
    public function getItems()
    {
        return $this->items;
    }
}
