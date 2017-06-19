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

namespace Apix\Cache\PsrCache;

class TaggableItem extends Item
{

    /**
     * The tags associated with this entry.
     * @var array|null
     */
    protected $tags = null;

    /**
     * Sets this item tags.
     *
     * @param  array|null   $tags
     * @return TaggableItem The invoked object.
     */
    public function setTags(array $tags=null)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Returns this item tags.
     *
     * @return array|null
     */
    public function getTags()
    {
        return $this->tags;
    }

}
