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

class MemcachedTest extends GenericTestCase
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
        return $this->assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3', 'tag4'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );
        $this->assertSame('data3', $this->cache->loadKey('id3'));
    }

    public function testSaveIsUniqueAndOverwrite()
    {
        $this->assertTrue(
            $this->cache->save('bar1', 'foo')
            && $this->cache->save('bar2', 'foo')
        );
        $this->assertEquals('bar2', $this->cache->loadKey('foo'));
    }

    public function testFlushNamespace()
    {
        $this->_commonMemcachedData();

        $otherMemcached = $this->getMemcached();
        $otherMemcached->add('foo', 'bar');

        $this->assertTrue($this->cache->flush(), "Flush the namespace");

        $this->assertEquals('bar', $otherMemcached->get('foo'));

        $this->assertNull($this->cache->loadKey('id3'));
        $this->assertNull($this->cache->loadTag('tag1'));
    }

    public function testFlushIncrementsTheNamspaceIndex()
    {
        $this->_commonMemcachedData();
        $ns = $this->cache->getOption('prefix_nsp');

        $this->assertEquals($ns.'1_', $this->cache->getNamespace());
        $this->assertTrue($this->cache->flush(), "Flush the namespace");
        $this->assertEquals($ns.'2_', $this->cache->getNamespace());
    }

    /**
     * @group encours
     */
    public function testFlushPreserveTheNamspaceIndex()
    {
        $this->_commonMemcachedData();
        $this->assertTrue($this->cache->flush());

        $key = $this->cache->getNamespace();
        var_dump($key);

        $idx = $this->cache->mapIdx($key);
        var_dump($idx);

        $stuff = $this->cache->getIndex($idx)->load();
        // $stuff = $this->cache->loadIndex($idx);

        var_dump($stuff);

        /*
            $_key = $this->mapKey($key);
            $items = array( $_key );

            if ($this->options['tag_enable']) {

                $idx = $this->mapIdx($key);

                // mark key for deletion in tags
                $tags = $this->loadIndex($idx);
                if (is_array($tags)) {
                    foreach ($tags as $tag) {
                        $this->saveIndex($this->mapTag($tag), $_key, '-');
                    }
                    $items[] = $idx;
                }
            }
        */
    }

    public function testFlushAll()
    {
        $this->_commonMemcachedData();

        $this->getMemcached()->add('foo', 'bar');

        $this->assertTrue($this->cache->flush(true));
        $this->assertNull($this->cache->get('foo'));
        $this->assertNull($this->cache->loadKey('id3'));
        $this->assertNull($this->cache->loadTag('tag1'));
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
            $this->cache->getIndex($idx)->remove(array('tag3'))
            // $this->cache->saveIndex($idx, array('tag3'), '-')
        );
        $this->assertEquals(
            '+tag1 +tag2 +tag3 -tag3 ', $this->cache->get($idx)
        );
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

    public function testSetSerializerToNull()
    {
        $this->cache->setSerializer(null);
        $this->assertSame(
            \Memcached::SERIALIZER_PHP, $this->cache->getSerializer()
        );
    }

    public function testSetSerializerToPhp()
    {
        $this->cache->setSerializer('php');
        $this->assertSame(
            \Memcached::SERIALIZER_PHP, $this->cache->getSerializer()
        );
    }

    public function testSetSerializerToJson()
    {
        $this->markTestSkipped('json issue?!');
        if (defined('\Memcached::SERIALIZER_JSON')) {
            $this->cache->setSerializer('json');
            $this->assertSame(
                \Memcached::SERIALIZER_JSON, $this->cache->getSerializer()
            );
        }
    }

    public function testSetSerializerToIgbinary()
    {
        if (defined('\Memcached::SERIALIZER_IGBINARY')
            && function_exists('igbinary_serialize')) {
            $this->cache->setSerializer('igBinary');
            $this->assertSame(
                \Memcached::SERIALIZER_IGBINARY, $this->cache->getSerializer()
            );
        }
    }

}
