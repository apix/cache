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

class IgBinaryTest extends TestCase
{

    public function setUp()
    {
        $this->skipIfMissing('igbinary');
    }

    /**
     * @dataProvider serializerProvider
     */
    public function testSerialize($var)
    {
        $formatter = new IgBinary;
        $this->assertEquals(
            igbinary_serialize($var), $formatter->serialize($var)
        );
    }

    /**
     * @dataProvider serializerProvider
     */
    public function testUnserialize($var)
    {
        $formatter = new IgBinary;
        $this->assertEquals(
            $var, $formatter->unserialize(igbinary_serialize($var))
        );
    }

    /**
     * @dataProvider serializerProvider
     */
    public function testIsSerialized($var)
    {
        $formatter = new IgBinary;
        $this->assertFalse($formatter->isSerialized($var));

        $this->assertTrue(
            $formatter->isSerialized(
                $formatter->serialize($var)
            )
        );
    }

}
