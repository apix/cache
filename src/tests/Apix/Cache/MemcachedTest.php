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

class MemcachedTest extends TestCase
{
    protected $cache, $memcached;

    protected $options = array(
        'prefix_key' => 'unit_test-',
        'prefix_tag' => 'unit_utest-',
    );

    public function setUp()
    {
        $this->skipIfMissing('memcached');

        try {
            $this->memcached = new \Memcached;
        } catch (\Exception $e) {
            $this->markTestSkipped( $e->getMessage() );
        }

       $this->cache = new Memcached($this->memcached, $this->options);
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush(true);
            $this->memcached->quit();
            unset($this->cache, $this->memcached);
        }
    }

    public function testLoadReturnsNullWhenEmpty()
    {
        $this->assertNull($this->cache->load('id'));
    }

    public function testSaveIsUnique()
    {
        $this->assertTrue($this->cache->save('bar1', 'foo'));
        $this->assertTrue($this->cache->save('bar2', 'foo'));

        $this->assertEquals('bar2', $this->cache->load('foo'));

        $this->assertEquals(1, $this->cache->count('foo') );
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

    public function testSaveAndLoadArray()
    {
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

        $this->cache->getAdapter()->set('foo', 'bar');

        $this->assertTrue($this->cache->flush());

        $this->assertEquals(
            'bar', $this->cache->getAdapter()->get('foo')
        );

        $this->assertNull($this->cache->load('id3'));
        $this->assertNull($this->cache->load('tag1', 'tag'));
    }

    public function testFlushAll()
    {
        $this->cache->save('strData1', 'id1', array('tag1', 'tag2'));
        $this->cache->save('strData2', 'id2', array('tag2', 'tag3'));
        $this->cache->save('strData3', 'id3', array('tag3', 'tag4'));

        $this->cache->getAdapter()->set('foo', 'bar');

        $this->assertTrue($this->cache->flush(true));
        $this->assertNull($this->cache->getAdapter()->get('foo'));

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

        // How to forcibly run garbage collection?
        // $this->cache->db->command(array(
        //     'reIndex' => 'cache'
        // ));

        $this->assertNull( $this->cache->load('ttlId') );
    }

}
