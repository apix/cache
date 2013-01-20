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

class NoneTest extends TestCase
{

    /**
     * @dataProvider serializerProvider
     */
    public function testSerialize($var)
    {
        $formatter = new None;
        $this->assertEquals(
            $var, $formatter->serialize($var)
        );
    }

    /**
     * @dataProvider serializerProvider
     */
    public function testUnserialize($var)
    {
        $formatter = new None;
        $this->assertEquals(
            $var, $formatter->unserialize($var)
        );
    }

    /**
     * @dataProvider serializerProvider
     */
    public function testIsSerialized($var)
    {
        $formatter = new None;
        $this->assertFalse($formatter->isSerialized($var));
    }

}
