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
 * Supports both APC and APCu user cache.
 *
 * usage: php -d apc.enable_cli=1 `which phpunit` -v tests/ApcTest.php
 */
class ApcTest extends GenericTestCase
{
    /**
     * @var \Apix\Cache\Apc
     */
    protected $cache = null;

    public function setUp()
    {
        $this->skipIfMissing('apc');

        if (!ini_get('apc.enable_cli')) {
            self::markTestSkipped(
                'apc.enable_cli MUST be enabled in order to run this unit test'
            );
        }

        $this->cache = new Cache\Apc($this->options);
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush();
            unset($this->cache);
        }
    }

    public function testComplyWithApc()
    {
        self::assertTrue($this->cache->save('data', 'id'));
        $id = $this->cache->mapKey('id');
        self::assertEquals('data', apc_fetch($id));
    }

    public function testFlushSelected()
    {
        self::assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );
        apc_add('foo', 'bar');
        self::assertTrue($this->cache->flush());
        self::assertFalse($this->cache->flush());
        self::assertEquals('bar', apc_fetch('foo'));

        self::assertNull($this->cache->load('id3'));
        self::assertNull($this->cache->load('tag1', 'tag'));
    }

    public function testFlushAll()
    {
        self::assertTrue(
            $this->cache->save('data1', 'id1', array('tag1', 'tag2'))
            && $this->cache->save('data2', 'id2', array('tag2', 'tag3'))
            && $this->cache->save('data3', 'id3', array('tag3', 'tag4'))
        );

        apc_add('foo', 'bar');
        self::assertTrue($this->cache->flush(true)); // always true!

        self::assertEquals(false, apc_fetch('foo'));

        self::assertNull($this->cache->load('id3'));
        self::assertNull($this->cache->load('tag1', 'tag'));
    }

    /**
     * -runTestsInSeparateProcesses
     * -runInSeparateProcesses
     * -preserveGlobalState enable
     * -depends test
     */
    public function testShortTtlDoesExpunge()
    {
        self::markTestSkipped(
            "APC will only expunged its cache on the next request which makes "
            . "this specific unit untestable!?... :-("
        );
        $this->cache->save('ttl-1', 'ttlId', null, -1);
        // self::assertSame('ttl-1', apc_fetch($this->cache->mapKey('ttlId')));
        // self::assertSame('ttl-1', $this->cache->load('ttlId'));
        self::assertNull( $this->cache->load('ttlId'), "Should be null");
    }

    public function testGetInternalInfos()
    {
        $this->cache->save('someData', 'someId', null, 69);
        $infos = $this->cache->getInternalInfos('someId');
        self::assertSame(69, $infos['ttl']);
    }

    public function testGetInternalInfosReturnFalseWhenNonExistant()
    {
        self::assertFalse(
            $this->cache->getInternalInfos('non-existant')
        );
    }

}
