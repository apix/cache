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

        $this->assertTrue($this->item->isHit());
    }

    public function tearDown()
    {
        unset($this->pool, $this->item);
    }

    public function testGetItemWithNonExistantKey()
    {
        $item = $this->pool->getItem('non-existant');
        $this->assertInstanceOf('Psr\Cache\CacheItemInterface', $item);
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
        $this->assertNull($item->get());

        $this->pool->save($item);
        $this->assertEquals('bar value', $item->get());

        $this->assertEquals($item, $this->pool->getItem('bar'), "mm");

        // Update an existing item.
        $item->set('new bar value');
        $this->assertNull($item->get());

        $this->pool->save($item);
        // array_map(array($this->pool, 'save'), array($item));

        $this->assertEquals('new bar value', $item->get());
    }

    public function testGetItems()
    {
        $this->assertSame(array(), $this->pool->getItems());

        $items = $this->pool->getItems(array('non-existant'));
        $this->assertInstanceOf(
            '\Psr\Cache\CacheItemInterface', $items['non-existant']
        );

        $items = $this->pool->getItems(array('foo'));
        $this->assertEquals('foo value', $items['foo']->get());
    }

    public function testSave()
    {
        $item = $this->pool->getItem('baz')->set('foo value');
        $this->assertFalse($item->isHit());
        $this->assertSame($this->pool, $this->pool->save($item));
        $this->assertTrue($item->isHit());
    }

    public function testClear()
    {
        $this->assertTrue($this->pool->clear());

        $item = $this->pool->getItem('foo');

        $this->assertFalse($item->isHit());
        $this->assertFalse($item->exists());
    }

    public function testDeleteItems()
    {
        $this->assertSame($this->pool,
            $this->pool->deleteItems(array('foo', 'non-existant'))
        );

        $item = $this->pool->getItem('foo');
        $this->assertFalse($item->isHit());
        $this->assertFalse($item->exists());
    }

    public function testSaveDeferredAndCommit()
    {
        $item = $this->pool->getItem('foo')->set('foo value');
        $this->assertSame($this->pool, $this->pool->saveDeferred($item));
        $this->assertNull($item->get());
        $this->assertTrue($this->pool->commit());
        $this->assertEquals('foo value', $item->get());

        $items = $this->pool->getItems(array('foo', 'bar'));
        $this->assertEquals('foo value', $items['foo']->get());
        // $this->assertEquals($item, $items['foo']);
    }
}
