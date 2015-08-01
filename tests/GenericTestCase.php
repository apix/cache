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
        self::assertNull($this->cache->loadKey('id'));
    }

    public function testLoadTagReturnsNullWhenInexistant()
    {
        self::assertNull($this->cache->loadTag('id'));
    }

    public function testSaveAndLoadWithString()
    {
        self::assertTrue($this->cache->save('data', 'id'));
        self::assertEquals('data', $this->cache->loadKey('id'));
        self::assertEquals('data', $this->cache->load('id'));
    }

    public function testSaveAndLoadWithArray()
    {
        $data = array('foo' => 'bar');
        self::assertTrue($this->cache->save($data, 'id'));
        self::assertEquals($data, $this->cache->loadKey('id'));
        self::assertEquals($data, $this->cache->load('id'));
    }

    public function testSaveAndLoadWithObject()
    {
        $data = new \stdClass();
        self::assertTrue($this->cache->save($data, 'id'));
        self::assertEquals($data, $this->cache->loadKey('id'));
        self::assertEquals($data, $this->cache->load('id'));
    }

    public function testDeleteInexistantReturnsFalse()
    {
        self::assertFalse($this->cache->delete('Inexistant'));
    }

    public function testDelete()
    {
        self::assertTrue(
            $this->cache->save('foo value', 'foo')
            && $this->cache->save('bar value', 'bar')
        );

        self::assertTrue($this->cache->delete('foo'));
        self::assertFalse($this->cache->delete('foo'));

        self::assertNull($this->cache->loadKey('foo'));
    }

    public function testTllFromSave()
    {
        self::assertFalse($this->cache->getTtl('non-existant'));

        self::assertTrue($this->cache->save('data', 'id'));
        self::assertEquals(0, $this->cache->getTtl('id'),
            "Expiration should be set to 0 (for ever) by default."
        );

        self::assertTrue($this->cache->save('data', 'id', null, 3600));
        self::assertEquals(3600, $this->cache->getTtl('id'), null, 5);
        // self::assertLessThanOrEqual(3600, $this->cache->getTtl('id'));
    }

    public function testTllFromLoad()
    {
        self::assertFalse($this->cache->getTtl('non-existant'));

        self::assertNull($this->cache->load('id'));
        self::assertEquals(0, $this->cache->getTtl('id'),
            "Expiration should be set to 0 (for ever) by default."
        );

        self::assertTrue($this->cache->save('data', 'id', null, 3600));
        self::assertEquals('data', $this->cache->load('id'));
        self::assertEquals(3600, $this->cache->getTtl('id'), null, 5);
        // self::assertLessThanOrEqual(3600, $this->cache->getTtl('id'));
    }

    ////
    // The tests belowe are tags related
    ////

    public function testSaveWithTagDisabled()
    {
        $this->cache->setOptions(array('tag_enable' => false));

        self::assertTrue(
            $this->cache->save('data', 'id', array('tag1', 'tag2'))
        );
        self::assertNull($this->cache->loadTag('tag1'));
    }

    /**
     * @group testme
     */
    public function testSaveWithJustOneSingularTag()
    {
        self::assertTrue($this->cache->save('data', 'id', array('tag')));
        $ids = array($this->cache->mapKey('id'));

        self::assertEquals($ids, $this->cache->loadTag('tag'));
        self::assertEquals($ids, $this->cache->load('tag', 'tag'));
    }

    public function testSaveManyTags()
    {
        self::assertTrue(
            $this->cache->save('data', 'id', array('tag1', 'tag2'))
        );
        $ids = array($this->cache->mapKey('id'));

        self::assertEquals($ids, $this->cache->loadTag('tag2'));
        self::assertEquals($ids, $this->cache->load('tag2', 'tag'));
    }

    public function testSaveWithOverlappingTags()
    {
        self::assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
        );

        $ids = $this->cache->loadTag('tag2');
        self::assertTrue(count($ids) == 2);
        self::assertContains($this->cache->mapKey('id1'), $ids);
        self::assertContains($this->cache->mapKey('id2'), $ids);
    }

    public function testClean()
    {
        self::assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3', 'tag4'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );

        self::assertTrue($this->cache->clean(array('tag4')));
        self::assertFalse($this->cache->clean(array('tag4')));
        self::assertFalse($this->cache->clean(array('non-existant')));

        self::assertNull($this->cache->loadKey('id2'));
        self::assertNull($this->cache->loadKey('id3'));
        self::assertNull($this->cache->loadTag('tag4'));
        self::assertEquals('data1', $this->cache->loadKey('id1'));
    }

    public function testDeleteAlsoRemoveTags()
    {
        self::assertTrue(
            $this->cache->save('foo value', 'foo', array('foo_tag', 'all_tag'))
            && $this->cache->save('bar value', 'bar', array('bar_tag', 'all_tag'))
        );

        self::assertContains(
            $this->cache->mapKey('foo'), $this->cache->loadTag('foo_tag')
        );
        self::assertTrue($this->cache->delete('foo'));
        self::assertNull($this->cache->loadKey('foo'));

        self::assertNull($this->cache->loadTag('foo_tag'));

        self::assertContains(
            $this->cache->mapKey('bar'), $this->cache->loadTag('all_tag')
        );
    }
}
