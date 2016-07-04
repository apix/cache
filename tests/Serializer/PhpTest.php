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

class PhpTest extends TestCase
{

    public function setUp()
    {
        $this->serializer = new Php;
    }

    /**
     * @dataProvider serializerProvider
     */
    public function testSerialize($var)
    {
        $this->assertEquals(
            \serialize($var),
            $this->serializer->serialize($var)
        );
    }

    /**
     * @dataProvider serializerProvider
     */
    public function testUnserialize($var)
    {
        $this->assertEquals(
            $var,
            $this->serializer->unserialize(
                \serialize($var)
            )
        );
    }

}
