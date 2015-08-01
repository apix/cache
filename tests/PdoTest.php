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
    /**
     * @var \Apix\Cache\AbstractPdo
     */
    protected $cache;

    /**
     * @var \PDO
     */
    protected $pdo;

    protected $options = array(
        'db_name'  => 'apix_tests',
        'db_table' => 'cache'
    );

    /**
     * @var string
     */
    protected $classname = '';

    /**
     * @return array|void
     */
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
            ),

        );
        $DB = getenv('DB') ? getenv('DB') : 'sqlite';

        if (in_array($DB, array_keys($dbs))) {
            return $dbs[$DB];
        }

        self::markTestSkipped("Unsupported DB ($DB) environment.");
    }

    public function setUp()
    {
        list($ext_name, $dbh, $class) = $this->pdoProvider();

        $this->skipIfMissing('pdo');
        $this->skipIfMissing($ext_name);

        try {
            $this->pdo = $dbh();
        } catch (\Exception $e) {
            self::markTestSkipped( $e->getMessage() );
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
        self::assertTrue($this->cache->save('bar_1', 'foo'));
        self::assertEquals('bar_1', $this->cache->load('foo'));

        self::assertTrue($this->cache->save('bar_2', 'foo'));
        self::assertEquals('bar_2', $this->cache->load('foo'));

        // self::assertEquals(1, $this->cache->getAdapter()->rowCount() );
    }

    public function testFlushCacheOnly()
    {
        self::assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );
        // $foo = array('foo' => 'bar');
        // $this->cache->getAdapter()->add($foo);

        self::assertTrue($this->cache->flush());

        // self::assertEquals(
        //     $foo,
        //     $this->cache->getAdapter()->findOne(array('foo'=>'bar'))
        // );

        self::assertNull($this->cache->load('id3'));
        self::assertNull($this->cache->load('tag1', 'tag'));
    }

    /**
     * @expectedException \PDOException
     */
    public function testFlushAll()
    {
        self::assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );

        self::assertTrue($this->cache->flush(true));

        self::assertNull($this->cache->load('id3'));
        self::assertNull($this->cache->load('tag1', 'tag'));
    }

    public function testShortTtlDoesExpunge()
    {
        self::assertTrue(
            $this->cache->save('ttl-1', 'ttlId', array('someTags!'), -1)
        );

        self::assertNull( $this->cache->load('ttlId') );
    }

    public function testTtlSetToNull()
    {
        self::assertTrue(
            $this->cache->save('ttl-null', 'ttlId', array('someTags!'), null)
        );

        self::assertEquals('ttl-null', $this->cache->load('ttlId') );
    }

    public function testPurge()
    {
        self::assertTrue(
            $this->cache->save('120s', 'id1', null, 120)
            && $this->cache->save('600s', 'id2', null, 600)
        );
        self::assertEquals('120s', $this->cache->load('id1'));
        self::assertTrue($this->cache->purge(130));
        self::assertFalse($this->cache->purge());
        self::assertNull($this->cache->load('id1'));

        self::assertEquals('600s', $this->cache->load('id2'));
        self::assertTrue($this->cache->purge(630));
        self::assertNull($this->cache->load('id2'));
    }

    public function testcreateIndexTableReturnsFalse()
    {
        self::assertFalse( $this->cache->createIndexTable('not-defined') );
    }

    public function testGetDriverName()
    {
        if ($this->classname != 'Apix\Cache\Pdo\Sql1999') {
            self::assertSame(
                $this->classname,
                'Apix\\Cache\\Pdo\\'
                . Cache\AbstractPdo::getDriverName($this->pdo)
            );
        }
    }

}
