<?php

namespace Apix\Cache\tests;

use Apix\Cache;

class FilesTest extends GenericTestCase
{
    /** @var null|Cache\Files  */
    protected $cache = null;

    public function setUp()
    {
        $this->cache = new Cache\Files($this->options+array('directory' => __DIR__.DIRECTORY_SEPARATOR.'files_test'));
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush();
            unset($this->cache);

            rmdir(__DIR__.DIRECTORY_SEPARATOR.'files_test');
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