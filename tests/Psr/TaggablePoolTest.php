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

namespace Apix\Cache\tests\Psr;

use Apix\Cache,
    Apix\Cache\Psr\TaggablePool;

class TaggablePoolTest extends PoolTest
{
    protected $cache = null, $pool = null, $item = null;

    public function setUp()
    {
        $this->cache = new Cache\Runtime();

        $this->pool = new TaggablePool($this->cache);
        $this->item = $this->pool->getItem('foo')->set('foo value');
        $this->pool->save($this->item);

        $this->assertTrue($this->item->isHit());
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush();
            unset($this->cache);
        }
        unset($this->pool, $this->item);
    }

    public function testGetItemsByTagIsEmptyArrayByDefault()
    {
        $this->assertEquals(array(), $this->pool->getItemsByTag('non-existant'));

        $this->assertSame($this->pool, $this->pool->save($this->item));
        $this->assertEquals(array(), $this->pool->getItemsByTag('non-existant'));
    }

    public function testGetItemsByTag()
    {
        $tags = array('fooTag', 'barTag');
        $this->assertSame($this->item, $this->item->setTags($tags));
        $this->assertSame($this->pool, $this->pool->save($this->item));

        $items = $this->pool->getItemsByTag('fooTag');

        $this->assertInstanceOf('Apix\Cache\Psr\TaggableItem', $items['foo']);
        $this->assertSame('foo', $items['foo']->getkey());
        $this->assertSame('foo value', $items['foo']->get());

        $this->assertSame($tags, $items['foo']->getTags());
    }

    public function testClearByTags()
    {
        $this->assertFalse($this->pool->clearByTags( array('non-existant') ));

        $tags = array('fooTag', 'barTag');
        $this->assertSame($this->item, $this->item->setTags($tags));
        $this->assertSame($this->pool, $this->pool->save($this->item));

        $this->assertTrue($this->pool->clearByTags( array('fooTag') ));
    }

}
