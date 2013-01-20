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

class PdoTest extends TestCase
{
    protected $cache, $pdo;

    protected $options = array(
    );

    public function pdoProvider()
    {
        $dbs = array(
            'sqlite' => array('pdo_sqlite', function(){return new \PDO('sqlite::memory:');}),
            // before_script:
            // mysql -e 'create database apix_cache;'
            'mysql' => array('pdo_mysql', function(){return new \PDO('mysql:dbname=apix_cache;host=127.0.0.1', 'root');}),
            // before_script:
            //   - psql -c 'create database apix_cache;' -U postgres
            'postgresql' => array('pdo_postgress', function(){return new \PDO('pgsql:dbname=apix_cache;host=127.0.0.1', 'postgres');})
        );
        $DB = getenv('DB');

        if (in_array($DB, array_keys($dbs))) {
            return $dbs[$DB];
        }

        $this->markTestSkipped('Unsupported DB environment.');
    }

    /**
     * @dataProvider pdoProvider
     */
    public function setUp()
    {
        list($ext_name, $pdo_dbh) = $this->pdoProvider();

        $this->skipIfMissing('pdo');
        $this->skipIfMissing($ext_name);

        try {
            $this->pdo = $pdo_dbh();
        } catch (\Exception $e) {
            $this->markTestSkipped( $e->getMessage() );
        }

       $this->cache = new Pdo($this->pdo, $this->options);
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
        );

        $this->assertTrue(
            $this->cache->save('strData2', 'id2', array('tag3', 'tag4'))
        );

        $ids = $this->cache->load('tag2', 'tag');

        $this->assertEquals( array($this->cache->mapKey('id1')), $ids );
    }

    public function testSaveWithTagDisabled()
    {
        return;
       $options = $this->options+array('tag_enable' => false);
       $this->cache = new Sqlite($this->sqlite, $options);

        $this->assertTrue(
            $this->cache->save('strData1', 'id1', array('tag1', 'tag2'))
        );

        $this->assertNull($this->cache->load('tag1', 'tag'));
    }

    public function testSaveWithOverlappingTags()
    {
        $this->assertTrue(
            $this->cache->save('strData1', 'id1', array('tag1', 'tag2'))
        );

        $this->assertTrue(
            $this->cache->save('strData2', 'id2', array('tag2', 'tag3'))
        );

        $ids = $this->cache->load('tag2', 'tag');
        $this->assertTrue(count($ids) == 2);
        $this->assertContains($this->cache->mapKey('id1'), $ids);
        $this->assertContains($this->cache->mapKey('id2'), $ids);
    }

    public function testClean()
    {
        $this->cache->save('strData1', 'id1', array('tag1', 'tag2'));
        $this->cache->save('strData2', 'id2', array('tag2', 'tag3', 'tag4'));
        $this->cache->save('strData3', 'id3', array('tag3', 'tag4'));

        $this->assertTrue($this->cache->clean(array('tag4')));
        $this->assertFalse($this->cache->clean(array('tag4')));

        $this->assertNull($this->cache->load('id2'));
        $this->assertNull($this->cache->load('id3'));
        $this->assertNull($this->cache->load('tag4', 'tag'));
        $this->assertEquals('strData1', $this->cache->load('id1'));
    }

    public function testFlushCacheOnly()
    {
        $this->cache->save('strData1', 'id1', array('tag1', 'tag2'));
        $this->cache->save('strData2', 'id2', array('tag2', 'tag3'));
        $this->cache->save('strData3', 'id3', array('tag3', 'tag4'));

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
        $this->cache->save('strData1', 'id1', array('tag1', 'tag2'));
        $this->cache->save('strData2', 'id2', array('tag2', 'tag3'));
        $this->cache->save('strData3', 'id3', array('tag3', 'tag4'));

        $this->assertTrue($this->cache->flush(true));

        $this->assertNull($this->cache->load('id3'));
        $this->assertNull($this->cache->load('tag1', 'tag'));
    }

    public function testDelete()
    {
        $this->cache->save('strData1', 'id1', array('tag1', 'tag2', 'tagz'));
        $this->cache->save('strData2', 'id2', array('tag2', 'tag3'));

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
        // How to forcibly run garbage collection?
        // $this->cache->db->command(array(
        //     'reIndex' => 'cache'
        // ));
    }

}
