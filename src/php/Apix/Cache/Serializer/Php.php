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
 * Serializes data using the native PHP serializer.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class Php implements Adapter
{

    /**
     * {@inheritdoc}
     */
    public function serialize($data)
    {
        return serialize($data);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str)
    {
        return unserialize($str);
    }

    /**
     * {@inheritdoc}
     */
    public function isSerialized($str)
    {
        if (!is_string($str)) {
            return false;
        }

        return (boolean) ($str=='b:0;' || @unserialize($str) !== false);
    }

}
