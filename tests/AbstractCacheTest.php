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
 * Class AbstractCacheTest
 *
 * @package Apix\Cache\tests
 */
class AbstractCacheTest extends TestCase
{
    /**
     * @var \Apix\Cache\Runtime
     */
    protected $cache = null;

    public function setUp()
    {
        $this->cache = new Cache\Runtime(null, $this->options);
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush();
            unset($this->cache);
        }
    }

    public function testGetOption()
    {
        self::assertSame(
            $this->options['prefix_key'],
            $this->cache->getOption('prefix_key')
        );
    }

    /**
     * @expectedException \Apix\Cache\PsrCache\InvalidArgumentException
     */
    public function testGetOptionThrowAnInvalidArgumentException()
    {
        $this->cache->getOption('key');
    }

    public function testSetOption()
    {
        $this->cache->setOption('prefix_key', 'foo');

        self::assertSame('foo', $this->cache->getOption('prefix_key'));
    }
}
