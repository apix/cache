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
 * Class FactoryTest
 *
 * @package Apix\Cache\tests
 */
class FactoryTest extends TestCase
{
    /*
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
    /**/

    /**
     * @expectedException \Apix\Cache\PsrCache\InvalidArgumentException
     */
    public function testPoolWithUnsurportedObjectThrowsException()
    {
        Cache\Factory::getPool( new \StdClass() );
    }

    public function testPoolFromCacheClientObject()
    {
        $adapter = new \ArrayObject();
        $pool = Cache\Factory::getPool($adapter, $this->options);
        self::assertInstanceOf('\Apix\Cache\PsrCache\Pool', $pool);
        self::assertInstanceOf('\Apix\Cache\Runtime', $pool->getCacheAdapter());
    }

    /**
     * @expectedException \Apix\Cache\PsrCache\InvalidArgumentException
     */
    public function testPoolWithUnsurportedStringThrowsException()
    {
        Cache\Factory::getPool('non-existant', $this->options);
    }

    public function testPoolFromString()
    {
        $pool = Cache\Factory::getPool('Runtime', $this->options);
        self::assertInstanceOf('\Apix\Cache\PsrCache\Pool', $pool);

        $pool = Cache\Factory::getPool('Array', $this->options);
        self::assertInstanceOf('\Apix\Cache\PsrCache\Pool', $pool);
        self::assertInstanceOf('\Apix\Cache\Runtime', $pool->getCacheAdapter());
    }

    public function testPoolFromStringMixedCase()
    {
        $pool = Cache\Factory::getPool('arRay', $this->options);
        self::assertInstanceOf('\Apix\Cache\PsrCache\Pool', $pool);
        self::assertInstanceOf('\Apix\Cache\Runtime', $pool->getCacheAdapter());
    }

    public function testPoolFromArray()
    {
        $pool = Cache\Factory::getPool(array(), $this->options);
        self::assertInstanceOf('\Apix\Cache\PsrCache\Pool', $pool);
        self::assertInstanceOf('\Apix\Cache\Runtime', $pool->getCacheAdapter());
    }

    public function testTaggablePoolFromString()
    {
        $pool = Cache\Factory::getPool('ArrayObject', $this->options, true);
        self::assertInstanceOf('\Apix\Cache\PsrCache\TaggablePool', $pool);
        self::assertInstanceOf('\Apix\Cache\Runtime', $pool->getCacheAdapter());
    }

    public function testGetTaggablePool()
    {
        $pool = Cache\Factory::getTaggablePool(array(), $this->options, true);
        self::assertInstanceOf('\Apix\Cache\PsrCache\TaggablePool', $pool);
        self::assertInstanceOf('\Apix\Cache\Runtime', $pool->getCacheAdapter());
    }

    /**
     * @expectedException \Apix\Cache\Exception
     */
    public function testGetPoolThrowsApixCacheException()
    {
        $adapter = new Cache\Runtime(new \StdClass, $this->options);
        Cache\Factory::getPool($adapter);
    }

}
