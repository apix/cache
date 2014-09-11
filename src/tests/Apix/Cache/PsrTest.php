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

namespace Apix\Cache;

class PsrTest extends GenericTestCase
{
    protected $cache = null;

    protected $options = array(
        'prefix_key' => 'unittest-apix-key:',
        'prefix_tag' => 'unittest-apix-tag:'
    );

    public function setUp()
    {
        $this->cache = new Psr($this->options);
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush(); // clear()
            unset($this->cache);
        }
    }

    // public function testFlushSelected()
    public function testDeleteItems()
    {
        $this->assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );
        apc_add('foo', 'bar');
        $this->assertTrue($this->cache->flush());
        $this->assertFalse($this->cache->flush());
        $this->assertEquals('bar', apc_fetch('foo'));

        $this->assertNull($this->cache->load('id3'));
        $this->assertNull($this->cache->load('tag1', 'tag'));
    }

    public function testFlushAll()
    {
        $this->assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );

        apc_add('foo', 'bar');
        $this->assertTrue($this->cache->flush(true)); // always true!

        $this->assertEquals(false, apc_fetch('foo'));

        $this->assertNull($this->cache->load('id3'));
        $this->assertNull($this->cache->load('tag1', 'tag'));
    }

    /**
     * -runTestsInSeparateProcesses
     * -runInSeparateProcesses
     * -preserveGlobalState enable
     * -depends test
     */
    public function testShortTtlDoesExpunge()
    {
        $this->markTestSkipped(
            "APC will only expunged its cache on the next request which makes "
            . "this specific unit untestable!?... :-("
        );
        $this->cache->save('ttl-1', 'ttlId', null, -1);
        // $this->assertSame('ttl-1', apc_fetch($this->cache->mapKey('ttlId')));
        // $this->assertSame('ttl-1', $this->cache->load('ttlId'));
        $this->assertNull( $this->cache->load('ttlId'), "Should be null");
    }

    public function testGetInternalInfos()
    {
        $this->cache->save('someData', 'someId', null, 69);
        $infos = $this->cache->getInternalInfos('someId');
        $this->assertSame(69, $infos['ttl']);
    }

    public function testGetInternalInfosReturnFalseWhenNonExistant()
    {
        $this->assertFalse(
            $this->cache->getInternalInfos('non-existant')
        );
    }

}
