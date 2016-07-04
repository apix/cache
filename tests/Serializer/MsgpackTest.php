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

use Apix\Cache\Serializer\Msgpack;

class MsgpackTest extends TestCase
{

    public function setUp()
    {
        $this->skipIfMissing('msgpack');
        $this->serializer = new Msgpack;
    }

    /**
     * @dataProvider serializerProvider
     */
    public function testSerialize($var)
    {
        $this->assertEquals(
            \msgpack_pack($var),
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
                \msgpack_pack($var)
            )
        );
    }

}
