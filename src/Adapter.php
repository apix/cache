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

/**
 * The interface/adapter that the cache wrappers must implement.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
interface Adapter
{
    /**
     * Retrieves the cache content for the given key.
     *
     * @param  string     $key The cache key to retrieve.
     * @return mixed|null Returns the cached data or null.
     */
    public function loadKey($key);

    /**
     * Retrieves the cache keys for the given tag.
     *
     * @param  string     $tag The cache tag to retrieve.
     * @return array|null Returns an array of cache keys or null.
     */
    public function loadTag($tag);

    /**
     * Saves data to the cache.
     *
     * @param  mixed   $data The data to cache.
     * @param  string  $key  The cache id to save.
     * @param  array   $tags The cache tags for this cache entry.
     * @param  int     $ttl  The time-to-live in seconds, if set to null the
     *                       cache is valid forever.
     * @return boolean Returns True on success or False on failure.
     */
    public function save($data, $key, array $tags=null, $ttl=null);

    /**
     * Deletes the specified cache record.
     *
     * @param  string  $key The cache id to remove.
     * @return boolean Returns True on success or False on failure.
     */
    public function delete($key);

    /**
     * Removes all the cached entries associated with the given tag names.
     *
     * @param  array   $tags The array of tags to remove.
     * @return boolean Returns True on success or False on failure.
     */
    public function clean(array $tags);

    /**
     * Flush all the cached entries.
     *
     * @param  boolean $all Wether to flush the whole database, or (preferably)
     *                      the entries prefixed with prefix_key and prefix_tag.
     * @return boolean Returns True on success or False on failure.
     */
    public function flush($all=false);

    /**
     * Returns the time-to-live (in seconds) for the given key.
     *
     * @param  string    $key The name of the key.
     * @return int|false Returns the number of seconds left, 0 if valid
     *                       forever or False if the key is non-existant.
     */
    public function getTtl($key);

}
