<?php

namespace Apix\Cache;

/**
 * Class Files
 * In files cache wrapper.
 * Expiration time and tags are stored in the cache file
 *
 * @package Apix\Cache
 * @author  MacFJA
 */
class Files extends AbstractCache
{
    /**
     * Constructor.
     *
     * @param array  $options Array of options.
     */
    public function __construct(array $options=null)
    {
        $options += array(
            'directory' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'apix-cache'
        );
        parent::__construct(null, $options);
        if (!file_exists($this->getOption('directory')) || !is_dir($this->getOption('directory'))) {
            mkdir($this->getOption('directory'), 0755, true);
        }
    }

    /**
     * Retrieves the cache content for the given key.
     *
     * @param  string $key The cache key to retrieve.
     * @return mixed|null Returns the cached data or null.
     */
    public function loadKey($key)
    {
        $key = $this->mapKey($key);
        $path = $this->getOption('directory') . DIRECTORY_SEPARATOR . base64_encode($key);

        if (!file_exists($path) || !is_file($path)) {
            return null;
        }

        $data = file_get_contents($path);
        if ('' === $data) {
            unlink($path);
            return null;
        }
        $pos = strpos($data, PHP_EOL, 0);
        $pos = strpos($data, PHP_EOL, $pos+1);
        if (false === $pos) {// Un-complete file
            unlink($path);
            return null;
        }

        $serialized = substr($data, $pos+1);
        return unserialize($serialized);
    }

    /**
     * Retrieves the cache keys for the given tag.
     *
     * @param  string $tag The cache tag to retrieve.
     * @return array|null Returns an array of cache keys or null.
     */
    public function loadTag($tag)
    {
        if (!$this->getOption('tag_enable')) {
            return null;
        }

        $encoded = base64_encode($this->mapTag($tag));
        $found = array();
        $files = scandir($this->getOption('directory'));
        foreach ($files as $file) {
            if (substr($file, 0, 1) === '.') {
                continue;
            }
            $path = $this->getOption('directory') . DIRECTORY_SEPARATOR . $file;
            $handle = fopen($path, 'r');
            $fileTags = explode(' ', rtrim(fgets($handle), PHP_EOL));
            fclose($handle);
            if (in_array($encoded, $fileTags, true)) {
                $found[] = base64_decode($file);
            }
        }

        if (0 === count($found)) {
            return null;
        }
        return $found;
    }

    /**
     * Saves data to the cache.
     *
     * @param  mixed $data The data to cache.
     * @param  string $key The cache id to save.
     * @param  array $tags The cache tags for this cache entry.
     * @param  int $ttl The time-to-live in seconds, if set to null the
     *                       cache is valid forever.
     * @return boolean Returns True on success or False on failure.
     */
    public function save($data, $key, array $tags = null, $ttl = null)
    {
        $key = $this->mapKey($key);
        $expire = (null === $ttl) ? 0 : time() + $ttl;

        $tag = '';
        if (null !== $tags) {
            $baseTags = $tags;
            array_walk($baseTags, function (&$item, $key, Files $cache) {
                $item = base64_encode($cache->mapTag($item));
            }, $this);
            $tag = implode(' ', $baseTags);
        }

        $path = $this->getOption('directory') . DIRECTORY_SEPARATOR . base64_encode($key);
        file_put_contents($path, $tag . PHP_EOL . $expire . PHP_EOL . serialize($data));
        return true;
    }

    /**
     * Deletes the specified cache record.
     *
     * @param  string $key The cache id to remove.
     * @return boolean Returns True on success or False on failure.
     */
    public function delete($key)
    {
        $key = $this->mapKey($key);
        $path = $this->getOption('directory') . DIRECTORY_SEPARATOR . base64_encode($key);
        if (!file_exists($path)) {
            return false;
        }

        return unlink($path);
    }

    /**
     * Removes all the cached entries associated with the given tag names.
     *
     * @param  array $tags The array of tags to remove.
     * @return boolean Returns True on success or False on failure.
     */
    public function clean(array $tags)
    {
        $toRemove = array();
        foreach ($tags as $tag) {
            $keys = $this->loadTag($tag);
            if (null === $keys) {
                return false;
            }
            $toRemove = array_merge($toRemove, $keys);
        }
        $toRemove = array_unique($toRemove);

        foreach ($toRemove as $key) {
            $this->delete($this->removePrefixKey($key));
        }

        return true;
    }

    /**
     * Flush all the cached entries.
     *
     * @param  boolean $all Wether to flush the whole database, or (preferably)
     *                      the entries prefixed with prefix_key and prefix_tag.
     * @return boolean Returns True on success or False on failure.
     */
    public function flush($all = false)
    {
        $files = scandir($this->getOption('directory'));
        foreach ($files as $file) {
            if ('.' === substr($file, 0, 1)) {
                continue;
            }
            $path = $this->getOption('directory') . DIRECTORY_SEPARATOR . $file;
            $fullKey = base64_decode($file);
            $key = $this->removePrefixKey($fullKey);

            if (!$all && ($key !== $fullKey || '' === $this->options['prefix_key'])) {
                unlink($path);
            }
        }
    }

    /**
     * Returns the time-to-live (in seconds) for the given key.
     *
     * @param  string $key The name of the key.
     * @return int|false Returns the number of seconds left, 0 if valid
     *                       forever or False if the key is non-existant.
     */
    public function getTtl($key)
    {
        $key = $this->mapKey($key);
        $path = $this->getOption('directory') . DIRECTORY_SEPARATOR . base64_encode($key);
        if (!file_exists($path) || !is_file($path)) {
            return false;
        }

        $handle = fopen($path, 'r');
        fgets($handle);
        $expire = fgets($handle);
        fclose($handle);

        if (0 === (int) $expire) {
            return 0;
        }

        return $expire - time();
    }
}