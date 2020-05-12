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

namespace Apix\Cache\Pdo;

use Apix\Cache\AbstractPdo;

/**
 * The PostgreSQL (PDO) wrapper.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class Pgsql extends AbstractPdo
{

    /**
     * Holds the SQL definitions for PostgreSQL.
     */
    protected $sql_definitions = array(
        'init'      => 'CREATE TABLE IF NOT EXISTS "%s"
                        ("key" VARCHAR PRIMARY KEY, "data" TEXT, "tags" TEXT,
                        "expire" INTEGER, "dated" TIMESTAMP);',
        'key_idx'   => 'CREATE INDEX "%s_key_idx" ON "%s" ("key");',
        'exp_idx'   => 'CREATE INDEX "%s_exp_idx" ON "%s" ("expire");',
        'tag_idx'   => 'CREATE INDEX "%s_tag_idx" ON "%s" ("tags");',
        'loadKey'   => 'SELECT "data", "expire" FROM "%s" WHERE "key"=:key AND
                        ("expire" IS NULL OR "expire" > :now);',
        'loadTag'   => 'SELECT "key" FROM "%s" WHERE "tags" LIKE :tag AND
                        ("expire" IS NULL OR "expire" > :now);',
        'update'    => 'UPDATE "%s" SET "data"=:data, "tags"=:tags, "expire"=:exp,
                        "dated"=:dated WHERE "key"=:key;',
        'insert'    => 'INSERT INTO "%s" ("key", "data", "tags", "expire", "dated")
                        VALUES (:key, :data, :tags, :exp, :dated);',
        'delete'    => 'DELETE FROM "%s" WHERE "key"=?;',
        'clean'     => 'DELETE FROM "%s" WHERE %s;', // %s 'clean_like' iterated
        'clean_like'=> 'tags LIKE ?',
        'flush_all' => 'DELETE FROM "%s";',
        'flush'     => 'DELETE FROM "%s" WHERE "key" LIKE ? OR "tags" LIKE ?;',
        'purge'     => 'DELETE FROM "%s" WHERE "expire" IS NOT NULL AND "expire" < %d;'
    );

    /**
     * Constructor.
     *
     * @param \PDO  $pdo
     * @param array $options Array of options.
     */
    public function __construct(\PDO $pdo, array $options=null)
    {
        parent::__construct($pdo, $options);
    }

}
