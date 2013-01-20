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

class Redis extends AbstractCache
{

    /**
     * Constructor.
     *
     * @param \Redis $redis   The redis database to instantiate.
     * @param array  $options Array of options.
     */
    public function __construct(\Redis $redis, array $options=null)
    {
        $options['atomicity'] = !isset($options['atomicity'])
                                || true === $options['atomicity']
                                 ? \Redis::MULTI
                                 : \Redis::PIPELINE;

        $this->options['serializer'] = 'igBinary'; // none, php, igBinary.

        parent::__construct($redis, $options);

        $redis->setOption(
            \Redis::OPT_SERIALIZER,
            $this->getSerializer($this->options['serializer'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function load($key, $type='key')
    {
        if ($type == 'tag') {
            $cache = $this->adapter->sMembers(
                $this->mapTag($key)
            );

            return empty($cache) ? null : $cache;
        }
        $cache = $this->adapter->get(
            $this->mapKey($key)
        );

        return false === $cache ? null : $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function save($data, $key, array $tags=null, $ttl=null)
    {
        $key = $this->mapKey($key);

        if (null === $ttl || 0 === $ttl) {
            $success = $this->adapter->set($key, $data);
        } else {
            $success = $this->adapter->setex($key, $ttl, $data);
        }

        if ($success && $this->options['tag_enable'] && !empty($tags)) {
            $redis = $this->adapter->multi($this->options['atomicity']);
            foreach ($tags as $tag) {
                $redis->sAdd($this->mapTag($tag), $key);
            }
            $redis->exec();
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function clean(array $tags)
    {
        $items = array();
        foreach ($tags as $tag) {
            $keys = $this->load($tag, 'tag');
            if (is_array($keys)) {
                array_walk_recursive(
                    $keys,
                    function($key) use (&$items) { $items[] = $key; }
                );
            }
            $items[] = $this->mapTag($tag);
        }

        return (boolean) $this->adapter->del($items);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $key = $this->mapKey($key);

        if ($this->options['tag_enable']) {
            $tags = $this->adapter->keys($this->mapTag('*'));
            if (!empty($tags)) {
                $redis = $this->adapter->multi($this->options['atomicity']);
                foreach ($tags as $tag) {
                    $redis->sRem($tag, $key);
                }
                $redis->exec();
            }
        }

        return (boolean) $this->adapter->del($key);
    }

    /**
     * {@inheritdoc}
     */
    public function flush($all=false)
    {
        if (true === $all) {
            return $this->adapter->flushAll();
        }
        $items = array_merge(
            $this->adapter->keys($this->mapTag('*')),
            $this->adapter->keys($this->mapKey('*'))
        );

        return (boolean) $this->adapter->del($items);
    }

    /**
     * Returns a Redis constant
     *
     * @param  string  $name Can be php, igBinary or none.
     * @return integer Corresponding to a Redis constant.
     */
    public function getSerializer($name)
    {
        switch ($name) {
            case 'igBinary':
                // @codeCoverageIgnoreStart
                // igBinary is not always compiled on the host machine.
                return \Redis::SERIALIZER_IGBINARY;
                // @codeCoverageIgnoreEnd
            case 'php':
                return \Redis::SERIALIZER_PHP;
            case 'none':
            default:
                return \Redis::SERIALIZER_NONE;
        }
    }

}
