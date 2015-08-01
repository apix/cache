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

namespace Apix\Cache\tests;

/**
 * Class TestCase
 *
 * @package Apix\Cache\tests
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $options = array(
        'prefix_key' => 'unittest-apix-key:',
        'prefix_tag' => 'unittest-apix-tag:'
    );

    /**
     * @param string $name
     */
    public function skipIfMissing($name)
    {
        // do not try to load a missing extension on runtime
        // the required function is deprecated
        if (!extension_loaded($name)) {
            self::markTestSkipped(
                sprintf('The "%s" extension is required.', $name)
            );
        }
    }
}
