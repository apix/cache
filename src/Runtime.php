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
 * Runtime (Array/ArrayObject) cache wrapper.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class Runtime extends AbstractCache
{
    /**
     * Holds the cached items.
     * e.g. ['key' => ['data', 'tags', 'expire']]
     * @var \ArrayObject
     */
    protected $items;

    /**
     * Constructor.
     */
    public function __construct($array = null, array $options=array())
    {
        $this->items = $array !== null ? $array : new \ArrayObject();

        // if (! is_array($array) || is_a( $this->items, 'ArrayObject') ) )

        parent::__construct(null, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function loadKey($key)
    {
        $key = $this->mapKey($key);

        return isset($this->items[$key]) ? $this->items[$key]['data'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function loadTag($tag)
    {
        if ($this->options['tag_enable']) {
            $tag = $this->mapTag($tag);
            $keys = array();
            foreach ($this->items as $key => $data) {
                if( isset($data['tags'])
                    && false !== array_search($tag, $data['tags'])
                ) {
                    $keys[] = $key;
                }
            }

            return empty($keys) ? null : $keys;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save($data, $key, array $tags=null, $ttl=null)
    {
        $key = $this->mapKey($key);
        $this->items[$key] = array(
            'data'   => $data,
            'expire' => $ttl
        );

        if ($this->options['tag_enable'] && !empty($tags)) {
            $_tags = array();
            foreach ($tags as $tag) {
                $_tags[] = $this->mapTag($tag);
            }
            $this->items[$key]['tags'] = array_unique($_tags);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $key = $this->mapKey($key);

        if ( isset($this->items[$key]) ) {
            unset($this->items[$key]);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function clean(array $tags)
    {
        $rmed = array();
        foreach ($tags as $tag) {
            $keys = $this->loadTag($tag);
            if ($keys) {
                foreach ($keys as $key) {
                    unset($this->items[$key]);
                }
            } else {
                $rmed[] = false;
            }
        }

        return !in_array(false, $rmed);
    }

    /**
     * {@inheritdoc}
     */
    public function flush($all=false)
    {
        $this->items = new \ArrayObject();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getTtl($key)
    {
        $key = $this->mapKey($key);

        return isset($this->items[$key]) ? $this->items[$key]['expire'] : false;
    }

}
