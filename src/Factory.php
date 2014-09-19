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

use Apix\Cache;

/**
 * Apix Cache Factory class.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class Factory
{

    const ERROR = '%s requires either a supported client object e.g. "\Redis", "\MongoClient", ...; or an object that implements Apix\Cache\Adapter; or an adapter name (string) e.g. "APC", "array"; or even an a plain array().';

    /**
     * Holds an array of supported cache clients.
     * @var array
     */
    public static $clients = array(
        'Runtime', 'Array', 'ArrayObject', 'Apc',
        'Redis', 'MongoClient', 'Memcached', 'PDO'
    );

    /**
     * Holds an associative array of cache adapters.
     * @var array
     */
    public static $adapters = array(
        'MongoClient' => 'Mongo', 'PDO' => 'Pdo',
        'ArrayObject' => 'Runtime'
    );

    /**
     * Factory pattern.
     *
     * @param  mixed                                   $mix      Either a supported client object e.g. '\Redis';
     *                                                           or one that implements \Apix\Cache\Adapter;
     *                                                           or an adapter name (string) e.g. "APC", "Runtime";
     *                                                           or even a plain array() or \ArrayObject.
     * @param  array                                   $options  An array of options
     * @param  boolean                                 $taggable Wether to return a taggable pool.
     * @return PsrCache\Pool|PsrCache\TaggablePool
     * @throws PsrCache\InvalidArgumentException
     * @throws Apix\Cache\Exception
     */
    public static function getPool($mix, array $options=array(), $taggable=false)
    {
        switch (true) {

            case is_a($mix, 'Apix\Cache\Adapter'):
                $class = $mix;
            break;

            case is_object($mix)
                 && in_array($name = get_class($mix), self::$clients):
                
                if ($name == 'PDO') {
                    $name = 'Pdo\\' . AbstractPdo::getDriverName($mix);
                } else {
                    $name = isset(self::$adapters[$name])
                            ? self::$adapters[$name]
                            : $name;
                }
            break;

            case is_string($mix)
                 && in_array(
                        $name = strtolower($mix),
                        $clients = array_map('strtolower', self::$clients)
                    ):
                $key = array_search($name, $clients);
                $name = self::$clients[$key];
                $name = $name == 'Array' || $name == 'ArrayObject' ? 'Runtime' : $name;
                $mix = null;
            break;

            case is_array($mix):
                $name = 'Runtime';
                $mix = null;
            break;

            default:
                throw new PsrCache\InvalidArgumentException(
                    sprintf(self::ERROR, __CLASS__)
                );
        }

        if (!isset($class)) {
            $class = '\Apix\Cache\\' . $name;
        }

        try {
            if (null === $mix) {
                $cache = new $class($options);
            } else {
                $cache = new $class($mix, $options);
            }
        } catch (\Exception $e) {
            throw new Cache\Exception($e);
        }

        return $taggable
                ? new PsrCache\TaggablePool($cache)
                : new PsrCache\Pool($cache);
    }

    /**
     * @see self::getPool
     * @return PsrCache\TaggablePool
     */
    public static function getTaggablePool($mix, array $options=array())
    {
        return self::getPool($mix, $options, true);
    }

}
