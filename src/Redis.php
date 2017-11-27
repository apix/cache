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
 * Redis/PhpRedis cache wrapper.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class Redis extends AbstractCache
{

    /**
     * Constructor.
     *
     * @param \Redis $redis   A Redis client instance.
     * @param array  $options Array of options.
     */
    public function __construct(\Redis $redis, array $options=null)
    {
        $options['atomicity'] = !isset($options['atomicity'])
                                || true === $options['atomicity']
                                 ? \Redis::MULTI
                                 : \Redis::PIPELINE;

        $this->options['serializer'] = 'php'; // null, php, igBinary, json,
                                              // msgpack

        parent::__construct($redis, $options);

        $this->setSerializer($this->options['serializer']);
        $redis->setOption( \Redis::OPT_SERIALIZER, $this->getSerializer() );
    }

    /**
     * {@inheritdoc}
     */
    public function loadKey($key)
    {
        $cache = $this->adapter->get($this->mapKey($key));

        return false === $cache ? null : $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function loadTag($tag)
    {
        $cache = $this->adapter->sMembers($this->mapTag($tag));

        return empty($cache) ? null : $cache;
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
            $keys = $this->loadTag($tag);
            if (is_array($keys)) {
                array_walk_recursive(
                    $keys,
                    function ($key) use (&$items) { $items[] = $key; }
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
            return $this->adapter->flushDb();
        }
        $items = array_merge(
            $this->adapter->keys($this->mapTag('*')),
            $this->adapter->keys($this->mapKey('*'))
        );

        return (boolean) $this->adapter->del($items);
    }

    /**
     * {@inheritdoc}
     * @param string $serializer
     */
    public function setSerializer($serializer)
    {
        switch ($serializer) {
            case 'json':
                // $this->serializer = \Redis::SERIALIZER_JSON;
                parent::setSerializer($serializer);
            break;

            case 'php':
                $this->serializer = \Redis::SERIALIZER_PHP;
            break;

            // @codeCoverageIgnoreStart
            case 'igBinary':
                // igBinary is not always compiled on the host machine.
                $this->serializer = \Redis::SERIALIZER_IGBINARY;
            break;

            case 'msgpack':
                // available on PHP7 since msgpack 2.0.1
                $this->serializer = \Redis::SERIALIZER_MSGPACK;
            break;
            // @codeCoverageIgnoreEnd

            default:
                $this->serializer = \Redis::SERIALIZER_NONE;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTtl($key)
    {
        $ttl = $this->adapter->ttl($this->mapKey($key));

        if ($ttl == -2) {
           return false;
        }

        return $ttl > -1 ? $ttl : 0;
    }

}
