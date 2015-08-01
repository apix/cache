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

namespace Apix\Cache\tests\PsrCache;

use Apix\Cache,
    Apix\Cache\PsrCache\TaggablePool;

/**
 * Class TaggablePoolTest
 *
 * @package Apix\Cache\tests\PsrCache
 */
class TaggablePoolTest extends PoolTest
{
    /**
     * @var \Apix\Cache\Runtime
     */
    protected $cache = null;

    /**
     * @var \Apix\Cache\PsrCache\TaggablePool
     */
    protected $pool = null;

    /**
     * @var \Apix\Cache\PsrCache\TaggableItem
     */
    protected $item = null;

    public function setUp()
    {
        $this->cache = new Cache\Runtime();

        $this->pool = new TaggablePool($this->cache);
        $this->item = $this->pool->getItem('foo')->set('foo value');
        $this->pool->save($this->item);

        self::assertTrue($this->item->isHit());
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
        self::assertEquals(array(), $this->pool->getItemsByTag('non-existant'));

        self::assertSame($this->pool, $this->pool->save($this->item));
        self::assertEquals(array(), $this->pool->getItemsByTag('non-existant'));
    }

    public function testGetItemsByTag()
    {
        $tags = array('fooTag', 'barTag');
        self::assertSame($this->item, $this->item->setTags($tags));
        self::assertSame($this->pool, $this->pool->save($this->item));

        $items = $this->pool->getItemsByTag('fooTag');

        self::assertInstanceOf('Apix\Cache\PsrCache\TaggableItem', $items['foo']);
        self::assertSame('foo', $items['foo']->getkey());
        self::assertSame('foo value', $items['foo']->get());

        self::assertSame($tags, $items['foo']->getTags());
    }

    public function testClearByTags()
    {
        self::assertFalse($this->pool->clearByTags( array('non-existant') ));

        $tags = array('fooTag', 'barTag');
        self::assertSame($this->item, $this->item->setTags($tags));
        self::assertSame($this->pool, $this->pool->save($this->item));

        self::assertTrue($this->pool->clearByTags( array('fooTag') ));
    }

}
