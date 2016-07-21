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
 * Class Directory
 * Directory cache wrapper.
 * Expiration time and tags are stored separately from the cached data
 *
 * @package Apix\Cache
 * @author  MacFJA
 */
class Directory extends AbstractCache
{
    /**
     * Constructor.
     *
     * @param array  $options Array of options.
     */
    public function __construct(array $options = array())
    {
        $options += array(
            'directory' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'apix-cache',
            'locking' => true
        );
        parent::__construct(null, $options);
        $this->initDirectories();
    }

    /**
     * Initialize cache directories (create them)
     */
    protected function initDirectories()
    {
        $this->getBasePath('key');
        $this->getBasePath('tag');
        $this->getBasePath('ttl');
    }

    /**
     * Get the base path, and ensure they are created
     *
     * @param string $type The path type (key, ttl, tag)
     * @return string
     */
    protected function getBasePath($type)
    {
        $path = rtrim($this->getOption('directory'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        switch ($type) {
            case 'ttl':
                $path .= 'ttl' . DIRECTORY_SEPARATOR;
                break;
            case 'tags':
            case 'tag':
                $path .= 'tag' . DIRECTORY_SEPARATOR;
                break;
        }
        $this->buildPath($path);

        return $path;
    }

    /**
     * Get the file data.
     * If enable, lock file to preserve atomicity
     *
     * @param string $path The file path
     * @return string
     */
    protected function readFile($path)
    {
        $handle = fopen($path, 'rb');
        if ($this->getOption('locking')) {
            flock($handle, LOCK_SH);
        }
        $data = stream_get_contents($handle);
        if ($this->getOption('locking')) {
            flock($handle, LOCK_UN);
        }
        fclose($handle);

        return $data;
    }

    /**
     * Get the path of a cached data
     *
     * @param string $key The cache key
     * @return string
     */
    protected function getKeyPath($key)
    {
        $dir = $this->getBasePath('key');
        $baseKey = base64_encode($key);
        $sep = DIRECTORY_SEPARATOR;
        $path = $dir . preg_replace('/^(.)(.)(.).+$/', '$1' . $sep . '$2' . $sep . '$3' . $sep . '$0', $baseKey);

        return $path;
    }

    /**
     * Get the path of the expiration file for a key
     *
     * @param string $key The cache key
     * @return string
     */
    protected function getTtlPath($key)
    {
        $baseKey = base64_encode($key);
        $path = $this->getBasePath('ttl') . substr($baseKey, 0, 4);

        return $path;
    }

    /**
     * Get the expiration data of a key
     *
     * @param string $key The cache key
     * @return bool|int
     */
    protected function loadExpire($key)
    {
        $path = $this->getTtlPath($key);

        if (!is_file($path)) {
            return false;
        }

        $expires = json_decode($this->readFile($path), true);

        if (!array_key_exists(base64_encode($key), $expires)) {
            return false;
        }

        return $expires[base64_encode($key)];
    }

    /**
     * Get the path of a tag file
     *
     * @param string $tag The tag name
     * @return string
     */
    protected function getTagPath($tag)
    {
        $baseTag = base64_encode($tag);
        $path = $this->getBasePath('tag') . $baseTag;

        return $path;
    }

    /**
     * Build and return the path of a directory
     *
     * @param string $path The directory path to build
     * @return mixed
     */
    protected function buildPath($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    /**
     * Save a tag
     *
     * @param string $name The tag name
     * @param string[] $ids The list of cache keys associated to the tag
     */
    protected function saveTag($name, $ids)
    {
        $ids = array_unique($ids);
        array_walk($ids, function(&$item) { $item = base64_encode($item); });

        $path = $this->getTagPath($this->mapTag($name));
        $this->buildPath(dirname($path));
        file_put_contents($path, implode(PHP_EOL, $ids), $this->getOption('locking') ? LOCK_EX : null);
    }

    /**
     * Save the expiration time of a cache
     *
     * @param string $key the cache key
     * @param false|int $ttl The TTL of the cache
     */
    protected function saveExpire($key, $ttl)
    {
        $baseKey = base64_encode($key);

        $path = $this->getTtlPath($key);
        $this->buildPath(dirname($path));

        $expires = array();
        if (file_exists($path) && is_file($path)) {
            $expires = json_decode($this->readFile($path), true);
        }

        if ($ttl === false) {
            if (array_key_exists($baseKey, $expires)) {
                unset($expires[$baseKey]);
            } else {
                return;
            }
        } else {
            $expires[$baseKey] = time() + $ttl;
        }

        file_put_contents($path, json_encode($expires), $this->getOption('locking') ? LOCK_EX : null);
    }

    /**
     * Return the list of all existing tags
     *
     * @return string[]
     */
    protected function getAllTags()
    {
        $basePath = $this->getBasePath('tag');
        $baseTags = scandir($basePath);

        $tags = array();

        foreach ($baseTags as $baseTag) {
            if (substr($baseTag, 0, 1) === '.') {
                continue;
            }

            $tags[] = $this->removePrefixTag(base64_decode($baseTag));
        }

        return $tags;
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

        $path = $this->getKeyPath($key);
        if (!file_exists($path) && !is_file($path)) {
            return null;
        }

        return unserialize($this->readFile($path));
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

        $tag = $this->mapTag($tag);

        $path = $this->getTagPath($tag);
        if (!is_file($path)) {
            return null;
        }

        $keys = file($path, FILE_IGNORE_NEW_LINES);

        if (0 === count($keys)) {
            return null;
        }

        array_walk($keys, function (&$item) { $item = base64_decode($item); });

        return $keys;
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

        $path = $this->getKeyPath($key);
        $this->buildPath(dirname($path));
        file_put_contents($path, serialize($data), $this->getOption('locking') ? LOCK_EX : null);

        if (null !== $tags) {
            foreach ($tags as $tag) {
                $ids = $this->loadTag($tag);
                $ids[] = $key;
                $this->saveTag($tag, $ids);
            }
        }

        if (null !== $ttl) {
            $this->saveExpire($key, $ttl);
        } else {
            $this->saveExpire($key, false);
        }

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

        $path = $this->getKeyPath($key);
        if (!is_file($path)) {
            return false;
        }

        unlink($path);

        foreach ($this->getAllTags() as $tag) {
            $ids = $this->loadTag($tag);
            if (null === $ids) {
                continue;
            }
            if (in_array($key, $ids, true) !== false) {
                unset($ids[array_search($key, $ids, true)]);
                $this->saveTag($tag, $ids);
            }
        }

        $this->saveExpire($key, false);

        return true;
    }

    /**
     * Removes all the cached entries associated with the given tag names.
     *
     * @param  array $tags The array of tags to remove.
     * @return boolean Returns True on success or False on failure.
     */
    public function clean(array $tags)
    {
        foreach ($tags as $tag) {
            $ids = $this->loadTag($tag);

            if (null === $ids) {
                continue;
            }
            foreach ($ids as $key) {
                $this->delete($this->removePrefixKey($key));
            }
            unlink($this->getTagPath($this->mapTag($tag)));
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
        $this->delTree($this->getOption('directory'));
        $this->initDirectories();
    }

    /**
     * Remove a directory
     *
     * @param string $dir The path of the directory to remove
     * @return bool
     */
    public function delTree($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            $newPath = $dir . DIRECTORY_SEPARATOR . $file;
            (is_dir($newPath)) ? $this->delTree($newPath) : unlink($newPath);
        }
        return rmdir($dir);
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

        $path = $this->getKeyPath($key);

        if (!file_exists($path) && !is_file($path)) {
            return false;
        }

        $expire = $this->loadExpire($key);

        if (false === $expire) {
            return 0;
        }
        return $expire - time();
    }
}
