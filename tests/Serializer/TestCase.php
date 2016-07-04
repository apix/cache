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

use Apix\Cache\tests\TestCase as ApixTestCase;

class TestCase extends ApixTestCase
{
    protected $serializer = null;

    public function serializerProvider()
    {
        return array(
            array('string'),
            array(array('foo' => 'bar')),
            array(new \stdClass()),
            array(' '),
            // array(null),
        );
    }

    /**
     * @dataProvider serializerProvider
     * @deprecated
     */
    public function testIsSerialized($var, $str = null)
    {
        $this->assertFalse(
            $this->serializer->isSerialized($var)
        );

        $this->assertTrue(
            $this->serializer->isSerialized(
                $this->serializer->serialize($var)
            )
        );
    }

}
