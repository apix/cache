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

use Apix\Cache\Serializer\Json;

class JsonTest extends TestCase
{

    public function setUp()
    {
        $this->skipIfMissing('json');
        $this->serializer = new Json;
    }

    /**
     * @dataProvider serializerProvider
     */
    public function testSerialize($var)
    {
        $this->assertEquals(
            \json_encode($var),
            $this->serializer->serialize($var)
        );
    }

    /**
     * @dataProvider serializerProvider
     */
    public function testUnserialize($var)
    {
        if(is_array($var)) $var = (object) $var;

        $this->assertEquals(
            $var,
            $this->serializer->unserialize(
                \json_encode($var)
            )
        );
    }

}
