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

namespace Apix\Cache\Indexer;

use Apix\TestCase,
    Apix\Cache\InArray as Engine;

class InArrayIndexerTest_OFF extends GenericIndexerTestCase
{
    protected $cache, $indexer;

    public $indexKey = 'indexKey';

    protected $options = array(
        'prefix_key' => 'unit_test-',
        'prefix_tag' => 'unit_test-',
    );

    public function setUp()
    {
        $this->cache = new Engine($this->options);

        $this->indexer = new InArrayIndexer($this->indexKey, $this->cache);
    }

    public function tearDown()
    {
        if (null !== $this->cache) {
            $this->cache->flush(true);
            unset($this->cache, $this->indexer);
        }
    }

}
