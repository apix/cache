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

    /**
     * Holds some generic default options.
     * @var array
     */
    protected $options = array(
        'prefix_key'        => 'apix-cache-key:', // prefix cache keys
        'prefix_tag'        => 'apix-cache-tag:', // prefix cache tags
        'tag_enable'        => true               // wether to enable tagging
    );

    /**
     * Constructor use to set the adapter and dedicated options.
     *
     * @param object|null $adapter The adapter to set, generally an object.
     * @param array|null  $options The array of user options.
     */
    public function __construct($adapter=null, array $options=null)
    {
        $this->adapter = $adapter;
        $this->setOptions($options);
    }

    /**
     * Sets and merges the options (overriding the default options).
     *
     * @param array|null $options The array of user options.
     */
    public function setOptions(array $options=null)
    {
        if (null !== $options) {
            $this->options = $options+$this->options;
        }
    }

    /**
     * Returns the named option.
     *
     * @param  string $key
     * @return mixed
     */
    public function getOption($key)
    {
        if (!isset($this->options[$key])) {
            throw new PsrCache\InvalidArgumentException(
                sprintf('Invalid option "%s"', $key)
            );
        }

        return $this->options[$key];
    }

    /**
     * Sets the named option.
     *
     * @param   string $key
     * @param   mixed  $value
     */
    public function setOption($key, $value)
    {
        return $this->options[$key] = $value;
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
     * @param string $name
     */
    public function setSerializer($name)
    {
        if (null === $name) {
            $this->serializer = null;
        } else {
            $classname = __NAMESPACE__ . '\Serializer\\';
            $classname .= ucfirst(strtolower($name));
            $this->serializer = new $classname();
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

    /**
     * Retrieves the cache content for the given key or the keys for a given tag.
     *
     * @param  string     $key  The cache id to retrieve.
     * @param  string     $type The type of the key (either 'key' or 'tag').
     * @return mixed|null Returns the cached data or null if not set.
     */
    public function load($key, $type='key')
    {
        return $type == 'key' ? $this->loadKey($key) : $this->loadTag($key);
    }

    /**
     * Returns the given string without the given prefix.
     *
     * @param  string $str    The subject string
     * @param  string $prefix The prefix to remove
     * @return string
     */
    public function removePrefix($str, $prefix)
    {
        return substr($str, 0, strlen($prefix)) == $prefix
                ? substr($str, strlen($prefix))
                : $str;
    }

    /**
     * Returns the given string without the internal key prefix.
     *
     * @param  string $str
     * @return string
     */
    public function removePrefixKey($str)
    {
        return $this->removePrefix($str, $this->options['prefix_key']);
    }

   /**
     * Returns the given string without the internal tag prefix.
     *
     * @param  string $str
     * @return string
     */
    public function removePrefixTag($str)
    {
        return $this->removePrefix($str, $this->options['prefix_tag']);
    }

}
