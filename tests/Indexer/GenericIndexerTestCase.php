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

use Apix\Cache\Tests\TestCase;

class GenericIndexerTestCase extends TestCase
{

    public function testAddOneElement()
    {
        $this->assertTrue( $this->indexer->add('str') );
        $this->assertSame(array('str'), $this->indexer->load());
    }

    public function testAddManyElements()
    {
        $this->assertTrue( $this->indexer->add(array('a', 'b')));
        $this->assertSame(array('a', 'b'), $this->indexer->load());
    }

    public function testRemoveOneElement()
    {
        $this->assertNull($this->indexer->load());

        $this->assertTrue( $this->indexer->add(array('a', 'b')) );
        $this->assertSame(array('a', 'b'), $this->indexer->load());

        $this->assertTrue( $this->indexer->remove('b') );
        $this->assertSame(array('a'), $this->indexer->load());
    }

    public function testRemoveManyElements()
    {
        $this->assertNull($this->indexer->load());

        $this->assertTrue( $this->indexer->add(array('a', 'b', 'c')) );
        $this->assertSame(array('a', 'b', 'c'), $this->indexer->load());

        $this->assertTrue( $this->indexer->Remove(array('a', 'b')) );
        $this->assertSame(array('c'), $this->indexer->load());
    }

}
