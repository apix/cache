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
 * The Mysql (PDO) wrapper.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class Mysql extends AbstractPdo
{

    /**
     * Holds the SQL definitions for MySQL 3.x, 4.x and 5.x.
     */
    public $sql_definitions = array(
        'init'      => 'CREATE TABLE IF NOT EXISTS %s (`key` VARCHAR(255) NOT NULL,
                        `data` LONGTEXT NULL, `tags` TEXT NULL, `expire` INTEGER
                        UNSIGNED, `dated` TIMESTAMP, PRIMARY KEY (`key`))
                        ENGINE=MYISAM DEFAULT charset=utf8;',
        'key_idx'   => 'CREATE INDEX `%s_key_idx` ON `%s` (`key`);',
        'exp_idx'   => 'CREATE INDEX `%s_exp_idx` ON `%s` (`expire`);',

        // 'tag_idx' will throw MYSQL ERROR 1170 -- if the index is needed then
        // we should split keys and tags into diff tables and use varchar(255).
        // 'tag_idx'   => 'CREATE INDEX `%s_tag_idx` ON `%s` (`tags`);',

        'loadKey'   => 'SELECT `data`, `expire` FROM `%s` WHERE `key`=:key AND
                        (`expire` IS NULL OR `expire` > :now);',
        'loadTag'   => 'SELECT `key` FROM `%s` WHERE `tags` LIKE :tag AND
                        (`expire` IS NULL OR `expire` > :now);',
        'update'    => 'UPDATE `%s` SET `data`=:data, `tags`=:tags, `expire`=:exp,
                        `dated`=:dated WHERE `key`=:key;',
        'insert'    => 'INSERT INTO `%s` (`key`, `data`, `tags`, `expire`, `dated`)
                        VALUES (:key, :data, :tags, :exp, :dated);',
        'delete'    => 'DELETE FROM `%s` WHERE `key`=?;',
        'clean'     => 'DELETE FROM `%s` WHERE %s;', // %s 'clean_like' iterated
        'clean_like'=> 'tags LIKE ?',
        'flush_all' => 'DROP TABLE IF EXISTS `%s`;',
        'flush'     => 'DELETE FROM `%s`;',
        'purge'     => 'DELETE FROM `%s` WHERE `expire` IS NOT NULL AND `expire` < %d;'
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
