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

class DirectoryTest extends GenericTestCase
{
    /** @var null|Cache\Directory  */
    protected $cache = null;

    /** @var string **/
    protected $dir = null;

    public function setUp()
    {
        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'apix-cache-unittest';

        $this->cache = new Cache\Directory(
            $this->options + array('directory' => $this->dir)
        );
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush();

            $this->cache->delTree( $this->dir );
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