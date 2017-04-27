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

class PoolTest extends TestCase
{
    protected $pool = null, $item = null;

    public function setUp()
    {
        $cache = new Cache\Runtime();

        $this->pool = new Pool($cache);
        $this->item = $this->pool->getItem('foo')->set('foo value');
        $this->assertTrue( $this->pool->save($this->item) );
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
        // Create the 'bar' item.
        $item = $this->pool->getItem('bar');

        // Set a the 'bar' item value.
        $item->set('bar value');
        $this->assertNull($item->get());
        $this->pool->save($item);
        $this->assertEquals('bar value', $item->get());

        // Update the 'bar' item value.
        $item->set('new bar value');
        $this->assertNull($item->get());
        $this->pool->save($item);
        $this->assertEquals('new bar value', $item->get());
    }

    public function testGetItems()
    {
        $this->assertSame(array(), $this->pool->getItems());

        $items = $this->pool->getItems(array('non-existant'));
        $this->assertInstanceOf(
            '\Psr\Cache\CacheItemInterface', $items['non-existant']
        );

        $items = $this->pool->getItems( array('foo') );
        $this->assertEquals('foo value', $items['foo']->get());
    }

    public function testSave()
    {
        $item = $this->pool->getItem('baz')->set('foo value');
        $this->assertFalse($item->isHit());
        $this->assertTrue($this->pool->save($item));
    }

    public function testClear()
    {
        $this->assertTrue($this->pool->clear());

        $item = $this->pool->getItem('foo');

        $this->assertFalse($item->isHit());
    }

    public function testDeleteItems()
    {
        $this->assertTrue(
            $this->pool->deleteItems(array('foo', 'non-existant'))
        );

        $this->assertFalse( $this->pool->hasItem('foo') );
    }

    public function testDeleteItem()
    {
        $this->assertTrue( $this->pool->deleteItem('foo') );

        $this->assertTrue( $this->pool->deleteItem('foo') );
        $this->assertFalse( $this->pool->hasItem('foo') );
    }

    /**
     * It MUST NOT be considered an error condition if the specified key does
     * not exist. The post-condition is the same (the key does not exist, or
     * the pool is empty), thus there is no error condition.
     */
    public function testDeleteNonExistant()
    {
        $this->assertTrue($this->pool->deleteItem('non-existant'));
        $this->assertFalse($this->pool->hasItem('non-existant'));
    }

    public function testSaveDeferredAndCommit()
    {
        $item = $this->pool->getItem('foo')->set('foo value');
        $this->assertSame($this->pool, $this->pool->saveDeferred($item));

        $this->assertNull($item->get());

        // get the deferred version
        $this->assertEquals('foo value', $this->pool->getItem('foo'));

        $this->assertTrue($this->pool->commit());
        $this->assertEquals('foo value', $item->get());

        $items = $this->pool->getItems(array('foo', 'bar'));
        $this->assertEquals('foo value', $items['foo']->get());
    }

    /**
     * Regression test for bug GH#13
     *
     * @link https://github.com/frqnck/apix-cache/issues/13
     *       "TaggablePool and Pool overrides prefix_key and prefix_tag options
     *       with hardcoded values"
     * @group regression
     */
    public function testBug13()
    {
        $pool = new Pool(
            new Cache\Runtime(array(), $this->options)
        );
        $adapter = $pool->getCacheAdapter();

        $this->assertSame(
            $this->options['prefix_key'],
            $adapter->getOption('prefix_key')
        );
        $this->assertSame(
            $this->options['prefix_tag'],
            $adapter->getOption('prefix_tag')
        );
    }

    public function testDestructDoesCommit()
    {
        $item = $this->pool->getItem('foo')->set('foo value');
        $this->assertSame($this->pool, $this->pool->saveDeferred($item));
        $this->pool->__destruct();
        $this->assertEquals('foo value', $item->get());

        $item = $this->pool->getItem('foo');
        $this->assertEquals('foo value', $item );
    }

}
