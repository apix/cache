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

    protected $sql_definitions = array(
        'init' => 'CREATE TABLE IF NOT EXISTS %s (key VARCHAR(255) PRIMARY KEY,
                  data LONGTEXT, tags TEXT, expire UNSIGNED INTEGER,
                  created UNSIGNED INTEGER)',
        'key_idx' => 'CREATE INDEX IF NOT EXISTS %s_key_idx ON %s (key)',
        'exp_idx' => 'CREATE INDEX IF NOT EXISTS %s_exp_idx ON %s (expire)',
        'tag_idx' => 'CREATE INDEX IF NOT EXISTS %s_tag_idx ON %s (tags)',
        'loadKey' => 'SELECT data FROM %s WHERE key=:key AND
                      (expire IS NULL OR expire > :now)',
        'loadTag' => 'SELECT key FROM %s WHERE tags LIKE :tag AND
                      (expire IS NULL OR expire > :now)',
        'update' => 'UPDATE %s SET data=:data, tags=:tags, expire=:exp
                     WHERE key=:key',
        'insert' => 'INSERT INTO %s (key, data, tags, expire)
                     VALUES (:key, :data, :tags, :exp)',
        'delete' => 'DELETE FROM %s WHERE key = ?',
        'clean' => 'DELETE FROM %s WHERE %s',
        'flush_all' => 'DROP TABLE IF EXISTS %s',
        'flush' =>  'DELETE FROM %s',
        'purge' => 'DELETE FROM %s WHERE expire IS NOT NULL AND expire < %d'
    );

    /**
     * Constructor.
     *
     * @param \PDO  $pdo
     * @param array $options Array of options.
     */
    public function __construct(\PDO $pdo, array $options=null)
    {
        // default options
        $this->options['db_name'] = 'apix_cache';
        $this->options['db_table'] = 'cache';

        $this->options['serializer'] = 'php'; // none, php, igBinary, json.

        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        parent::__construct($pdo, $options);
        $this->setSerializer($this->options['serializer']);

        // Initialises the database.
        $this->adapter->exec( $this->getSql('init') );
        $this->adapter->exec(
            $this->getSql('key_idx', $this->options['db_table'])
        );
        $this->adapter->exec(
            $this->getSql('exp_idx', $this->options['db_table'])
        );
        $this->adapter->exec(
            $this->getSql('tag_idx', $this->options['db_table'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function load($key, $type='key')
    {
        return $type == 'key'
                ? $this->loadKey($this->mapKey($key))
                : $this->loadTag($key);
    }

    /**
     * Retrieves the cache for the given key.
     *
     * @param  string     $key The cache key to retrieve.
     * @return mixed|null Returns the cached data or null.
     */
    protected function loadKey($key)
    {
        $sql = $this->getSql('loadKey');
        $values = array('key' => $key, 'now' => time());

        $cached = $this->exec($sql, $values)->fetch();

        if (null !== $cached['data'] && null !== $this->serializer) {
            return $this->serializer->unserialize($cached['data']);
        }

        return false === $cached ? null : $cached['data'];
    }

    /**
     * Retrieves the cache keys for the given tag.
     *
     * @param  string     $tag The cache tag to retrieve.
     * @return array|null Returns an array of cache keys or null.
     */
    protected function loadTag($tag)
    {
        $sql = $this->getSql('loadTag');
        $values = array('tag' => "%$tag%", 'now' => time());

        $items = $this->exec($sql, $values)->fetchAll();

        $keys = array();
        foreach ($items as $item) {
            $keys[] = $item['key'];
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
            'exp'  => null !== $ttl && 0 !== $ttl ? time()+$ttl : null
        );

        $values['tags'] = $this->options['tag_enable'] && null !== $tags
                            ? implode(', ', $tags)
                            : null;

        // upsert
        $sql = $this->getSql('update');
        $nb = $this->exec($sql, $values)->rowCount();
        if ($nb == 0) {
            $sql = $this->getSql('insert');
            $nb = $this->exec($sql, $values)->rowCount();
        }

        return (boolean) $nb;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $sql = $this->getSql('delete');
        $values = array($this->mapKey($key));

        return (boolean) $this->exec($sql, $values)->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function clean(array $tags)
    {
        $values = array();
        foreach ($tags as $tag) {
            // $tag = $this->mapTag($tag);
            $values[] = '%' . $tag . '%';
        }

        $sql = $this->getSql(
            'clean', implode(' OR ', array_fill(0, count($tags), 'tags LIKE ?'))
        );

        return (boolean) $this->exec($sql, $values)->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function flush($all=false)
    {
        if (true === $all) {
            return (boolean) $this->adapter->exec( $this->getSql('flush_all') );
        }

        return (boolean) $this->adapter->exec( $this->getSql('flush') );
    }

    /**
     * Purges expired items.
     *
     * @param  integer|null $add Extra time in second to add.
     * @return boolean      Returns True on success or False on failure.
     */
    public function purge($add=null)
    {
        $time = null == $add ? time() : time()+$add;

        return (boolean) $this->adapter->exec( $this->getSql('purge', $time) );
    }

    /**
     * Gets the named SQL definition.
     *
     * @param  string         $key
     * @param  string|integer $value An additional value.
     * @return string
     */
    protected function getSql($key, $value=null)
    {
        return sprintf(
            $this->sql_definitions[$key],
            $this->options['db_table'],
            $value
        );
    }

    /**
     * Prepares and executes a SQL query.
     *
     * @param  string        $sql    The SQL to prepare.
     * @param  array         $values The values to execute.
     * @return \PDOStatement
     */
    protected function exec($sql, array $values)
    {
        $prep = $this->adapter->prepare($sql);
        $prep->execute($values);

        return $prep;
    }

}
