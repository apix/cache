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

class MongoDbTest extends TestCase
{
    protected $cache, $mongo;

    protected $options = array(
        'prefix_key' => 'unittest-apix-key:',
        'prefix_tag' => 'unittest-apix-tag:',
        'serializer' => 'php' // null, php, igBinary.
    );

    public function setUp()
    {
        if (!extension_loaded('mongo')) {
            $this->markTestSkipped(
                'The MongoDB PHP driver is required to run this unit test.'
            );
        }

        try {
            $this->mongo = new \MongoClient();
        } catch (\Exception $e) {
            $this->markTestSkipped( $e->getMessage() );
        }

       $this->cache = new MongoDb($this->mongo, $this->options);
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush();
            $this->mongo->close();
            unset($this->cache);
        }
    }

    public function testLoadReturnsNullWhenEmpty()
    {
        $this->assertNull( $this->cache->load('id') );
    }

    public function testSaveAndLoadWithString()
    {
        $this->assertTrue($this->cache->save('strData', 'id'));

$cursor = $this->cache->collection->find();
foreach ($cursor as $doc) {
    var_dump($doc);
}

        $this->assertEquals('strData', $this->cache->load('id'));
        exit;
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

    public function testSaveAndLoadArray()
    {
        $this->cache = new MongoDb($this->mongo, $this->options);

        $data = array('arrayData');
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

        $this->cache->clean(array('tag4'));

        $this->assertNull($this->cache->load('id3'));
        $this->assertNull($this->cache->load('tag4', 'tag'));
        $this->assertEquals('strData1', $this->cache->load('id1'));
    }

    public function testFlushSelected()
    {
        $this->cache->save('strData1', 'id1', array('tag1', 'tag2'));
        $this->cache->save('strData2', 'id2', array('tag2', 'tag3'));
        $this->cache->save('strData3', 'id3', array('tag3', 'tag4'));

        $this->cache->collection->insert(array('foo' => 'bar'));
        $this->cache->flush();
        $this->assertTrue($this->cache->collection->findOne(array('foo')));

        $this->assertNull($this->cache->load('id3'));
        $this->assertNull($this->cache->load('tag1', 'tag'));
    }

    public function testFlushAll()
    {
        $this->cache->save('strData1', 'id1', array('tag1', 'tag2'));
        $this->cache->save('strData2', 'id2', array('tag2', 'tag3'));
        $this->cache->save('strData3', 'id3', array('tag3', 'tag4'));

        $this->cache->collection->insert(array('foo' => 'bar'));
        $this->cache->flush(true);
        $this->assertFalse($this->cache->collection->findOne(array('foo')));

        $this->assertNull($this->cache->load('id3'));
        $this->assertNull($this->cache->load('tag1', 'tag'));
    }

    public function testDelete()
    {
        $this->cache->save('strData1', 'id1', array('tag1', 'tag2', 'tagz'));
        $this->cache->save('strData2', 'id2', array('tag2', 'tag3'));

        $this->cache->delete('id1');

        $this->assertNull($this->cache->load('id1'));
        $this->assertNull($this->cache->load('tag1', 'tag'));
        $this->assertNull($this->cache->load('tagz', 'tag'));

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
        $this->cache->save('ttl-1', 'ttlId', null, -1);

        // $this->cache->save('ttl-1', 'ttlId', null, 1);
        // sleep(1);

        $this->assertNull( $this->cache->load('ttlId'), "Should be null");
    }

}
