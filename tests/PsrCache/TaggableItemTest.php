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

use Apix\Cache\PsrCache;

class TaggableItemTest extends ItemTest
{
    protected $item = null;

    public function setUp()
    {
        $this->item = new PsrCache\TaggableItem('foo');
    }

    public function tearDown()
    {
        unset($this->item);
    }

    public function testGetItemTagsIsNullByDefault()
    {
        $this->assertNull($this->item->getTags());
    }

    public function testSetItemTags()
    {
        $tags = array('fooTag', 'barTag');
        $this->assertSame($this->item, $this->item->setTags($tags));
        $this->assertSame($tags, $this->item->getTags());
    }

}
