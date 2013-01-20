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

    public function provider()
    {
        return array(
            array('str' => 'str'),
            array('arr' => array('foo' => 'bar')),
            array('obj' => new \stdClass)
        );
    }

    /**
     * @dataProvider provider
     */
    public function testSerializeSetToNone($var)
    {
        $this->assertEquals(
            $var, $this->cache->serialize($var, 'none')
        );
    }

    /**
     * @dataProvider provider
     */
    public function testUnserializeSetToNone($var)
    {
        $this->assertEquals(
            $var, $this->cache->unserialize($var, 'none')
        );
    }

    /**
     * @dataProvider provider
     */
    public function testIsSerializedSetToNone($var)
    {
        $this->assertFalse($this->cache->isSerialized($var, 'none'));
    }

    /**
     * @dataProvider provider
     */
    public function testSerializeSetToPhp($var)
    {
        $this->assertEquals(
            serialize($var), $this->cache->serialize($var, 'php')
        );
    }

    /**
     * @dataProvider provider
     */
    public function testUnserializeSetToPhp($var)
    {
        $this->assertEquals(
            $var, $this->cache->unserialize(serialize($var), 'php')
        );
    }

    /**
     * @dataProvider provider
     */
    public function testIsSerializedSetToPhp($var)
    {
        $this->assertFalse($this->cache->isSerialized($var, 'php'));
        $this->assertTrue(
            $this->cache->isSerialized(
                $this->cache->serialize($var, 'php'), 'php'
            )
        );
    }

    /**
     * @dataProvider provider
     */
    public function testSerializeSetToJson($var)
    {
        $this->assertEquals(
            json_encode($var), $this->cache->serialize($var, 'json')
        );
    }

    /**
     * @dataProvider provider
     */
    public function testUnserializeSetToJson($var)
    {
        if(is_array($var)) $var = (object) $var;
        $this->assertEquals(
            $var, $this->cache->unserialize(json_encode($var), 'json')
        );
    }

    /**
     * @dataProvider provider
     */
    public function testIsSerializedSetToJson($var)
    {
        $this->assertFalse($this->cache->isSerialized($var, 'json'));
        $this->assertTrue(
            $this->cache->isSerialized(
                $this->cache->serialize($var, 'json'), 'json'
            )
        );
    }

    /**
     * @dataProvider provider
     */
    public function testSerializeSetToIgBinary($var)
    {
        $this->skipIfMissing('igbinary');

        $this->assertEquals(
            igbinary_serialize($var), // binary output
            $this->cache->serialize($var, 'igBinary')
        );
    }

    /**
     * @dataProvider provider
     */
    public function testUnserializeSetToIgBinary($var)
    {
        $this->skipIfMissing('igbinary');

        $this->assertEquals(
            $var, // binary output
            $this->cache->unserialize(igbinary_serialize($var), 'igBinary')
        );
    }

    /**
     * @dataProvider provider
     */
    public function testIsSerializedSetToIgBinary($var)
    {
        $this->skipIfMissing('igbinary');

        $this->assertFalse($this->cache->isSerialized($var, 'igBinary'));
        $this->assertTrue(
            $this->cache->isSerialized(
                $this->cache->serialize($var, 'igBinary'), 'igBinary'
            )
        );
    }

}
