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
    const HOST = '127.0.0.1';
    const PORT = 11211;
    const AUTH = NULL;

    protected $cache, $memcached;

    protected $options = array(
        'prefix_key' => 'key_',
        'prefix_tag' => 'tag_',
        'prefix_idx' => 'idx_'
    );

    public function getMemcached()
    {
        try {
            $m = new \Memcached;
            $m->addServer(self::HOST, self::PORT);

            $stats = $m->getStats();
            $host = self::HOST.':'.self::PORT;
            if($stats[$host]['pid'] == -1)
                throw new \Exception(
                    sprintf('Unable to reach a memcached server on %s', $host)
                );

        } catch (\Exception $e) {
            $this->markTestSkipped( $e->getMessage() );
        }

        return $m;
    }

    public function setUp()
    {
        $this->skipIfMissing('memcached');
        $this->memcached = $this->getMemcached();
        $this->cache = new Memcached($this->memcached, $this->options);
    }

    public function OfftearDown()
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
        $this->assertTrue(
            $this->cache->save('bar1', 'foo')
            && $this->cache->save('bar2', 'foo')
        );
        $this->assertEquals('bar2', $this->cache->loadKey('foo'));

        // $this->assertEquals(1, $this->cache->count('foo') );
    }

    public function testSaveAndLoadWithString()
    {
        $this->assertTrue($this->cache->save('data', 'id'));
        $this->assertEquals('data', $this->cache->loadKey('id'));
    }

    public function testSaveAndLoadWithArray()
    {
        $data = array('foo' => 'bar');
        $this->assertTrue($this->cache->save($data, 'id'));
        $this->assertEquals($data, $this->cache->loadKey('id'));
    }

    public function testSaveAndLoadWithObject()
    {
        $data = new \stdClass;
        $this->assertTrue($this->cache->save($data, 'id'));
        $this->assertEquals($data, $this->cache->loadKey('id'));
    }

    public function testSaveAndLoadArray()
    {
        $data = array('arrayData');
        $this->assertTrue($this->cache->save($data, 'id'));
        $this->assertEquals($data, $this->cache->loadKey('id'));
    }

    public function testSaveJustOneTag()
    {
        $this->assertTrue( $this->cache->save('data', 'id', array('tag')) );
        $this->assertEquals(
            array($this->cache->mapKey('id')),
            $this->cache->loadTag('tag')
        );
    }

    public function testSaveManyTags()
    {
        $this->assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag3', 'tag4'))
        );

        $ids = $this->cache->loadTag('tag2');

        $this->assertEquals( array($this->cache->mapKey('id1')), $ids );
    }

    public function testSaveWithTagDisabled()
    {
        $this->cache->setOptions(array('tag_enable' => false));

        $this->assertTrue(
            $this->cache->save('data', 'id', array('tag1', 'tag2'))
        );

        $this->assertNull($this->cache->loadTag('tag1'));
    }

    public function testSaveWithOverlappingTags()
    {
        $this->assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
        );

        $ids = $this->cache->loadTag('tag2');
        $this->assertTrue(count($ids) == 2);
        $this->assertContains($this->cache->mapKey('id1'), $ids);
        $this->assertContains($this->cache->mapKey('id2'), $ids);
    }

    public function testClean()
    {
        $this->assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3', 'tag4'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );

        $this->assertTrue($this->cache->clean(array('tag4')));
        $this->assertFalse($this->cache->clean(array('tag4')));

        $this->assertNull($this->cache->load('id2'));
        $this->assertNull($this->cache->load('id3'));
        $this->assertNull($this->cache->load('tag4', 'tag'));
        $this->assertEquals('data1', $this->cache->loadKey('id1'));
    }

    public function commonMemcachedData()
    {
        return $this->assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3', 'tag4'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );
    }

    public function testFlushNamespace()
    {
        $this->commonMemcachedData();
        $this->assertSame('data3', $this->cache->loadKey('id3'));

        $otherMemcached = $this->getMemcached();
        $otherMemcached->add('foo', 'bar');

        $this->assertTrue($this->cache->flush(), "Flushing our namespace.");

        $this->assertEquals('bar', $otherMemcached->get('foo'));

        $this->assertNull($this->cache->loadKey('id3'));
        $this->assertNull($this->cache->loadTag('tag1'));
    }

    /**
     * @group encours
     * @dataprovider memcachedProvider
     */
    public function testFlushLeaveNamspaceIndex()
    {
        echo 'TODO';

        $this->commonMemcachedData();

        #$this->assertTrue($this->cache->flush(), "Flushing the namespace.");

        $idx = $this->cache->mapIdx($this->cache->getOption('namespace_key'));
        $this->assertNull($this->cache->loadIndex($idx));

    }

    public function testFlushAll()
    {
        $this->assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );

        $this->getMemcached()->add('foo', 'bar');

        $this->assertTrue($this->cache->flush(true));
        $this->assertNull($this->cache->get('foo'));
        $this->assertNull($this->cache->loadKey('id3'));
        $this->assertNull($this->cache->loadTag('tag1'));
    }

    public function testDelete()
    {
        $this->assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2', 'tagz'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
        );

        $this->assertTrue($this->cache->delete('id1'));

        $this->assertNull($this->cache->load('id1'));
        $this->assertNull(
            $this->cache->get($this->cache->mapIdx('id1'))
        );
        $this->assertNull($this->cache->loadTag('tag1'));
        $this->assertNull($this->cache->loadTag('tagz'));

        $this->assertContains(
            $this->cache->mapKey('id2'), $this->cache->loadTag('tag2')
        );
    }

    public function testDeleteWithTagDisabled()
    {
        $this->cache->setOptions(array('tag_enable' => false));

        $this->assertTrue(
            $this->cache->save('data', 'id', array('tag1', 'tag2'))
        );

        $this->assertTrue($this->cache->delete('id'));
        $this->assertNull($this->cache->loadTag('tag1'));
    }


    public function testDeleteInexistant()
    {
        $this->assertFalse($this->cache->delete('Inexistant'));
    }

    public function OFF_testShortTtlDoesExpunge()
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

    public function testIndexing()
    {
        $this->assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2', 'tag3'))
        );
        $idx = $this->cache->mapIdx('id1');
        $this->assertEquals(
            '+tag1 +tag2 +tag3 ', $this->cache->get($idx)
        );
        $this->assertTrue(
            $this->cache->upsertIndex($idx, array('tag3'), '-')
        );
        $this->assertEquals(
            '+tag1 +tag2 +tag3 -tag3 ', $this->cache->get($idx)
        );
    }

}