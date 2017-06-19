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

namespace Apix\Cache\tests;

use Apix\Cache;

/**
 * @covers Apix\Cache\AbstractPdo
 * @covers Apix\Cache\Pdo\Sqlite
 * @covers Apix\Cache\Pdo\Mysql
 * @covers Apix\Cache\Pdo\Pgsql
 * @covers Apix\Cache\Pdo\Sql1999
 */
class PdoTest extends GenericTestCase
{
    protected $cache, $pdo;

    protected $options = array(
        'db_name'  => 'apix_tests',
        'db_table' => 'cache'
    );

    public function pdoProvider()
    {
        $dbs = array(
            'sqlite' => array(
                'pdo_sqlite',
                function () {
                    return new \PDO('sqlite::memory:');
                },
                'Sqlite'
            ),
            'mysql' => array(
                'pdo_mysql',
                function () {
                    return new \PDO(
                        'mysql:dbname=apix_tests;host=127.0.0.1', 'root'
                    );
                },
                'Mysql'
            ),
            'pgsql' => array(
                'pdo_pgsql',
                function () {
                    return new \PDO(
                        'pgsql:dbname=apix_tests;host=127.0.0.1', 'postgres'
                    );
                },
                'Pgsql'
            ),
            'sql1999' => array(
                'pdo_sqlite',
                function () {
                    return new \PDO('sqlite::memory:');
                },
                'Sql1999'
            )
        );
        $DB = getenv('DB') ? getenv('DB') : 'sqlite';

        if (in_array($DB, array_keys($dbs))) {
            return $dbs[$DB];
        }

        $this->markTestSkipped("Unsupported PDO DB ($DB) environment.");
    }

    public function setUp()
    {
        list($ext_name, $dbh, $class) = $this->pdoProvider();

        $this->skipIfMissing('pdo');
        $this->skipIfMissing($ext_name);

        try {
            $this->pdo = $dbh();
        } catch (\Exception $e) {
            $this->markTestSkipped( $e->getMessage() );
        }

        $this->classname = 'Apix\\Cache\\Pdo\\' . $class;
        $this->cache = new $this->classname($this->pdo, $this->options);
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush(true);
            unset($this->cache, $this->pdo, $this->classname);
        }
    }

    public function testSaveIsUnique()
    {
        $this->assertTrue($this->cache->save('bar_1', 'foo'));
        $this->assertEquals('bar_1', $this->cache->load('foo'));

        $this->assertTrue($this->cache->save('bar_2', 'foo'));
        $this->assertEquals('bar_2', $this->cache->load('foo'));

        // $this->assertEquals(1, $this->cache->getAdapter()->rowCount() );
    }

    public function testFlushCacheOnly()
    {
        $this->assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );
        // $foo = array('foo' => 'bar');
        // $this->cache->getAdapter()->add($foo);

        $this->assertTrue($this->cache->flush());

        // $this->assertEquals(
        //     $foo,
        //     $this->cache->getAdapter()->findOne(array('foo'=>'bar'))
        // );

        $this->assertNull($this->cache->load('id3'));
        $this->assertNull($this->cache->load('tag1', 'tag'));
    }

    /**
     * @expectedException PDOException
     */
    public function testFlushAll()
    {
        $this->assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );

        $this->assertTrue($this->cache->flush(true));

        $this->assertNull($this->cache->load('id3'));
        $this->assertNull($this->cache->load('tag1', 'tag'));
    }

    public function testShortTtlDoesExpunge()
    {
        $this->assertTrue(
            $this->cache->save('ttl-1', 'ttlId', array('someTags!'), -1)
        );

        $this->assertNull( $this->cache->load('ttlId') );
    }

    public function testTtlSetToNull()
    {
        $this->assertTrue(
            $this->cache->save('ttl-null', 'ttlId', array('someTags!'), null)
        );

        $this->assertEquals('ttl-null', $this->cache->load('ttlId') );
    }

    public function testPurge()
    {
        $this->assertTrue(
            $this->cache->save('120s', 'id1', null, 120)
            && $this->cache->save('600s', 'id2', null, 600)
        );
        $this->assertEquals('120s', $this->cache->load('id1'));
        $this->assertTrue($this->cache->purge(130));
        $this->assertFalse($this->cache->purge());
        $this->assertNull($this->cache->load('id1'));

        $this->assertEquals('600s', $this->cache->load('id2'));
        $this->assertTrue($this->cache->purge(630));
        $this->assertNull($this->cache->load('id2'));
    }

    public function testcreateIndexTableReturnsFalse()
    {
        $this->assertFalse( $this->cache->createIndexTable('not-defined') );
    }

    public function testGetDriverName()
    {
        if ($this->classname != 'Apix\Cache\Pdo\Sql1999') {
            $this->assertSame(
                $this->classname,
                'Apix\\Cache\\Pdo\\'
                . Cache\AbstractPdo::getDriverName($this->pdo)
            );
        }
    }

    /**
     * Regression test for pull request GH#28
     *
     * @link https://github.com/frqnck/apix-cache/pull/28
     *  PDOException: SQLSTATE[23000]: Integrity constraint violation:
     *  1062 Duplicate entry 'apix-cache-key:same_key' for key 'PRIMARY'
     * @group pr
     */
    public function testPullRequest28()
    {
        $this->cache->save('same_data', 'same_key');
        $this->cache->save('same_data', 'same_key');

        $this->assertEquals('same_data', $this->cache->load('same_key'));
    }

}
