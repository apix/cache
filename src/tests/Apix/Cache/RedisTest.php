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

class RedisTest extends GenericTestCase
{
    const HOST = '127.0.0.1';
    const PORT = 6379;
    const AUTH = NULL;

    protected $cache, $redis;

    protected $options = array(
        'prefix_key' => 'unittest-apix-key:',
        'prefix_tag' => 'unittest-apix-tag:',
        'serializer' => 'php' // null, json, php, igBinary.
    );

    public function setUp()
    {
        $this->skipIfMissing('redis');

        try {
            $this->redis = new \Redis();
            $this->redis->connect(self::HOST, self::PORT);
            if (self::AUTH) {
                $this->redis->auth(self::AUTH);
            }
            $this->redis->ping();
        } catch (\Exception $e) {
            $this->markTestSkipped( $e->getMessage() );
        }

       $this->cache = new Redis($this->redis, $this->options);
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush();
            $this->redis->close();
            unset($this->cache, $this->redis);
        }
    }

    public function testFlushSelected()
    {
        $this->assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );

        $this->redis->set('foo', 'bar');
        $this->assertTrue($this->cache->flush());
        $this->assertFalse($this->cache->flush());
        $this->assertTrue($this->redis->exists('foo'));

        $this->assertNull($this->cache->loadKey('id3'));
        $this->assertNull($this->cache->loadTag('tag1'));
    }

    public function testFlushAll()
    {
        $this->assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );

        $this->redis->set('foo', 'bar');
        $this->assertTrue($this->cache->flush(true)); // always true!
        $this->assertFalse($this->redis->exists('foo'));

        $this->assertNull($this->cache->loadKey('id3'));
        $this->assertNull($this->cache->loadTag('tag1'));
    }

    public function testShortTtlDoesExpunge()
    {
        $this->cache->save('ttl-1', 'ttlId', null, -1);
        $this->assertNull( $this->cache->load('ttlId'));
    }

    public function testSetSerializerToPhp()
    {
        $this->cache->setSerializer('php');
        $this->assertSame(
            \Redis::SERIALIZER_PHP, $this->cache->getSerializer()
        );
    }

    public function testSetSerializerToIgBinary()
    {
        if (defined('Redis::SERIALIZER_IGBINARY')) {
            $this->cache->setSerializer('igBinary');
            $this->assertSame(
                \Redis::SERIALIZER_IGBINARY, $this->cache->getSerializer()
            );
        }
    }

    public function testSetSerializerToNull()
    {
        $this->cache->setSerializer(null);
        $this->assertSame(
            \Redis::SERIALIZER_NONE, $this->cache->getSerializer()
        );
    }

    public function testSetSerializerToJson()
    {
        $this->cache->setSerializer('json');
        $this->assertInstanceOf(
            'Apix\Cache\Serializer\Json', $this->cache->getSerializer()
        );
    }

}
