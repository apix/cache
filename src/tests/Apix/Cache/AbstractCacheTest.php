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

namespace Apix\Cache;

use Apix\TestCase;

class AbstractCacheTest extends TestCase
{

    public function setUp()
    {
        $this->cache = new Apc;
    }

    public function testSerializeDoesString()
    {
        $str = 'str';
        $this->assertEquals($str, $this->cache->serialize($str, 'none'));
        $this->assertEquals('s:3:"str";', $this->cache->serialize($str, 'php'));
        $this->assertEquals('"str"', $this->cache->serialize($str, 'json'));
        // $this->assertEquals('"str"', $this->cache->serialize($str, 'igBinary'));
    }

    public function testSerializeDoesObject()
    {
        $obj = new \stdClass;
        $this->assertEquals($obj, $this->cache->serialize($obj, 'none'));
        $this->assertEquals('O:8:"stdClass":0:{}', $this->cache->serialize($obj, 'php'));
        $this->assertEquals('{}', $this->cache->serialize($obj, 'json'));
        // $this->assertEquals('"str"', $this->cache->serialize($obj, 'igBinary'));
    }

    public function testSerializeDoesArray()
    {
        $array = array('foo'=>'bar');
        $this->assertEquals($array, $this->cache->serialize($array, 'none'));
        $this->assertEquals('a:1:{s:3:"foo";s:3:"bar";}', $this->cache->serialize($array, 'php'));
        $this->assertEquals('{"foo":"bar"}', $this->cache->serialize($array, 'json'));
        // $this->assertEquals('"str"', $this->cache->serialize($array, 'igBinary'));
    }

}
