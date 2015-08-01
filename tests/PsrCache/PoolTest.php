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

use Apix\Cache\tests\TestCase;

use Apix\Cache,
    Apix\Cache\PsrCache\Pool;

/**
 * Class PoolTest
 *
 * @package Apix\Cache\tests\PsrCache
 */
class PoolTest extends TestCase
{
    /**
     * @var \Apix\Cache\PsrCache\Pool
     */
    protected $pool = null;

    /**
     * @var \Apix\Cache\PsrCache\Item
     */
    protected $item = null;

    public function setUp()
    {
        $cache = new Cache\Runtime();

        $this->pool = new Pool($cache);
        $this->item = $this->pool->getItem('foo')->set('foo value');
        $this->pool->save($this->item);

        self::assertTrue($this->item->isHit());
    }

    public function tearDown()
    {
        unset($this->pool, $this->item);
    }

    public function testGetItemWithNonExistantKey()
    {
        $item = $this->pool->getItem('non-existant');
        self::assertInstanceOf('Psr\Cache\CacheItemInterface', $item);
    }

    /**
     * @expectedException \Apix\Cache\PsrCache\InvalidArgumentException
     */
    public function testGetItemThrowsException()
    {
        $item = $this->pool->getItem('{}');
    }

    public function testBasicSetAndGetOperations()
    {
        $item = $this->pool->getItem('bar');
        $item->set('bar value');
        self::assertNull($item->get());

        $this->pool->save($item);
        self::assertEquals('bar value', $item->get());

        self::assertEquals($item, $this->pool->getItem('bar'), "mm");

        // Update an existing item.
        $item->set('new bar value');
        self::assertNull($item->get());

        $this->pool->save($item);
        // array_map(array($this->pool, 'save'), array($item));

        self::assertEquals('new bar value', $item->get());
    }

    public function testGetItems()
    {
        self::assertSame(array(), $this->pool->getItems());

        $items = $this->pool->getItems(array('non-existant'));
        self::assertInstanceOf(
            '\Psr\Cache\CacheItemInterface', $items['non-existant']
        );

        $items = $this->pool->getItems(array('foo'));
        self::assertEquals('foo value', $items['foo']->get());
    }

    public function testSave()
    {
        $item = $this->pool->getItem('baz')->set('foo value');
        self::assertFalse($item->isHit());
        self::assertSame($this->pool, $this->pool->save($item));
        self::assertTrue($item->isHit());
    }

    public function testClear()
    {
        self::assertTrue($this->pool->clear());

        $item = $this->pool->getItem('foo');

        self::assertFalse($item->isHit());
        self::assertFalse($item->exists());
    }

    public function testDeleteItems()
    {
        self::assertSame($this->pool,
            $this->pool->deleteItems(array('foo', 'non-existant'))
        );

        $item = $this->pool->getItem('foo');
        self::assertFalse($item->isHit());
        self::assertFalse($item->exists());
    }

    public function testSaveDeferredAndCommit()
    {
        $item = $this->pool->getItem('foo')->set('foo value');
        self::assertSame($this->pool, $this->pool->saveDeferred($item));
        self::assertNull($item->get());
        self::assertTrue($this->pool->commit());
        self::assertEquals('foo value', $item->get());

        $items = $this->pool->getItems(array('foo', 'bar'));
        self::assertEquals('foo value', $items['foo']->get());
        // self::assertEquals($item, $items['foo']);
    }
}
