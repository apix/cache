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

class JsonTest extends TestCase
{

    /**
     * @dataProvider serializerProvider
     */
    public function testSerialize($var)
    {
        $formatter = new Json;
        $this->assertEquals(
            json_encode($var), $formatter->serialize($var)
        );
    }

    /**
     * @dataProvider serializerProvider
     */
    public function testUnserialize($var)
    {
        $formatter = new Json;
        if(is_array($var)) $var = (object) $var;
        $this->assertEquals(
            $var, $formatter->unserialize(json_encode($var))
        );
    }

    /**
     * @dataProvider serializerProvider
     */
    public function testIsSerialized($var)
    {
        $formatter = new Json;
        $this->assertFalse($formatter->isSerialized($var));

        $this->assertTrue(
            $formatter->isSerialized(
                $formatter->serialize($var)
            )
        );
    }

}
