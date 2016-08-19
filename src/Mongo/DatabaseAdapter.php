<?php

namespace Apix\Cache\Mongo;

use MongoDB\Database;

/**
 * allows to use \MongoDB\Database in the code as \MongoDB
 */
class DatabaseAdapter /*extends \MongoDB*/
{
    /**
     * @var Database
     */
    private $db;

    /**
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $name
     * @param array $options
     * @return CollectionAdapter
     */
    public function createCollection($name, $options)
    {
        return new CollectionAdapter($this->db->selectCollection($name, $options));
    }
}