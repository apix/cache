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
 * The SQL:1999 / SQL 3 (PDO) cache wrapper.
 *
 * Allows to use databases that have 'some' SQL:1999 compliance.
 * e.g. Oracle, IBM DB2, Informix.
 * PostgreSQL, MySQl and SQLite should also support SQL99.
 *
 * Conforms to at least SQL-92 are DB2, MSSQL, MySQL, Oracle, Informix.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class Sql1999 extends AbstractPdo
{

    /**
     * Holds a generic SQL-99'ish schema definitions.
     * @see http://www.contrib.andrew.cmu.edu/~shadow/sql/sql1992.txt
     */
    protected $sql_definitions = array(
        'init'      => 'CREATE TABLE "%s" ("key" VARCHAR PRIMARY KEY, "data" TEXT,
                        "tags" TEXT, "expire" INTEGER, "dated" TIMESTAMP);',
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
        'flush_all' => 'DROP TABLE IF EXISTS "%s";',
        'flush'     => 'DELETE FROM "%s";',
        'purge'     => 'DELETE FROM "%s" WHERE "expire" IS NOT NULL AND "expire" < %d;'
    );

}
