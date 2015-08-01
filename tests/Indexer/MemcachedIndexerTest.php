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

namespace Apix\Cache\tests\Indexer;

use Apix\Cache;
use Apix\Cache\Indexer;

/**
 * Class MemcachedIndexerTest
 *
 * @package Apix\Cache\tests\Indexer
 */
class MemcachedIndexerTest extends GenericIndexerTestCase
{
    const HOST = '127.0.0.1';
    const PORT = 11211;
    const AUTH = null;

    /**
     * @var \Apix\Cache\Memcached
     */
    protected $cache;

    /**
     * @var \Memcached
     */
    protected $memcached;

    /**
     * @var \Apix\Cache\Indexer\MemcachedIndexer
     */
    protected $indexer;

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
            $host  = self::HOST.':'.self::PORT;

            if ($stats[$host]['pid'] == -1) {
                throw new \Exception(
                    sprintf('Unable to reach a memcached server on %s', $host)
                );
            }
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

        $this->indexer = new Indexer\MemcachedIndexer($this->indexKey, $this->cache);
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush(true);
            $this->memcached->quit();
            unset($this->cache, $this->memcached, $this->indexer);
        }
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
