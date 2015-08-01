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
 * Class RedisTest
 *
 * @package Apix\Cache\tests
 */
class RedisTest extends GenericTestCase
{
    const HOST = '127.0.0.1';
    const PORT = 6379;
    const AUTH = null;

    /**
     * @var \Apix\Cache\Redis
     */
    protected $cache;

    /**
     * @var \Redis
     */
    protected $redis;

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
            self::markTestSkipped( $e->getMessage() );
        }

       $this->cache = new Cache\Redis($this->redis, $this->options);
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
        self::assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );

        $this->redis->set('foo', 'bar');
        self::assertTrue($this->cache->flush());
        self::assertFalse($this->cache->flush());
        self::assertTrue($this->redis->exists('foo'));

        self::assertNull($this->cache->loadKey('id3'));
        self::assertNull($this->cache->loadTag('tag1'));
    }

    public function testFlushAll()
    {
        self::assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );

        $this->redis->set('foo', 'bar');
        self::assertTrue($this->cache->flush(true)); // always true!
        self::assertFalse($this->redis->exists('foo'));

        self::assertNull($this->cache->loadKey('id3'));
        self::assertNull($this->cache->loadTag('tag1'));
    }

    public function testShortTtlDoesExpunge()
    {
        $this->cache->save('ttl-1', 'ttlId', null, -1);
        self::assertNull( $this->cache->load('ttlId'));
    }

    public function testSetSerializerToPhp()
    {
        $this->cache->setSerializer('php');
        self::assertSame(
            \Redis::SERIALIZER_PHP, $this->cache->getSerializer()
        );
    }

    public function testSetSerializerToIgBinary()
    {
        if (defined('Redis::SERIALIZER_IGBINARY')) {
            $this->cache->setSerializer('igBinary');
            self::assertSame(
                \Redis::SERIALIZER_IGBINARY, $this->cache->getSerializer()
            );
        }
    }

    public function testSetSerializerToNull()
    {
        $this->cache->setSerializer(null);
        self::assertSame(
            \Redis::SERIALIZER_NONE, $this->cache->getSerializer()
        );
    }

    public function testSetSerializerToJson()
    {
        $this->cache->setSerializer('json');
        self::assertInstanceOf(
            'Apix\Cache\Serializer\Json', $this->cache->getSerializer()
        );
    }

}
