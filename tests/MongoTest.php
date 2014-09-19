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

class MongoTest extends GenericTestCase
{
    protected $cache, $mongo;

    public function setUp()
    {
        $this->skipIfMissing('mongo');

        try {
            $this->mongo = new \MongoClient();
        } catch (\Exception $e) {
            $this->markTestSkipped( $e->getMessage() );
        }

       $this->cache = new Cache\Mongo($this->mongo, $this->options);
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush();
            $this->mongo->close();
            unset($this->cache, $this->mongo);
        }
    }

    public function testSaveIsUnique()
    {
        $this->assertTrue($this->cache->save('bar1', 'foo'));
        $this->assertTrue($this->cache->save('bar2', 'foo'));

        $this->assertEquals('bar2', $this->cache->loadKey('foo'));

        $this->assertEquals(1, $this->cache->count('foo') );
    }

    public function testFlushCacheOnly()
    {
        $this->cache->save('data1', 'id1', array('tag1', 'tag2'));
        $this->cache->save('data2', 'id2', array('tag2', 'tag3'));
        $this->cache->save('data3', 'id3', array('tag3', 'tag4'));

        $foo = array('foo' => 'bar');
        $this->cache->collection->insert($foo);

        $this->assertTrue($this->cache->flush());

        $this->assertEquals(
            $foo, $this->cache->collection->findOne(array('foo'=>'bar'))
        );

        $this->assertNull($this->cache->loadKey('id3'));
        $this->assertNull($this->cache->loadTag('tag1'));
    }

    public function testFlushAll()
    {
        $this->cache->save('data1', 'id1', array('tag1', 'tag2'));
        $this->cache->save('data2', 'id2', array('tag2', 'tag3'));
        $this->cache->save('data3', 'id3', array('tag3', 'tag4'));

        $this->cache->collection->insert(array('key' => 'foobar'));

        $this->assertTrue($this->cache->flush(true));
        $this->assertNull($this->cache->collection->findOne(array('key')));

        $this->assertNull($this->cache->loadKey('id3'));
        $this->assertNull($this->cache->loadTag('tag1'));
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

        $this->assertNull( $this->cache->loadKey('ttlId') );
    }

}
