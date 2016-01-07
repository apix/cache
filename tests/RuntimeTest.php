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
 * RuntimeTest
 *
 * @package Apix\Cache
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class RuntimeTest extends GenericTestCase
{
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

    public function testWithAPopulatedArray()
    {
        $options = array('prefix_key' => null, 'prefix_tag' => null);
        $pre_cached_items = array(
            'foo' => array(
                'data' => 'foo value', 'tags' => array('tag'), 'expire' => null
            )
        );

        $this->cache = new Cache\Runtime($pre_cached_items, $options);
        $this->assertSame('foo value', $this->cache->loadKey('foo'));
        $this->assertSame(array('foo'), $this->cache->loadTag('tag'));
    }

    // AbstratcCache

    public function testGetOption()
    {
        $this->assertSame($this->options['prefix_key'], $this->cache->getOption('prefix_key') );
    }

    public function testRemovePrefix()
    {
        $this->assertSame(
            '-str', $this->cache->removePrefix('prefix-str', 'prefix')
        );
    }

    public function testRemovePrefixKey()
    {
        $this->assertSame(
            'foo',
            $this->cache->removePrefixKey($this->options['prefix_key'] . 'foo')
        );
        $this->assertSame(
            'not-prefixed-key',
            $this->cache->removePrefixKey('not-prefixed-key')
        );
    }
    public function testRemovePrefixTag()
    {
        $this->assertSame(
            'foo',
            $this->cache->removePrefixTag($this->options['prefix_tag'] . 'foo')
        );
        $this->assertSame(
            'not-prefixed-key',
            $this->cache->removePrefixTag('not-prefixed-key')
        );
    }

}
