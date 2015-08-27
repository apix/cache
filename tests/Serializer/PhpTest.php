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

namespace Apix\Cache\tests\Serializer;

use Apix\Cache\Serializer\Php;

/**
 * Class PhpTest
 *
 * @package Apix\Cache\tests\Serializer
 */
class PhpTest extends TestCase
{

    /**
     * @dataProvider serializerProvider
     */
    public function testSerialize($var)
    {
        $formatter = new Php();
        $this->assertEquals(
            serialize($var), $formatter->serialize($var)
        );
    }

    /**
     * @dataProvider serializerProvider
     */
    public function testUnserialize($var)
    {
        $formatter = new Php();
        $this->assertEquals(
            $var, $formatter->unserialize(serialize($var))
        );
    }

    /**
     * @dataProvider serializerProvider
     */
    public function testIsSerialized($var)
    {
        $formatter = new Php();
        $this->assertFalse($formatter->isSerialized($var));

        $this->assertTrue(
            $formatter->isSerialized(
                $formatter->serialize($var)
            )
        );
    }

}
