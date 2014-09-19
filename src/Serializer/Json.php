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
 * Serializes data using the native PHP Json extension.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class Json implements Adapter
{

    /**
     * {@inheritdoc}
     */
    public function serialize($data)
    {
        return json_encode($data);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str)
    {
        return json_decode($str);
    }

    /**
     * {@inheritdoc}
     */
    public function isSerialized($str)
    {
        if (!is_string($str)) {
            return false;
        }

        return (boolean) (json_decode($str) !== null);
    }

}
