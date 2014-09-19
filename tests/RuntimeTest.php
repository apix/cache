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

}
