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
        self::assertTrue( $this->indexer->add('foo') );
        self::assertSame(array('foo'), $this->indexer->load());
    }

    public function testAddManyElements()
    {
        self::assertTrue( $this->indexer->add('foo') );
        self::assertTrue( $this->indexer->add(array('bar', 'baz')));
        self::assertSame(array('foo', 'bar', 'baz'), $this->indexer->load());
    }

    public function testRemoveOneElement()
    {
        self::assertNull($this->indexer->load());

        self::assertTrue( $this->indexer->add(array('foo', 'bar')) );
        self::assertSame(array('foo', 'bar'), $this->indexer->load());

        self::assertTrue( $this->indexer->remove('bar') );
        self::assertSame(array('foo'), $this->indexer->load());
    }

    public function testRemoveManyElements()
    {
        $items = array('foo', 'bar', 'baz');
        self::assertNull($this->indexer->load());

        self::assertTrue( $this->indexer->add($items) );
        self::assertSame($items, $this->indexer->load());

        self::assertTrue( $this->indexer->Remove(array('foo', 'bar')) );
        self::assertSame(array('baz'), $this->indexer->load());
    }

    public function testGetName()
    {
        self::assertSame($this->indexKey, $this->indexer->getName());
    }

    public function testGetItems()
    {
        self::assertSame(array(), $this->indexer->getItems());

        // $items = array('foo', 'bar', 'baz');
        // self::assertTrue( $this->indexer->add($items) );
        // self::assertSame($items, $this->indexer->getItems());
    }

}
