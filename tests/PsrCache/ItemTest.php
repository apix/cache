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

namespace Apix\Cache\tests\PsrCache;

use Apix\Cache\tests\TestCase;
use Apix\Cache\PsrCache\Item;

/**
 * Class ItemTest
 *
 * @package Apix\Cache\tests\PsrCache
 */
class ItemTest extends TestCase
{
    /**
     * @var \Apix\Cache\PsrCache\Item
     */
    protected $item = null;

    public function setUp()
    {
        $this->item = new Item('foo');
    }

    public function tearDown()
    {
        if (null !== $this->item) {
            unset($this->item);
        }
    }

    public function testItemConstructor()
    {
        $item = new Item('foo', 'bar');
        $this->assertEquals('foo', $item->getKey());
        $this->assertNull($item->get());

        $this->assertFalse($item->isHit());
        $this->assertFalse($item->exists());
    }

    public function testItemConstructorHittingCache()
    {
        $item = new Item('foo', 'bar', null, true);
        $this->assertEquals('foo', $item->getKey());
        $this->assertEquals('bar', $item->get());

        $this->assertTrue($item->isHit());
        $this->assertTrue($item->exists());
    }

    public function testSetAndisHit()
    {
        $this->assertSame($this->item, $this->item->set('new foo value'));
        $this->assertFalse($this->item->isHit());

        $this->assertSame($this->item, $this->item->setHit(true));
        $this->assertSame('new foo value', $this->item->get());
    }

    /**
     * @expectedException \Apix\Cache\PsrCache\InvalidArgumentException
     */
    public function testSetExpirationThrowAnException()
    {
        $this->item->setExpiration('string');
    }

    /**
     * @return array
     */
    public function dateProvider()
    {
        return array(
            'FromDateTime' => array(new \DateTime('1 day'), '1 day', 86400),
            'FromZeroToNow' => array(0, null, 0), // or 'now' (same)
            'FromInteger' => array(999, '+999 seconds', 999),
            'FromNull' => array(null, Item::DEFAULT_EXPIRATION, null),
        );
    }

    /**
     * @dataProvider dateProvider
     */
    public function testSetExpiration($from, $to)
    {
        $this->assertSame($this->item, $this->item->setExpiration($from));
        $date = new \DateTime($to);

        $expire = $this->item->getExpiration();
        $this->assertInstanceOf('DateTime', $expire);

        $this->assertEquals((int) $date->format('U'), $expire->format('U'), '', 10);
    }

    /**
     * @dataProvider dateProvider
     */
    public function testGetTtlInSecond($from, $to, $sec)
    {
        if ($sec !== null) {
            $this->item->setExpiration($from);
            $this->assertEquals($sec, $this->item->getTtlInSecond(), '', 10);
        }
    }

    public function testItemIsRegenerating()
    {
        $this->assertFalse($this->item->isRegenerating());
    }

}
