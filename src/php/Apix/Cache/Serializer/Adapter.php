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

namespace Apix\Cache\Serializer;

/**
 * The interface adapter for the cache serializer.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
interface Adapter
{

    /**
     * Serialises mixed data as a string.
     *
     * @param  mixed        $data
     * @return string|mixed
     */
    public function serialize($data);

    /**
     * Unserialises a string representation as mixed data.
     *
     * @param  string       $data
     * @return mixed|string
     */
    public function unserialize($str);

    /**
     * Checks if the input is serialized a string representation as mixed data.
     *
     * @param  string  $data
     * @return boolean
     */
    public function isSerialized($str);

}
