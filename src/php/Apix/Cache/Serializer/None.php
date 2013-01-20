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
 * Blank/null/none serializer.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class None implements Adapter
{

    /**
     * {@inheritdoc}
     */
    public function serialize($data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str)
    {
        return $str;
    }

    /**
     * {@inheritdoc}
     */
    public function isSerialized($str)
    {
        return false;
    }

}
