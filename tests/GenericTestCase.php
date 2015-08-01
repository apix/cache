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

/**
 * Class GenericTestCase
 *
 * @package Apix\Cache\tests
 */
class GenericTestCase extends TestCase
{
    /**
     * @var \Apix\Cache\AbstractCache
     */
    protected $cache = null;

    public function testLoadKeyReturnsNullWhenInexistant()
    {
        $this->assertNull($this->cache->loadKey('id'));
    }

    public function testLoadTagReturnsNullWhenInexistant()
    {
        $this->assertNull($this->cache->loadTag('id'));
    }

    public function testSaveAndLoadWithString()
    {
        $this->assertTrue($this->cache->save('data', 'id'));
        $this->assertEquals('data', $this->cache->loadKey('id'));
        $this->assertEquals('data', $this->cache->load('id'));
    }

    public function testSaveAndLoadWithArray()
    {
        $data = array('foo' => 'bar');
        $this->assertTrue($this->cache->save($data, 'id'));
        $this->assertEquals($data, $this->cache->loadKey('id'));
        $this->assertEquals($data, $this->cache->load('id'));
    }

    public function testSaveAndLoadWithObject()
    {
        $data = new \stdClass();
        $this->assertTrue($this->cache->save($data, 'id'));
        $this->assertEquals($data, $this->cache->loadKey('id'));
        $this->assertEquals($data, $this->cache->load('id'));
    }

    public function testDeleteInexistantReturnsFalse()
    {
        $this->assertFalse($this->cache->delete('Inexistant'));
    }

    public function testDelete()
    {
        $this->assertTrue(
            $this->cache->save('foo value', 'foo')
            && $this->cache->save('bar value', 'bar')
        );

        $this->assertTrue($this->cache->delete('foo'));
        $this->assertFalse($this->cache->delete('foo'));

        $this->assertNull($this->cache->loadKey('foo'));
    }

    public function testTllFromSave()
    {
        $this->assertFalse($this->cache->getTtl('non-existant'));

        $this->assertTrue($this->cache->save('data', 'id'));
        $this->assertEquals(0, $this->cache->getTtl('id'),
            "Expiration should be set to 0 (for ever) by default."
        );

        $this->assertTrue($this->cache->save('data', 'id', null, 3600));
        $this->assertEquals(3600, $this->cache->getTtl('id'), null, 5);
        // $this->assertLessThanOrEqual(3600, $this->cache->getTtl('id'));
    }

    public function testTllFromLoad()
    {
        $this->assertFalse($this->cache->getTtl('non-existant'));

        $this->assertNull($this->cache->load('id'));
        $this->assertEquals(0, $this->cache->getTtl('id'),
            "Expiration should be set to 0 (for ever) by default."
        );

        $this->assertTrue($this->cache->save('data', 'id', null, 3600));
        $this->assertEquals('data', $this->cache->load('id'));
        $this->assertEquals(3600, $this->cache->getTtl('id'), null, 5);
        // $this->assertLessThanOrEqual(3600, $this->cache->getTtl('id'));
    }

    ////
    // The tests belowe are tags related
    ////

    public function testSaveWithTagDisabled()
    {
        $this->cache->setOptions(array('tag_enable' => false));

        $this->assertTrue(
            $this->cache->save('data', 'id', array('tag1', 'tag2'))
        );
        $this->assertNull($this->cache->loadTag('tag1'));
    }

    /**
     * @group testme
     */
    public function testSaveWithJustOneSingularTag()
    {
        $this->assertTrue($this->cache->save('data', 'id', array('tag')));
        $ids = array($this->cache->mapKey('id'));

        $this->assertEquals($ids, $this->cache->loadTag('tag'));
        $this->assertEquals($ids, $this->cache->load('tag', 'tag'));
    }

    public function testSaveManyTags()
    {
        $this->assertTrue(
            $this->cache->save('data', 'id', array('tag1', 'tag2'))
        );
        $ids = array($this->cache->mapKey('id'));

        $this->assertEquals($ids, $this->cache->loadTag('tag2'));
        $this->assertEquals($ids, $this->cache->load('tag2', 'tag'));
    }

    public function testSaveWithOverlappingTags()
    {
        $this->assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
        );

        $ids = $this->cache->loadTag('tag2');
        $this->assertTrue(count($ids) == 2);
        $this->assertContains($this->cache->mapKey('id1'), $ids);
        $this->assertContains($this->cache->mapKey('id2'), $ids);
    }

    public function testClean()
    {
        $this->assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3', 'tag4'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );

        $this->assertTrue($this->cache->clean(array('tag4')));
        $this->assertFalse($this->cache->clean(array('tag4')));
        $this->assertFalse($this->cache->clean(array('non-existant')));

        $this->assertNull($this->cache->loadKey('id2'));
        $this->assertNull($this->cache->loadKey('id3'));
        $this->assertNull($this->cache->loadTag('tag4'));
        $this->assertEquals('data1', $this->cache->loadKey('id1'));
    }

    public function testDeleteAlsoRemoveTags()
    {
        $this->assertTrue(
            $this->cache->save('foo value', 'foo', array('foo_tag', 'all_tag'))
            && $this->cache->save('bar value', 'bar', array('bar_tag', 'all_tag'))
        );

        $this->assertContains(
            $this->cache->mapKey('foo'), $this->cache->loadTag('foo_tag')
        );
        $this->assertTrue($this->cache->delete('foo'));
        $this->assertNull($this->cache->loadKey('foo'));

        $this->assertNull($this->cache->loadTag('foo_tag'));

        $this->assertContains(
            $this->cache->mapKey('bar'), $this->cache->loadTag('all_tag')
        );
    }

}
