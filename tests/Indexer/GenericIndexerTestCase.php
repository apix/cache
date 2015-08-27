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

namespace Apix\Cache\tests\Indexer;

use Apix\Cache\tests\TestCase;

/**
 * Class GenericIndexerTestCase
 *
 * @package Apix\Cache\tests\Indexer
 */
class GenericIndexerTestCase extends TestCase
{
    /**
     * @var \Apix\Cache\Indexer\AbstractIndexer
     */
    protected $indexer;

    /**
     * @var string
     */
    protected $indexKey = 'indexKey';

    public function testAddOneElement()
    {
        $this->assertTrue( $this->indexer->add('foo') );
        $this->assertSame(array('foo'), $this->indexer->load());
    }

    public function testAddManyElements()
    {
        $this->assertTrue( $this->indexer->add('foo') );
        $this->assertTrue( $this->indexer->add(array('bar', 'baz')));
        $this->assertSame(array('foo', 'bar', 'baz'), $this->indexer->load());
    }

    public function testRemoveOneElement()
    {
        $this->assertNull($this->indexer->load());

        $this->assertTrue( $this->indexer->add(array('foo', 'bar')) );
        $this->assertSame(array('foo', 'bar'), $this->indexer->load());

        $this->assertTrue( $this->indexer->remove('bar') );
        $this->assertSame(array('foo'), $this->indexer->load());
    }

    public function testRemoveManyElements()
    {
        $items = array('foo', 'bar', 'baz');
        $this->assertNull($this->indexer->load());

        $this->assertTrue( $this->indexer->add($items) );
        $this->assertSame($items, $this->indexer->load());

        $this->assertTrue( $this->indexer->Remove(array('foo', 'bar')) );
        $this->assertSame(array('baz'), $this->indexer->load());
    }

    public function testGetName()
    {
        $this->assertSame($this->indexKey, $this->indexer->getName());
    }

    public function testGetItems()
    {
        $this->assertSame(array(), $this->indexer->getItems());

        // $items = array('foo', 'bar', 'baz');
        // $this->assertTrue( $this->indexer->add($items) );
        // $this->assertSame($items, $this->indexer->getItems());
    }

}
