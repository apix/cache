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

class FactoryTest extends TestCase
{
    protected $cache = null;

    public function setUp()
    {
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush();
            unset($this->cache);
        }
    }

    /**
     * @expectedException Apix\Cache\PsrCache\InvalidArgumentException
     */
    public function testPoolWithUnsurportedObjectThrowsException()
    {
        Cache\Factory::getPool( new \StdClass() );
    }

    public function testPoolFromCacheClientObject()
    {
        $adapter = new \ArrayObject();
        $pool = Cache\Factory::getPool($adapter, $this->options);
        $this->assertInstanceOf('\Apix\Cache\PsrCache\Pool', $pool);
        $this->assertInstanceOf('\Apix\Cache\Runtime', $pool->getCacheAdapter());
    }

    /**
     * @expectedException Apix\Cache\PsrCache\InvalidArgumentException
     */
    public function testPoolWithUnsurportedStringThrowsException()
    {
        Cache\Factory::getPool('non-existant', $this->options);
    }

    public function testPoolFromString()
    {
        $pool = Cache\Factory::getPool('Runtime', $this->options);
        $this->assertInstanceOf('\Apix\Cache\PsrCache\Pool', $pool);

        $pool = Cache\Factory::getPool('Array', $this->options);
        $this->assertInstanceOf('\Apix\Cache\PsrCache\Pool', $pool);
        $this->assertInstanceOf('\Apix\Cache\Runtime', $pool->getCacheAdapter());
    }

    public function testPoolFromStringMixedCase()
    {
        $pool = Cache\Factory::getPool('arRay', $this->options);
        $this->assertInstanceOf('\Apix\Cache\PsrCache\Pool', $pool);
        $this->assertInstanceOf('\Apix\Cache\Runtime', $pool->getCacheAdapter());
    }

    public function testPoolFromArray()
    {
        $pool = Cache\Factory::getPool(array(), $this->options);
        $this->assertInstanceOf('\Apix\Cache\PsrCache\Pool', $pool);
        $this->assertInstanceOf('\Apix\Cache\Runtime', $pool->getCacheAdapter());
    }

    public function testTaggablePoolFromString()
    {
        $pool = Cache\Factory::getPool('ArrayObject', $this->options, true);
        $this->assertInstanceOf('\Apix\Cache\PsrCache\TaggablePool', $pool);
        $this->assertInstanceOf('\Apix\Cache\Runtime', $pool->getCacheAdapter());
    }

    public function testGetTaggablePool()
    {
        $pool = Cache\Factory::getTaggablePool(array(), $this->options, true);
        $this->assertInstanceOf('\Apix\Cache\PsrCache\TaggablePool', $pool);
        $this->assertInstanceOf('\Apix\Cache\Runtime', $pool->getCacheAdapter());
    }

    /**
     * @expectedException \Apix\Cache\Exception
     */
    public function testGetPoolThrowsApixCacheException()
    {
        $adapter = new Cache\Runtime(new \StdClass, $this->options);
        Cache\Factory::getPool($adapter);
    }

    public function providerAsPerExample()
    {
        return array(
            'pdo client' => array(new \PDO('sqlite::memory:'), 'Pdo\Sqlite'),
            'files' => array('files', 'Files'),
            'Files adapter' => array(new Cache\Files(), 'Files'),
            'directory' => array('Directory', 'Directory'),
            'Directory adapter' => array(new Cache\Directory(), 'Directory'),
            'apc' => array('apc', 'Apc'),
            'runtime' => array('runtime', 'Runtime'),
            'array' => array(array(), 'Runtime'),
        );
    }

    /**
     * Regression test for bug GH#12
     *
     * @link https://github.com/frqnck/apix-cache/issues/12
     *       "'Files' and 'Directory' are not listed as clients in the Factory"
     * @group regression
     * @dataProvider providerAsPerExample
     */
    public function testBugFactoryExample($backend, $expected)
    {
        $pool = Cache\Factory::getPool( $backend );
        $this->assertInstanceOf(
            '\Apix\Cache\\' . $expected,
            $pool->getCacheAdapter()
        );
    }

    /**
     * Regression test for bug GH#13
     *
     * @link https://github.com/frqnck/apix-cache/issues/13
     *       "TaggablePool and Pool overrides prefix_key and prefix_tag options
     *       with hardcoded values"
     * @group regression
     * @see PsrCache\PoolTest\testBug13()
     * @see PsrCache\TaggablePoolTest\testBug13()
     */
    public function testBug13()
    {
        $pool = Cache\Factory::getPool(array(), $this->options, true);
        $this->assertSame(
            $this->options['prefix_key'],
            $pool->getCacheAdapter()->getOption('prefix_key')
        );
    }

}
