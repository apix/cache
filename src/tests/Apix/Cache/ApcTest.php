<?php
namespace Apix\Cache;

use Apix\TestCase;

/* php -d apc.enable_cli=1 `which phpunit` -v */
class ApcTest extends TestCase
{

    protected $cache = null;

    public function setUp()
    {
        if (!extension_loaded('apc')) {
            self::markTestSkipped(
                'The APC extension is required in order to run this unit test'
            );
        }

        if (!ini_get('apc.enable_cli')) {
            self::markTestSkipped(
                'apc.enable_cli MUST be enable in order to run this unit test'
            );
        }

        $this->cache = new Apc(
            array(
                'prefix_key' => 'unittest-apix-key:',
                'prefix_tag' => 'unittest-apix-tag:'
            )
        );
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush();
            unset($this->cache);
        }
    }

    public function testLoadReturnsNullWhenEmpty()
    {
        $this->assertNull( $this->cache->load('id') );
    }

    public function testSaveAndLoadWithString()
    {
        $this->assertTrue( $this->cache->save('strData', 'id') );

        $this->assertEquals( 'strData', $this->cache->load('id') );

        $id = $this->cache->mapKey('id');
        $this->assertEquals( 'strData', apc_fetch($id) );
    }

    public function testSaveAndLoadWithArray()
    {
        $data = array('foo' => 'bar');
        $this->assertTrue($this->cache->save($data, 'id'));
        $this->assertEquals($data, $this->cache->load('id'));
    }

    public function testSaveAndLoadWithObject()
    {
        $data = new \stdClass;
        $this->assertTrue($this->cache->save($data, 'id'));
        $this->assertEquals($data, $this->cache->load('id'));
    }

    public function testSaveWithTags()
    {
        $this->assertTrue(
            $this->cache->save('strData1', 'id1', array('tag1', 'tag2'))
        );

        $this->assertTrue(
            $this->cache->save('strData2', 'id2', array('tag3', 'tag4'))
        );

        $ids = $this->cache->load('tag2', 'tag');
         $this->assertEquals( array($this->cache->mapKey('id1')), $ids );
    }

    public function testSaveWithOverlappingTags()
    {
        $this->assertTrue(
            $this->cache->save('strData1', 'id1', array('tag1', 'tag2'))
        );

        $this->assertTrue(
            $this->cache->save('strData2', 'id2', array('tag2', 'tag3'))
        );

        $ids = $this->cache->load('tag2', 'tag');
        $this->assertTrue(count($ids) == 2);
        $this->assertContains($this->cache->mapKey('id1'), $ids);
        $this->assertContains($this->cache->mapKey('id2'), $ids);
    }

    public function testClean()
    {
        $this->cache->save('strData1', 'id1', array('tag1', 'tag2'));
        $this->cache->save('strData2', 'id2', array('tag2', 'tag3'));
        $this->cache->save('strData3', 'id3', array('tag3', 'tag4'));

        $this->cache->clean(array('tag4'));

        $this->assertNull($this->cache->load('id3'));
        $this->assertNull($this->cache->load('tag4', 'tag'));
        $this->assertEquals('strData2', $this->cache->load('id2'));
    }

    public function testFlushSelected()
    {
        $this->cache->save('strData1', 'id1', array('tag1', 'tag2'));
        $this->cache->save('strData2', 'id2', array('tag2', 'tag3'));
        $this->cache->save('strData3', 'id3', array('tag3', 'tag4'));

        apc_add('foo', 'bar');
        $this->cache->flush();
        $this->assertEquals('bar', apc_fetch('foo'));

        $this->assertNull($this->cache->load('id3'));
        $this->assertNull($this->cache->load('tag1', 'tag'));
    }

    public function testFlushAll()
    {
        $this->cache->save('strData1', 'id1', array('tag1', 'tag2'));
        $this->cache->save('strData2', 'id2', array('tag2', 'tag3'));
        $this->cache->save('strData3', 'id3', array('tag3', 'tag4'));

        apc_add('foo', 'bar');
        $this->cache->flush(true);
        $this->assertEquals(false, apc_fetch('foo'));

        $this->assertNull($this->cache->load('id3'));
        $this->assertNull($this->cache->load('tag1', 'tag'));
    }

    public function testDelete()
    {
        $this->cache->save('strData1', 'id1', array('tag1', 'tag2'));
        $this->cache->save('strData2', 'id2', array('tag2', 'tag3'));

        $this->cache->delete('id1');

        $this->assertNull($this->cache->load('id1'));
        $this->assertNull($this->cache->load('tag1', 'tag'));
    }

    public function testDeleteInexistant()
    {
        $this->assertFalse($this->cache->delete('Inexistant'));
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
