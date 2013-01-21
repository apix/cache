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

use Apix\TestCase;

/**
 * @covers Apix\Cache\AbstractPdo
 * @covers Apix\Cache\Pdo\Mysql
 * @covers Apix\Cache\Pdo\Postgres
 * @covers Apix\Cache\Pdo\Sql1999
 * @covers Apix\Cache\Pdo\Sqlite
 */
class PdoTest extends TestCase
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
                function(){
                    return new \PDO('sqlite::memory:');
                },
                __NAMESPACE__ . '\\Pdo\\Sqlite'
            ),
            'mysql' => array(
                'pdo_mysql',
                function(){
                    return new \PDO(
                        'mysql:dbname=apix_tests;host=127.0.0.1', 'root'
                    );
                },
                __NAMESPACE__ . '\\Pdo\\Mysql'
            ),
            'pgsql' => array(
                'pdo_pgsql',
                function(){
                    return new \PDO(
                        'pgsql:dbname=apix_tests;host=127.0.0.1', 'postgres'
                    );
                },
                __NAMESPACE__ . '\\Pdo\\Postgres'
            ),
            'sql1999' => array(
                'pdo_sqlite',
                function(){
                    return new \PDO('sqlite::memory:');
                },
                __NAMESPACE__ . '\\Pdo\\Sql1999'
            ),

        );
        $DB = getenv('DB');

        if (in_array($DB, array_keys($dbs))) {
            return $dbs[$DB];
        }

        $this->markTestSkipped("Unsupported DB ($DB) environment.");
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

        $this->cache = new $class($this->pdo, $this->options);

        // create the indexes.
        $this->cache->createIndexes();
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush(true);
            unset($this->cache, $this->pdo);
        }
    }

    public function testLoadReturnsNullWhenEmpty()
    {
        $this->assertNull($this->cache->load('id'));
    }

    public function testSaveIsUnique()
    {
        $this->assertTrue($this->cache->save('bar_1', 'foo'));
        $this->assertEquals('bar_1', $this->cache->load('foo'));

        $this->assertTrue($this->cache->save('bar_2', 'foo'));

        $this->assertEquals('bar_2', $this->cache->load('foo'));
        // $this->assertEquals(1, $this->cache->getAdapter()->rowCount() );
    }

    public function testSaveAndLoadWithString()
    {
        $this->assertTrue($this->cache->save('strData', 'id'));

        $this->assertEquals('strData', $this->cache->load('id'));
    }

    public function testSaveAndLoadWithArray()
    {
        $data = array('foo' => 'bar');
        $this->assertTrue($this->cache->save($data, 'id'));
        $this->assertEquals($data, $this->cache->load('id'));
    }

    public function testSaveAndLoadWithObject()
    {
        $data = new \stdClass;
        $this->assertTrue($this->cache->save($data, 'id'));
        $this->assertEquals($data, $this->cache->load('id'));
    }

    public function testSaveWithTags()
    {
        $this->assertTrue(
            $this->cache->save('strData1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('strData2', 'id2', array('tag3', 'tag4'))
        );

        $ids = $this->cache->load('tag2', 'tag');

        $this->assertEquals( array($this->cache->mapKey('id1')), $ids );
    }

    public function testSaveWithTagDisabled()
    {
        $this->cache->setOptions(array('tag_enable' => false));

        $this->assertTrue(
            $this->cache->save('strData1', 'id1', array('tag1', 'tag2'))
        );

        $this->assertNull($this->cache->load('tag1', 'tag'));
    }

    public function testSaveWithOverlappingTags()
    {
        $this->assertTrue(
            $this->cache->save('strData1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('strData2', 'id2', array('tag2', 'tag3'))
        );

        $ids = $this->cache->load('tag2', 'tag');
        $this->assertTrue(count($ids) == 2);
        $this->assertContains($this->cache->mapKey('id1'), $ids);
        $this->assertContains($this->cache->mapKey('id2'), $ids);
    }

    public function testClean()
    {
        $this->assertTrue(
            $this->cache->save('strData2', 'id2', array('tag2', 'tag3', 'tag4'))
            && $this->cache->save('strData1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('strData3', 'id3', array('tag3', 'tag4'))
        );

        $this->assertTrue($this->cache->clean(array('tag4')));
        $this->assertFalse($this->cache->clean(array('tag4')));

        $this->assertNull($this->cache->load('id2'));
        $this->assertNull($this->cache->load('id3'));
        $this->assertNull($this->cache->load('tag4', 'tag'));
        $this->assertEquals('strData1', $this->cache->load('id1'));
    }

    public function testFlushCacheOnly()
    {
        $this->assertTrue(
            $this->cache->save('strData1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('strData2', 'id2', array('tag2', 'tag3'))
            && $this->cache->save('strData3', 'id3', array('tag3', 'tag4'))
        );
        // $foo = array('foo' => 'bar');
        // $this->cache->getAdapter()->insert($foo);

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
            $this->cache->save('strData1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('strData2', 'id2', array('tag2', 'tag3'))
            && $this->cache->save('strData3', 'id3', array('tag3', 'tag4'))
        );

        $this->assertTrue($this->cache->flush(true));

        $this->assertNull($this->cache->load('id3'));
        $this->assertNull($this->cache->load('tag1', 'tag'));
    }

    public function testDelete()
    {
        $this->assertTrue(
            $this->cache->save('strData1', 'id1', array('tag1', 'tag2', 'tagz'))
            && $this->cache->save('strData2', 'id2', array('tag2', 'tag3'))
        );

        $this->assertTrue($this->cache->delete('id1'));

        $this->assertNull($this->cache->load('id1'), 'msg1');
        $this->assertNull($this->cache->load('tag1', 'tag'), 'msg2');
        $this->assertNull($this->cache->load('tagz', 'tag'), 'msg3');

        $this->assertContains(
            $this->cache->mapKey('id2'),
            $this->cache->load('tag2', 'tag')
        );
    }

    public function testDeleteInexistant()
    {
        $this->assertFalse($this->cache->delete('Inexistant'));
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

}
