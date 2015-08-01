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
 * Class MongoTest
 *
 * @package Apix\Cache\tests
 */
class MongoTest extends GenericTestCase
{
    /**
     * @var \MongoClient
     */
    protected $mongo;

    public function setUp()
    {
        $this->skipIfMissing('mongo');

        try {
            $this->mongo = new \MongoClient();
        } catch (\Exception $e) {
            self::markTestSkipped( $e->getMessage() );
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
        self::assertTrue($this->cache->save('bar1', 'foo'));
        self::assertTrue($this->cache->save('bar2', 'foo'));

        self::assertEquals('bar2', $this->cache->loadKey('foo'));

        self::assertEquals(1, $this->cache->count('foo') );
    }

    public function testFlushCacheOnly()
    {
        $this->cache->save('data1', 'id1', array('tag1', 'tag2'));
        $this->cache->save('data2', 'id2', array('tag2', 'tag3'));
        $this->cache->save('data3', 'id3', array('tag3', 'tag4'));

        $foo = array('foo' => 'bar');
        $this->cache->collection->insert($foo);

        self::assertTrue($this->cache->flush());

        self::assertEquals(
            $foo, $this->cache->collection->findOne(array('foo'=>'bar'))
        );

        self::assertNull($this->cache->loadKey('id3'));
        self::assertNull($this->cache->loadTag('tag1'));
    }

    public function testFlushAll()
    {
        $this->cache->save('data1', 'id1', array('tag1', 'tag2'));
        $this->cache->save('data2', 'id2', array('tag2', 'tag3'));
        $this->cache->save('data3', 'id3', array('tag3', 'tag4'));

        $this->cache->collection->insert(array('key' => 'foobar'));

        self::assertTrue($this->cache->flush(true));
        self::assertNull($this->cache->collection->findOne(array('key')));

        self::assertNull($this->cache->loadKey('id3'));
        self::assertNull($this->cache->loadTag('tag1'));
    }

    public function testShortTtlDoesExpunge()
    {
        self::assertTrue(
            $this->cache->save('ttl-1', 'ttlId', array('someTags!'), -1)
        );

        // How to forcibly run garbage collection?
        // $this->cache->db->command(array(
        //     'reIndex' => 'cache'
        // ));

        self::assertNull( $this->cache->loadKey('ttlId') );
    }

}
