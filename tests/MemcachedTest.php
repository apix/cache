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
 * Class MemcachedTest
 *
 * @package Apix\Cache\tests
 */
class MemcachedTest extends GenericTestCase
{
    const HOST = '127.0.0.1';
    const PORT = 11211;
    const AUTH = null;

    /**
     * @var \Memcached
     */
    protected $memcached;

    protected $options = array(
        'prefix_key' => 'key_',
        'prefix_tag' => 'tag_',
        'prefix_idx' => 'idx_',
        'serializer' => 'php'
    );

    /**
     * @return \Memcached|null
     */
    public function getMemcached()
    {
        $m = null;

        try {
            $m = new \Memcached();
            $m->addServer(self::HOST, self::PORT);

            $stats = $m->getStats();
            $host = self::HOST.':'.self::PORT;
            if($stats[$host]['pid'] == -1)
                throw new \Exception(
                    sprintf('Unable to reach a memcached server on %s', $host)
                );

        } catch (\Exception $e) {
            self::markTestSkipped( $e->getMessage() );
        }

        return $m;
    }

    public function setUp()
    {
        $this->skipIfMissing('memcached');
        $this->memcached = $this->getMemcached();
        $this->cache = new Cache\Memcached($this->memcached, $this->options);
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush(true);
            $this->memcached->quit();
            unset($this->cache, $this->memcached);
        }
    }

    public function _commonMemcachedData()
    {
        self::assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3', 'tag4'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );
        self::assertSame('data3', $this->cache->loadKey('id3'));
    }

    public function testSaveIsUniqueAndOverwrite()
    {
        self::assertTrue(
            $this->cache->save('bar1', 'foo')
            && $this->cache->save('bar2', 'foo')
        );
        self::assertEquals('bar2', $this->cache->loadKey('foo'));
    }

    public function testFlushNamespace()
    {
        $this->_commonMemcachedData();

        $otherMemcached = $this->getMemcached();
        $otherMemcached->add('foo', 'bar');

        self::assertTrue($this->cache->flush(), "Flush the namespace");

        self::assertEquals('bar', $otherMemcached->get('foo'));

        self::assertNull($this->cache->loadKey('id3'));
        self::assertNull($this->cache->loadTag('tag1'));
    }

    public function testFlushIncrementsTheNamspaceIndex()
    {
        $this->_commonMemcachedData();
        $ns = $this->cache->getOption('prefix_nsp');

        self::assertEquals($ns.'1_', $this->cache->getNamespace());
        self::assertTrue($this->cache->flush(), "Flush the namespace");
        self::assertEquals($ns.'2_', $this->cache->getNamespace());
    }

    public function testFlushAll()
    {
        $this->_commonMemcachedData();

        $this->getMemcached()->add('foo', 'bar');

        self::assertTrue($this->cache->flush(true));
        self::assertNull($this->cache->get('foo'));
        self::assertNull($this->cache->loadKey('id3'));
        self::assertNull($this->cache->loadTag('tag1'));
    }

    public function testDeleteWithTagDisabled()
    {
        $this->cache->setOptions(array('tag_enable' => false));

        self::assertTrue(
            $this->cache->save('data', 'id', array('tag1', 'tag2'))
        );

        self::assertTrue($this->cache->delete('id'));
        self::assertNull($this->cache->loadTag('tag1'));

        $idxKey = $this->cache->mapIdx('id');
        self::assertNull($this->cache->getIndex($idxKey)->load());
    }

    /**
     * @group encours
     */
    public function testDelete()
    {
        $tags = array('tag1', 'tag2');
        self::assertTrue($this->cache->save('data', 'id', $tags));

        self::assertSame(
            array($this->cache->mapKey('id')), $this->cache->loadTag('tag1'),
            'tag1 isset'
        );

        // check the idx isset
        $indexer = $this->cache->getIndex($this->cache->mapIdx('id'));
        self::assertSame( $tags, $indexer->load(), 'idx_id isset');

        self::assertTrue($this->cache->delete('id'));
        self::assertFalse($this->cache->delete('id'));

        self::assertNull($this->cache->loadTag('tag1'), 'tag1 !isset');
        self::assertNull($indexer->load(), 'idx_id !isset');
    }

    public function testIndexing()
    {
        self::assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2', 'tag3'))
        );
        $idx = $this->cache->mapIdx('id1');
        self::assertEquals(
            'tag1 tag2 tag3 ', $this->cache->get($idx)
        );
        self::assertTrue(
            $this->cache->getIndex($idx)->remove(array('tag3'))
            // $this->cache->saveIndex($idx, array('tag3'), '-')
        );
        self::assertEquals(
            'tag1 tag2 tag3 -tag3 ', $this->cache->get($idx)
        );
    }

    public function OFF_testShortTtlDoesExpunge()
    {
        self::assertTrue(
            $this->cache->save('ttl-1', 'ttlId', array('someTags!'), -1)
        );

        // How to forcibly run garbage collection?
        // $this->cache->db->command(array(
        //     'reIndex' => 'cache'
        // ));

        self::assertNull( $this->cache->load('ttlId') );
    }

    public function testSetSerializerToNull()
    {
        $this->cache->setSerializer(null);
        self::assertSame(
            \Memcached::SERIALIZER_PHP, $this->cache->getSerializer()
        );
    }

    public function testSetSerializerToPhp()
    {
        $this->cache->setSerializer('php');
        self::assertSame(
            \Memcached::SERIALIZER_PHP, $this->cache->getSerializer()
        );
    }

    public function testSetSerializerToJson()
    {
        if (defined('\Memcached::SERIALIZER_JSON')
            && \Memcached::HAVE_JSON
        ) {
            $this->cache->setSerializer('json');
            self::assertSame(
                \Memcached::SERIALIZER_JSON, $this->cache->getSerializer()
            );
        }
    }

    public function testSetSerializerToIgbinary()
    {
        if (defined('\Memcached::SERIALIZER_IGBINARY')
            && \Memcached::HAVE_IGBINARY
        ) {
            $this->cache->setSerializer('igBinary');
            self::assertSame(
                \Memcached::SERIALIZER_IGBINARY, $this->cache->getSerializer()
            );
        }
    }

    public function testIncrement()
    {
        $this->options = array('tag_enable' => true);

        $this->cache = new Cache\Memcached($this->memcached, $this->options);

        self::assertNull($this->cache->get('testInc'));
        self::assertEquals(1, $this->cache->increment('testInc'));
        self::assertEquals(1, $this->cache->get('testInc'));
        self::assertEquals(2, $this->cache->increment('testInc'));
        self::assertEquals(2, $this->cache->get('testInc'));
    }

    // public function testIncrementWithBinaryProtocole()
    // {
    //     $m = $this->getMemcached();
    //     $m->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);

    //     $opts = array('tag_enable' => false);
    //     $cache = new Memcached($m, $opts);

    //     self::assertEquals(1, $cache->increment('testInc'));
    //     self::assertEquals(1, $cache->get('testInc'));

    //     self::assertEquals(2, $cache->increment('testInc'));
    //     self::assertEquals(2, $cache->get('testInc'));
    // }
}
