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
     * Returns the index name.
     *
     * @return Returns True on success or False on failure.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the items index.
     *
     * @return Returns True on success or False on failure.
     */
    public function getItems()
    {
        return $this->items;
    }
}
