<?php

namespace Apix\Cache\Mongo;

use MongoDB\Collection;

/**
 * allows to use \MongoDB\Collection in the code as \MongoCollection
 */
class CollectionAdapter /*extends \MongoCollection*/
{
    /**
     * @var Collection
     */
    private $collection;

    /**
     * CollectionAdapter constructor.
     * @param Collection $collection
     */
    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    public function ensureIndex(array $keys, array $options = array())
    {
        $this->collection->createIndex($keys, $options);
    }

    public function insert($a, array $options = array())
    {
        try {
            $this->collection->insertOne($a, $options);
            return ['ok' => 1];
        } catch (\Exception $e) {
            return ['ok' => 0, 'error' => $e->getMessage()];
        }
    }

    public function update(array $criteria, array $newobj, array $options = array())
    {
        try {
            $this->collection->updateOne($criteria, array('$set' => $newobj), $options);
            return ['ok' => 1];
        } catch (\Exception $e) {
            return ['ok' => 0, 'error' => $e->getMessage()];
        }
    }

    public function remove(array $criteria = array(), array $options = array())
    {
        try {
            $result = $this->collection->deleteMany($criteria, $options);
            return ['ok' => 1, 'n' => $result->getDeletedCount()];
        } catch (\Exception $e) {
            return ['ok' => 0, 'error' => $e->getMessage()];
        }
    }

    public function find(array $query = array(), array $fields = array())
    {
        $options = ['projection' => array_fill_keys($fields, 1) + ['_id' => 0]];

        return $this->collection->find($query, $options);
    }

    public function findOne(array $query = array(), array $fields = array())
    {
        $options = ['projection' => array_fill_keys($fields, 1) + ['_id' => 0]];

        $result =  $this->collection->findOne($query, $options);

        // mimic \MongoDate->sec
        if (!empty($result['expire'])) {
            $sec = $result['expire']->toDateTime()->getTimestamp();
            $result['expire'] = new \stdClass();
            $result['expire']->sec = $sec;
        }

        return $result;
    }

    public function drop()
    {
        $this->collection->drop();
        return ['ok' => 1];
    }

    public function count($query = array())
    {
        return $this->collection->count($query);
    }
}