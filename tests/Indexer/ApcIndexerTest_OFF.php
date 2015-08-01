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

namespace Apix\Cache\tests\Indexer;

use Apix\Cache,
    Apix\Cache\Indexer;

/**
 * Class ApcIndexerTest
 *
 * @package Apix\Cache\tests\Indexer
 */
class ApcIndexerTest extends GenericIndexerTestCase
{
    protected $cache;
    protected $indexer;

    public function setUp()
    {
        $this->skipIfMissing('apc');

        if (!ini_get('apc.enable_cli')) {
            self::markTestSkipped(
                'apc.enable_cli MUST be enabled in order to run this unit test'
            );
        }

        $this->cache = new Cache\Apc($this->options);

        $this->indexer = new Indexer\ApcIndexer($this->indexKey, $this->cache);
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush(true);
            unset($this->cache, $this->indexer);
        }
    }

}
