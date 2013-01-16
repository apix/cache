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

class MongoDb extends AbstractCache
{

    /**
     * Mongo database object
     * @var object
     */
    public $db;

    /**
     * Mongo collection object
     * @var object
     */
    public $collection;

    /**
     * Constructor.
     *
     * @param \MongoDb $MongoDb   The MongoDb database to instantiate.
     * @param array  $options Array of options.
     */
    public function __construct(\MongoClient $Mongo, array $options=null)
    {
        $this->options['db_name'] = 'apix';
        $this->options['collection_name'] = 'cache';

        parent::__construct($Mongo, $options);

        $this->db = $this->adapter->selectDB($this->options['db_name']);
        $this->collection = $this->db->createCollection($this->options['collection_name'], false);
    }

    /**
     * {@inheritdoc}
     */
    public function load($key, $type='key')
    {
        if ($type == 'tag') {
            $cache = $this->collection->find(
                array('tags' => $this->mapTag($key)),
                array('key')
            );

            $keys = array_map(
                function($v) { return $v['key']; },
                array_values(iterator_to_array($cache))
            );

            return empty($keys) ? null : $keys;
        }

        $cache = $this->collection->findOne(
            array('key' => $this->mapKey($key)
                ),
            array('data')
        );

        return isset($cache['data']) ? $cache['data'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function save($data, $key, array $tags=null, $ttl=null)
    {
        $cache = array(
            'key'   => $this->mapKey($key),
            'data'  => $data
        );

        if (null !== $tags) {
            $cache['tags'] = array();
            foreach($tags as $tag){
                $cache['tags'][] = $this->mapTag($tag);
            }
        }

        if (null !== $ttl && 0 !== $ttl) {
            $cache['ttl'] = $ttl;
            // 'expireAfterSeconds' => $ttl

        }

        $status = $this->collection->save($cache);
        return (boolean) $status['ok'];
    }

    /**
     * {@inheritdoc}
     */
    public function clean(array $tags)
    {
        foreach ($tags as $tag) {
            $res = $this->collection->remove(
                array('tags' => $this->mapTag($tag))
            );
        }

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
        } else {
            $regex = new \MongoRegex('/^' . $this->mapKey('') . '/'); 
            $res = $this->collection->remove( array('key' => $regex) );
        }

        return (boolean) $res['ok'];
    }

}
