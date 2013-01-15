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

        $this->db = $this->adapter->{$this->options['db_name']};
        $this->collection = $this->db->{$this->options['collection_name']};
    }

    /**
     * {@inheritdoc}
     */
    public function load($key, $type='key')
    {
        if ($type == 'tag') {
            $tag = $this->mapTag($key);
            $cache = $this->collection->find(
                array('tags' => $tag)
            );

            return empty($cache) ? null : $cache;
        }

        $key;
        #$key = $this->mapKey($key);
        $res = $this->collection->findOne(array('key' => $key));

// foreach ($res as $doc) {
//     var_dump($doc);
// }

    var_dump($res);

exit;

        return $this->collection->findOne(
            array('key' => $key, #'ttl' => array('$gte' => new \MongoDate)
                ),
            array('data')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function save($data, $key, array $tags=null, $ttl=null)
    {
        #$key = $this->mapKey($key);

        $cache = array(
            'data'  => $data
        );

        if (null !== $tags) {
            $cache['tags'] = $tags;
        }

        if (null !== $ttl && 0 !== $ttl) {
            $cache['ttl'] = $ttl;
        }

        $res = $this->collection->update(
            array('key' => $key),
            $cache
        );

        return $res['ok'] == 1  ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function clean(array $tags)
    {
        $items = array();
        foreach ($tags as $tag) {
            $items[] = $this->mapTag($tag);
        }

        return $this->collection->remove( array('tags' => $items)) ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $key = $this->mapKey($key);
        return $this->collection->remove(
            array('key' => $key)
        ) ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function flush($all=false)
    {
        if (true === $all) {
            $res = $this->db->drop();
        } else {
            $res = $this->collection->remove();
        }

        return $res['ok'] == 1 ? true : false;
    }

}
