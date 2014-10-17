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
 * Mongo cache wrapper.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class Mongo extends AbstractCache
{

    /**
     * Holds the array of TTLs.
     * @var array
     */
    protected $ttls = array();

    /**
     * Holds the MongoDB object
     * @var \MongoDB
     */
    public $db;

    /**
     * Holds the MongoCollection object
     * @var \MongoCollection
     */
    public $collection;

    /**
     * Constructor. Sets the Mongo DB adapter.
     *
     * @param \MongoClient $Mongo     A \MongoClient instance.
     * @param array        $options   Array of options.
     */
    public function __construct(\MongoClient $Mongo, array $options=null)
    {
        // default options
        $this->options['db_name'] = 'apix';
        $this->options['collection_name'] = 'cache';
        $this->options['object_serializer'] = 'php'; // null, php, json, igBinary.

        // Set the adapter and merge the user+default options
        parent::__construct($Mongo, $options);

        $this->db = $this->adapter->selectDB($this->options['db_name']);
        $this->collection = $this->db->createCollection(
                                $this->options['collection_name'],
                                false
                            );

        $this->collection->ensureIndex(
                                array('key' => 1),
                                array(
                                  'unique'   => true,
                                  'dropDups' => true,
                                  // 'sparse'   => true
                                )
                            );

        // Using MongoDB TTL collections (MongoDB 2.2+)
        $this->collection->ensureIndex(
                                array('expire' => 1),
                                array('expireAfterSeconds' => 1)
                            );

        $this->setSerializer($this->options['object_serializer']);
    }

    /**
     * {@inheritdoc}
     */
    public function loadKey($key)
    {
        $mKey = $this->mapKey($key);
        $cache = $this->get($mKey);

        // check expiration
        if (
            null === $cache
            or ( isset($cache['expire']) && (string) $cache['expire']->sec < time() )
        ) {
            unset($this->ttls[$mKey]);

            return null;
        }

        return null !== $this->serializer
              && $this->serializer->isSerialized($cache['data'])
              ? $this->serializer->unserialize($cache['data'])
              : $cache['data'];
    }

    /**
     * Retrieves the cache item for the given id.
     *
     * @param  string     $key The cache key to retrieve.
     * @return mixed|null Returns the cached data or null.
     */
    public function get($key)
    {
        $cache = $this->collection->findOne(
            array('key' => $key),
            array('data', 'expire')
        );

        if ($cache !== null) {
            $this->ttls[$key] = isset($cache['expire'])
                                    ? $cache['expire']->sec - time()
                                    : 0;
        }

        return $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function loadTag($tag)
    {
        $cache = $this->collection->find(
            array('tags' => $this->mapTag($tag)),
            array('key')
        );

        $keys = array_map(
            function ($v) { return $v['key']; },
            array_values(iterator_to_array($cache))
        );

        return empty($keys) ? null : $keys;
    }

    /**
     * {@inheritdoc}
     */
    public function save($data, $key, array $tags=null, $ttl=null)
    {
        $key = $this->mapKey($key);

        if (null !== $this->serializer && is_object($data)) {
            $data = $this->serializer->serialize($data);
        }

        $cache = array('key' => $key, 'data'  => $data);

        if ($this->options['tag_enable'] && null !== $tags) {
            $cache['tags'] = array();
            foreach ($tags as $tag) {
                $cache['tags'][] = $this->mapTag($tag);
            }
        }

        if (null !== $ttl && 0 !== $ttl) {
            $expire = time()+$ttl;
            $cache['expire'] = new \MongoDate($expire);
        }

        $this->ttls[$key] = isset($cache['expire'])
                                ? $cache['expire']->sec - time()
                                : 0;

        $res = $this->collection->update(
            array('key' => $key), $cache, array('upsert' => true)
        );

        return (boolean) $res['ok'];
    }

    /**
     * {@inheritdoc}
     */
    public function clean(array $tags)
    {
        $items = array();
        foreach ($tags as $tag) {
            $items += (array) $this->loadTag($tag);
        }
        $res = $this->collection->remove(
                    array('key'=>array('$in'=>$items))
                );

        return (boolean) $res['n'];
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $res = $this->collection->remove(
                    array('key' => $this->mapKey($key))
                );

        return (boolean) $res['n'];
    }

    /**
     * {@inheritdoc}
     */
    public function flush($all=false)
    {
        if (true === $all) {
            $res = $this->db->drop();

            return (boolean) $res['ok'];
        }
        // $res = $this->collection->drop();
        $regex = new \MongoRegex('/^' . $this->mapKey('') . '/');
        $res = $this->collection->remove( array('key' => $regex) );

        return (boolean) $res['ok'];
    }

    /**
     * Counts the number of cached items.
     *
     * @return integer Returns the number of items in the cache.
     */
    public function count()
    {
        return (integer) $this->collection->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getTtl($key)
    {
        $mKey = $this->mapKey($key);

        return !isset($this->ttls[$mKey]) && null === $this->get($mKey)
                ? false
                : $this->ttls[$mKey];
    }

}
