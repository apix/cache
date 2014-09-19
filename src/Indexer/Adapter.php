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
 * The interface/adapter that the cache indexers must implement.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
interface Adapter
{

    /**
     * Adds one or many element(s) to the index.
     *
     * @param  array|string $elements The element(s) to remove from the index.
     * @return Returns      TRUE on success or FALSE on failure.
     */
    public function add($elements);

    /**
     * Removes one or many element(s) from the index.
     *
     * @param  array|string $elements The element(s) to remove from the index.
     * @return Returns      True on success or False on failure.
     */
    public function remove($elements);

    /**
     * Returns the indexed items.
     *
     * @return Returns True on success or False on failure.
     */
    public function load();

}
