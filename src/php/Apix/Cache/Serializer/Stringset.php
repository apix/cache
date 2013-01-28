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
 * Serializes cache data using as Stringset.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class Stringset implements Adapter
{

    /**
     * Holds this string dirtiness.
     * @var integer
     */
    protected $dirtiness;

    /**
     * {@inheritdoc}
     *
     * e.g. ['a','c'] => '+a +b'
     */
    public function serialize($keys, $op='')
    {
        $str = '';
        foreach ((array) $keys as $key) {
            $str .= "${op}${key} ";
        }

        return $str != '' ? $str : null;
    }

    /**
     * {@inheritdoc}
     *
     * e.g. '+a +b +c -b -x' => ['a','c'];
     * Sets the dirtiness level (counts the negative entries).
     */
    public function unserialize($str)
    {
        $add    = array();
        $remove = array();
        foreach (explode(' ', trim($str)) as $key) {
            if (isset($key[0])) {
                if ($key[0] == '-') {
                    $remove[] = substr($key, 1);
                } else {
                    $add[] = $key;
                }                
            }
        }

        $this->dirtiness = count($remove);

        $items = array_values(array_diff($add, $remove));

        return empty($items) ? null : $items;
    }

    /**
     * {@inheritdoc}
     */
    public function isSerialized($str)
    {
        if (!is_string($str)) {
            return false;
        }

        return false; // todo
        // return preg_match('/^[\w]* $/', $str);
    }

    /**
     * Returns the dirtness level of the userialized string.
     *
     * @return integer
     */
    public function getDirtiness()
    {
        return $this->dirtiness;
    }

}
