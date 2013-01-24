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

use Apix\Cache\Memcached;

/**
 * Memcached index.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 *
 * @TODO: namespacing?!
 * @see http://code.google.com/p/memcached/wiki/NewProgrammingTricks
 * @TODO: tag set?!
 * @see http://dustin.github.com/2011/02/17/memcached-set.html
 *
 */
class MemcachedIndex
{

    const DIRTINESS_THRESHOLD = 100;

    /**
     * Holds the name of the index.
     * @var array
     */
    protected $index;

    /**
     * Holds the index items.
     * @var array
     */
    protected $items = null;

    /**
     * Holds this index dirtiness.
     * @var integer
     */
    protected $dirtiness;

    /**
     * Holds the CAS token.
     * @var array
     */
    private $token = null;

    /**
     * Constructor.
     *
     * @param Apix\Cache\Memcached $Memcached A Memcached instance.
     * @param array                $options   Array of options.
     */
    public function __construct(Memcached $engine, $index)
    {
        $this->engine = $engine;
        $this->index = $index;

        $this->options['prefix_idx'] = 'idx_'; // prefix cache index
    }

    /**
     * Gets the adapter.
     *
     * @return \Memcached
     */
    public function getAdapter()
    {
        return $this->engine->getAdapter();
    }

    /**
     * Adds one or many element(s) to the index.
     *
     * @param  array   $context The elements to remove from the index.
     * @return Returns True on success or False on failure.
     */
    public function add($elements)
    {
        $str = $this->serialize($elements, '+');

        $success = $this->getAdapter()->append($this->index, $str);

        if (!$success) {
            $success = $this->getAdapter()->add($this->index, $str);
        }

        return (boolean) $success;
    }

    /**
     * Removes one or many element(s) from the index.
     *
     * @param  array|string $items The items to remove from the index.
     * @return Returns      True on success or False on failure.
     */
    public function remove($elements)
    {
        $str = $this->serialize($elements, '-');

        return (boolean) $this->getAdapter()->append($this->index, $str);
    }

    /**
     * Returns the indexed items.
     *
     * @param  array   $context The elements to remove from the index.
     * @return Returns True on success or False on failure.
     */
    public function load()
    {
        $str = $this->engine->get($this->index, $this->token);

        if (null !== $str) {
            $this->items = $this->unserialize($str);

            if ($this->dirtiness > self::DIRTINESS_THRESHOLD) {
                $this->purge();
            }

            return $this->items;
        }
    }

    /**
     * Purge the index.
     *
     * @return [type] [description]
     */
    public function purge()
    {
        $str = $this->serialize($this->items, '+');

        return $this->getAdapter()->cas($this->token, $this->index, $str);
    }

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
     * Unserialises the given string.
     * e.g. '+a +b +c -b -x' => ['a','c'];
     * Sets the dirtiness level (2 in thaht case).
     *
     * @param  string $string
     * @return array
     */
    public function unserialize($str)
    {
        $add    = array();
        $remove = array();
        foreach (explode(' ', trim($str)) as $k) {
            $key = substr($k, 1);
            $op = $k[0];
            if ($op == '+') {
                $add[] = $key;
            } else {
                $remove[] = $key;
            }
        }

        $this->dirtiness = count($remove);

        $items = array_values(array_diff($add, $remove));

        return empty($items) ? null : $items;
    }

}
