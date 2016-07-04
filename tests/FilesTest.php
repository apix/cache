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

class FilesTest extends GenericTestCase
{
    /** @var null|Cache\Files **/
    protected $cache = null;

    /** @var string **/
    protected $dir = null;

    public function setUp()
    {
        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'apix-cache-unittest';

        $this->cache = new Cache\Files(
            $this->options + array('directory' => $this->dir)
        );
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush();
            unset($this->cache);

            rmdir($this->dir);
        }
    }

    public function testCorrupted()
    {
        $this->assertTrue($this->cache->save('data', 'id'));
        $encoded = base64_encode($this->cache->mapKey('id'));

        file_put_contents($this->cache->getOption('directory').DIRECTORY_SEPARATOR.$encoded, '');
        $this->assertNull($this->cache->loadKey('id'));

        file_put_contents($this->cache->getOption('directory').DIRECTORY_SEPARATOR.$encoded, ' ');
        $this->assertNull($this->cache->loadKey('id'));

        file_put_contents($this->cache->getOption('directory').DIRECTORY_SEPARATOR.$encoded, ' '.PHP_EOL);
        $this->assertNull($this->cache->loadKey('id'));

        file_put_contents($this->cache->getOption('directory').DIRECTORY_SEPARATOR.$encoded, PHP_EOL);
        $this->assertNull($this->cache->loadKey('id'));
    }
}