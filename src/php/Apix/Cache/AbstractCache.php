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

namespace Apix\Cache;

/**
 * Base class provides the cache wrappers structure.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
abstract class AbstractCache implements Adapter
{

    /**
     * Holds an injected adapter.
     * @var object
     */
    protected $adapter = null;

    /**
     * @var Serializer\Adapter
     */
    protected $serializer;

    protected $options = array(
        'prefix_key'    => 'apix-cache-key:', // prefix cache keys
        'prefix_tag'    => 'apix-cache-tag:', // prefix cache tags
        'tag_enable'    => true               // wether to enable tagging
    );

    /**
     * Constructor use to set the adapter and merge the options (overriding the
     * default ones).
     *
     * @param object|null $adapter The adapter to set, generally an object.
     * @param array       $options The array of user options.
     */
    public function __construct($adapter=null, array $options=null)
    {
        $this->adapter = $adapter;

        if (null !== $options) {
            $this->options = $options+$this->options;
        }
    }

    /**
     * Returns a prefixed and sanitased cache id.
     *
     * @param  string $key The base key to prefix.
     * @return string
     */
    public function mapKey($key)
    {
        return $this->sanitise($this->options['prefix_key'] . $key);
    }

    /**
     * Returns a prefixed and sanitased cache tag.
     *
     * @param  string $tag The base tag to prefix.
     * @return string
     */
    public function mapTag($tag)
    {
        return $this->sanitise($this->options['prefix_tag'] . $tag);
    }

    /**
     * Returns a sanitased string for keying/tagging purpose.
     *
     * @param  string $key The string to sanitise.
     * @return string
     */
    public function sanitise($key)
    {
        return $key;
        // return str_replace(array('/', '\\', ' '), '_', $key);
    }

    /**
     * Gets the injected adapter.
     *
     * @return object
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Sets the serializer.
     *
     * @param  string $name
     * @return void
     */
    public function setSerializer($name)
    {
        if (null === $name) {
            $this->serializer = null;
        } else {
            $classname = __NAMESPACE__ . '\Serializer\\';
            $classname .= ucfirst(strtolower($name));
            $this->serializer = new $classname;
        }
    }

    /**
     * Gets the serializer.
     *
     * @return Serializer\Adapter
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

}
