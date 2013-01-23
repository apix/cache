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

namespace Apix\Cache\Serializer;

class StringerSetTest extends TestCase
{

    public function setUp()
    {
        $this->formatter = new StringerSet;
    }

    public function serializerProvider()
    {
        return array(
            array(array('a', 'b', 'c'), '+a +b +c '),
            array(array('juju', 'molly', 'loulou'), '+juju +molly +loulou '),
            // array( array('a', 'c'), '+a +b +c -b -x ' ),
        );
    }

    /**
     * @dataProvider serializerProvider
     */
    public function testSerialize($arr, $str)
    {
        $this->assertEquals( $str, $this->formatter->serialize($arr) );
    }

    public function unserializerProvider()
    {
        return array( array(
                array( 'dirty'=>0, 'keys'=>array('a', 'b', 'c') ),
                '+a +b +c '
            ), array(
                array( 'dirty'=>2, 'keys'=>array('a', 'c') ),
                '+a +b +c -b -x '
            )
        );
    }

    /**
     * @dataProvider unserializerProvider
     */
    public function testUnserializer($arr, $str)
    {
        $this->assertEquals(
            $arr, $this->formatter->unserialize($str)
        );
    }

    /**
     * @dataProvider serializerProvider
     */
    public function OFFtestIsSerialized($arr, $str)
    {
        $this->assertFalse($this->formatter->isSerialized($arr));
        $this->assertTrue($this->formatter->isSerialized($str));
    }

}
