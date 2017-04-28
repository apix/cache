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

class ItemTest extends TestCase
{
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
    }

    public function testItemConstructorHittingCache()
    {
        $item = new Item('foo', 'bar', null, true);
        $this->assertEquals('foo', $item->getKey());
        $this->assertEquals('bar', $item->get());

        $this->assertTrue($item->isHit());
    }

    public function testSetAndisHit()
    {
        $this->assertSame($this->item, $this->item->set('new foo value'));
        $this->assertFalse($this->item->isHit());

        $this->assertSame($this->item, $this->item->setHit(true));
        $this->assertSame('new foo value', $this->item->get());
    }

    public function expiresAtProvider()
    {
        return array(
            'DateTime' => array(new \DateTime('1 day'), '1 day', 86400),
            'Zero to Now' => array(0, null, 0), // or 'now' (same)
            'Integer' => array(999, '+999 seconds', 999),
            'Null' => array(null, Item::DEFAULT_EXPIRATION, null),
        );
    }

    /**
     * @dataProvider expiresAtProvider
     */
    public function testExpiresAt($from, $to)
    {
        $this->assertSame($this->item, $this->item->expiresAt($from));
        $date = new \DateTime($to);

        $expire = $this->item->getExpiration();
        $this->assertInstanceOf('DateTime', $expire);

        $this->assertEquals(
            (int) $date->format('U'), $expire->format('U'), '', 10
        );
    }

    /**
     * @dataProvider expiresAtProvider
     */
    public function testGetTtlInSecond($from, $to, $sec)
    {
        if ($sec !== null) {
            $this->item->expiresAt($from);
            $this->assertEquals($sec, $this->item->getTtlInSecond(), '', 10);
        }
    }

    public function expiresAfterProvider()
    {
        return array(
            'DateInterval' => array(new \DateInterval('P1Y'), 'now+1year'),
            'Zero to Now' => array(0, null), // or 'now' (same)
            'Integer' => array(999, '+999 seconds'),
            'Null' => array(null, Item::DEFAULT_EXPIRATION),
        );
    }

    /**
     * @dataProvider expiresAfterProvider
     */
    public function testExpiresAfter($from, $to)
    {
        $this->assertSame($this->item, $this->item->expiresAfter($from));
        $date = new \DateTime($to);

        $expire = $this->item->getExpiration();
        $this->assertInstanceOf('DateTime', $expire);

        $this->assertEquals(
            (int) $date->format('U'), $expire->format('U'), '', 10
        );
    }

    /**
     * @expectedException Apix\Cache\PsrCache\InvalidArgumentException
     */
    public function testExpiresAtThrowAnException()
    {
        $this->item->expiresAt('string');
    }

    /**
     * @expectedException Apix\Cache\PsrCache\InvalidArgumentException
     */
    public function testExpiresAfterThrowAnException()
    {
        $this->item->expiresAfter('string');
    }

}
