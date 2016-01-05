<?php

namespace Apix\Cache\tests;

use Apix\Cache;

class DirectoryTest extends GenericTestCase
{
    /** @var null|Cache\Directory  */
    protected $cache = null;

    public function setUp()
    {
        $this->cache = new Cache\Directory($this->options+array('directory' => __DIR__.DIRECTORY_SEPARATOR.'directory_test'));
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush();

            $this->cache->delTree(__DIR__.DIRECTORY_SEPARATOR.'directory_test');
            unset($this->cache);
        }
    }

    public function testNonExistsExpire()
    {
        $this->assertTrue($this->cache->save('data', 'id', null, 1));
        $this->assertTrue($this->cache->save('data', 'id'));

        $this->assertEquals(0, $this->cache->getTtl('id'));
    }

    public function testDeleteWithNoTag()
    {
        $this->cache->setOption('tag_enable', false);
        $this->assertTrue($this->cache->save('data', 'id', array('tag')));
        $this->assertTrue($this->cache->delete('id'));
    }
}