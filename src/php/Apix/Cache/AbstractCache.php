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

abstract class AbstractCache implements Adapter
{

    protected $adapter;

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
     * Serialises mixed data as a string.
     *
     * @param  mixed        $data
     * @return string|mixed
     */
    public function serialize($data, $type)
    {
        switch ($type) {
            case 'json':
                return json_encode($data);

            case 'igBinary':
                // @codeCoverageIgnoreStart
                // igBinary is not always compiled on the host machine.
                return \igbinary_serialize($data);
                // @codeCoverageIgnoreEnd

            case 'php':
                return serialize($data);

            case 'none':
            default:
                return $data;
        }
    }

    /**
     * Unserialises a string representation as mixed data.
     *
     * @param  string       $data
     * @return mixed|string
     */
    public function unserialize($str, $type)
    {
        switch ($type) {
            case 'json':
                return json_decode($str);

            case 'igBinary':
                // @codeCoverageIgnoreStart
                // igBinary is not always compiled on the host machine.
                return \igbinary_unserialize($str);
                // @codeCoverageIgnoreEnd

            case 'php':
                return unserialize($str);

            case 'none':
            default:
                return $str;
        }
    }

    /**
     * Checks if the input is serialized a string representation as mixed data.
     *
     * @param  string  $data
     * @return boolean
     */
    public function isSerialized($str, $type)
    {
        if (!is_string($str)) {
            return false;
        }

        switch ($type) {
            case 'json':
                return (boolean) json_decode($str) !== null;

            case 'igBinary':
                // @codeCoverageIgnoreStart
                // igBinary is not always compiled on the host machine.
                return false; // TODO;
                // @codeCoverageIgnoreEnd

            case 'php':
                return (boolean) ($str=='b:0;' || @unserialize($str) !== false);

            case 'none':
            default:
                return false;
        }
    }

}
