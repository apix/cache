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

use Apix\Cache;

/**
 * ApcTest, supports both APC and APCu user cache.
 *
 * usage: php -d apc.enable_cli=1 `which phpunit` -v tests/ApcTest.php
 *
 * @package Apix\Cache
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class ApcuTest extends ApcTest
{
    public function setUp()
    {
        $this->skipIfMissing('apcu');

        if (!ini_get('apc.enable_cli')) {
            self::markTestSkipped(
                'apc.enable_cli MUST be enabled in order to run this unit test'
            );
        }

        $this->cache = new Cache\Apcu($this->options);
    }

    public function testComplyWithApc()
    {
        $this->assertTrue($this->cache->save('data', 'id'));
        $id = $this->cache->mapKey('id');
        $this->assertEquals('data', apcu_fetch($id));
    }

    public function testFlushSelected()
    {
        $this->assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );
        apcu_add('foo', 'bar');
        $this->assertTrue($this->cache->flush());
        $this->assertFalse($this->cache->flush());
        $this->assertEquals('bar', apcu_fetch('foo'));

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

        apcu_add('foo', 'bar');
        $this->assertTrue($this->cache->flush(true)); // always true!

        $this->assertEquals(false, apcu_fetch('foo'));

        $this->assertNull($this->cache->load('id3'));
        $this->assertNull($this->cache->load('tag1', 'tag'));
    }
}
