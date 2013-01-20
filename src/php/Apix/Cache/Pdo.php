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
 * PDO cache wrapper with tag support.
 *
 * @author Franck Cassedanne <franck at ouarz.net>
 */
class Pdo extends AbstractCache
{

    /**
     * Constructor.
     *
     * @param \PDO  $pdo
     * @param array $options Array of options.
     */
    public function __construct(\PDO $pdo, array $options=null)
    {
        // default options
        $this->options['cache_table'] = 'cache';
        $this->options['tag_table'] = 'tag';
        $this->options['serializer'] = 'php'; // none, php, igBinary, json.

        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        parent::__construct($pdo, $options);
        $this->setSerializer($this->options['serializer']);

        $this->init($this->options['cache_table'], $this->options['tag_table']);
    }

    /**
     * Initialises the database.
     */
    protected function init($cache, $tag=null)
    {
        $this->adapter->exec(
                'CREATE TABLE IF NOT EXISTS '
                . $cache . ' (
                id VARCHAR(255) PRIMARY KEY,
                data LONGTEXT,
                tags TEXT,
                expire UNSIGNED INTEGER,
                created UNSIGNED INTEGER)'
            );

        // if (null !== $tag) {
        //     $this->adapter->exec(
        //         'CREATE TABLE IF NOT EXISTS '
        //         . $tag . ' (
        //         id VARCHAR(255) PRIMARY KEY,
        //         tag TEXT,
        //         cache_id VARCHAR(255),
        //         expire UNSIGNED INTEGER,
        //         created UNSIGNED INTEGER)'
        //     );
        // }

        // clear expired
        // $now = time();
        // $this->pdo->query("DELETE FROM cache WHERE timeout <= $now");
    }

    /**
     * {@inheritdoc}
     */
    public function load($key, $type='key')
    {
        $data = $type == 'tag'
                ? $this->loadFromTag($key)
                : $this->loadFromKey($this->mapKey($key));

        return null === $data ? null : $data;
    }

    public function loadFromKey($key)
    {
        $sql = 'SELECT data FROM cache WHERE id=:key AND (expire IS NULL OR expire > :now)';
        $prep = $this->adapter->prepare($sql);
        $prep->execute(array('key' => $key, 'now' => time()));
        $cached = $prep->fetch();

        if (null !== $cached['data'] && null !== $this->serializer) {
            return $this->serializer->unserialize($cached['data']);
        }

        return false === $cached ? null : $cached['data'];
    }

    public function loadFromTag($tag)
    {
        // Two tables
        // $sql = 'SELECT cache_id FROM tag WHERE tag = :tag AND (expire IS NULL OR expire > :now)';
        $sql = 'SELECT id FROM cache WHERE tags LIKE :tag AND (expire IS NULL OR expire > :now)';
        $prep = $this->adapter->prepare($sql);
        $prep->execute(array('tag' => "%$tag%", 'now' => time()));
        $cached = $prep->fetchAll();

        $keys = array();
        foreach ($cached as $v) {
            $keys[] = $v['id'];
        }

        return empty($keys) ? null : $keys;
    }

    /**
     * {@inheritdoc}
     */
    public function save($data, $key, array $tags=null, $ttl=null)
    {
        $values = array(
            'key'  => $this->mapKey($key),
            'data' => null !== $this->serializer
                        ? $this->serializer->serialize($data)
                        : $data,
            'tags' => null !== $tags ? implode(', ', $tags) : null,
            'exp'  => null !== $ttl && 0 !== $ttl ? time()+$ttl : null
        );

        // do a upsert (try update first then insert)
        $prep = $this->adapter->prepare(
            'UPDATE cache SET data=:data, tags=:tags, expire=:exp WHERE id=:key'
        );
        // echo $prep->debugDumpParams();
        $success = $prep->execute($values);

        if ($prep->rowCount() == 0) {
            $prep = $this->adapter->prepare(
                        'INSERT INTO cache (id, data, tags, expire)
                            VALUES (:key, :data, :tags, :exp)'
                    );
            $success = $prep->execute($values);
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $prep = $this->adapter->prepare('DELETE FROM cache WHERE id=:key');
        $success = $prep->execute(
            array(
                'key' => $this->mapKey($key)
            )
        );

        return (boolean) $prep->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function clean(array $tags)
    {
        $items = array();
        foreach ($tags as $tag) {
            // $tag = $this->mapTag($tag);
            $items[] = '%' . $tag . '%';
        }

        $_tags = implode(' OR ', array_fill(0, count($tags), 'tags LIKE ?'));
        $prep = $this->adapter->prepare(
            'DELETE FROM cache WHERE ' . $_tags
        );
        $prep->execute($items);

        /*
            // when using TWO tables
            $prep = $this->adapter->prepare(
                'DELETE
                 FROM table
                 WHERE tag IN(' . implode(',', array_fill(0, count($tags), '?')) . ')'
            );
            $prep->execute($tags);
        */

        return (boolean) $prep->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function flush($all=false)
    {
        if (true === $all) {
            return (boolean) $this->adapter->exec('DROP TABLE IF EXISTS cache');
        }

        return (boolean) $this->adapter->exec('DELETE FROM cache');
    }

}
