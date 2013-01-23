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
 * Serializes cache data using as StringerSet.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class StringerSet //implements Adapter
{

    /**
     * {@inheritdoc}
     */
    public function serialize($keys, $op='+')
    {
        $str = '';
        foreach ((array) $keys as $key) {
            $str .= $op . $key . ' ';
        }

        return $str;
    }

    /**
     * {@inheritdoc}
     */
    // """Decode an item from the cache into a set impl.
    // Returns a dirtiness indicator (compaction hint) and the set
    // >>> decodeSet('+a +b +c -b -x')
    // (2, set(['a', 'c']))
    // """
    public function unserialize($str)
    {
        $add    = array();
        $remove = array();
        foreach (explode(' ', trim($str)) as $k) {
            $key = substr($k, 1);
            $op = $k[0];
            if($op == '+')
                $add[] = $key;
            elseif($op == '-')
                $remove[] = $key;
        }

        return array(
            'dirty' => count($remove),
            'keys'  => array_values(array_diff($add, $remove)),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isSerialized($str)
    {
        if (!is_string($str)) {
            return false;
        }

        return false;
        // return false; preg_match('/^\+ [.*] $/');
    }

}
