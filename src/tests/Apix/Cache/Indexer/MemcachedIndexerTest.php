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

namespace Apix\Cache\Indexer;

use Apix\TestCase,
    Apix\Cache\Memcached;

class MemcachedIndexerTest extends TestCase
{
    protected $cache, $memcached, $indexer;

    public $indexKey = 'qwertyuiop';

    protected $options = array(
        'prefix_key' => 'unit_test-',
        'prefix_tag' => 'unit_test-',
    );

    public function setUp()
    {
        $this->skipIfMissing('memcached');

        try {
            $this->memcached = new \Memcached;
            $this->memcached->addServer('127.0.0.1', 11211);

            // TODO
            $this->memcached->getStats() or die ("Could not connect");
        } catch (\Exception $e) {
            $this->markTestSkipped( $e->getMessage() );
        }

        $this->cache = new Memcached($this->memcached, $this->options);

        $this->indexer = new MemcachedIndexer($this->cache, $this->indexKey);
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush(true);
            $this->memcached->quit();
            unset($this->cache, $this->memcached, $this->indexer);
        }
    }

    public function testAddOneElement()
    {
        $this->assertTrue( $this->indexer->add('str') );
        $this->assertSame(array('str'), $this->indexer->load());
    }

    public function testAddManyElements()
    {
        $this->assertTrue( $this->indexer->add(array('a', 'b')));
        $this->assertSame(array('a', 'b'), $this->indexer->load());
    }

    public function testRemoveOneElement()
    {
        $this->assertNull($this->indexer->load());

        $this->assertTrue( $this->indexer->add(array('a', 'b')) );
        $this->assertSame(array('a', 'b'), $this->indexer->load());

        $this->assertTrue( $this->indexer->remove('b') );
        $this->assertSame(array('a'), $this->indexer->load());
    }

    public function testRemoveManyElements()
    {
        $this->assertNull($this->indexer->load());

        $this->assertTrue( $this->indexer->add(array('a', 'b', 'c')) );
        $this->assertSame(array('a', 'b', 'c'), $this->indexer->load());

        $this->assertTrue( $this->indexer->Remove(array('a', 'b')) );
        $this->assertSame(array('c'), $this->indexer->load());
    }

    public function testLoadDoesPurge()
    {
        $keys = range(1, 101);
        $this->assertTrue($this->indexer->add('a'));
        $this->assertTrue($this->indexer->Remove($keys));

        $keyStr = implode($keys, ' -');
        $this->assertEquals(
            'a -' . $keyStr . ' ', $this->cache->get($this->indexKey)
        );

        $this->assertEquals(array('a'), $this->indexer->load() );

        $this->assertEquals(
            'a ', $this->cache->get($this->indexKey)
        );
    }

}
