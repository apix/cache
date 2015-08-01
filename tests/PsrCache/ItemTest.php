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
        self::assertEquals('foo', $item->getKey());
        self::assertNull($item->get());

        self::assertFalse($item->isHit());
        self::assertFalse($item->exists());
    }

    public function testItemConstructorHittingCache()
    {
        $item = new Item('foo', 'bar', null, true);
        self::assertEquals('foo', $item->getKey());
        self::assertEquals('bar', $item->get());

        self::assertTrue($item->isHit());
        self::assertTrue($item->exists());
    }

    public function testSetAndisHit()
    {
        self::assertSame($this->item, $this->item->set('new foo value'));
        self::assertFalse($this->item->isHit());

        self::assertSame($this->item, $this->item->setHit(true));
        self::assertSame('new foo value', $this->item->get());
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
        self::assertSame($this->item, $this->item->setExpiration($from));
        $date = new \DateTime($to);

        $expire = $this->item->getExpiration();
        self::assertInstanceOf('DateTime', $expire);

        self::assertEquals((int) $date->format('U'), $expire->format('U'), '', 10);
    }

    /**
     * @dataProvider dateProvider
     */
    public function testGetTtlInSecond($from, $to, $sec)
    {
        if ($sec !== null) {
            $this->item->setExpiration($from);
            self::assertEquals($sec, $this->item->getTtlInSecond(), '', 10);
        }
    }

    public function testItemIsRegenerating()
    {
        self::assertFalse($this->item->isRegenerating());
    }

}
