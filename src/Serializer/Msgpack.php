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
 * MessagePack - a light cross-language binary serializer.
 * Serializes data using the Msgpack extension.
 * @see https://github.com/msgpack/msgpack-php
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class Msgpack implements Adapter
{

    /**
     * {@inheritdoc}
     */
    public function serialize($data)
    {
        return \msgpack_pack($data);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str)
    {
        return \msgpack_unpack($str);
    }

    /**
     * {@inheritdoc}
     */
    public function isSerialized($str)
    {
        if (!is_string($str)) {
            return false;
        }

        return (boolean) !is_integer( @\msgpack_unpack($str) );
    }

}
