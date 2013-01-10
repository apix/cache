<?php
namespace Apix\Cache;

/**
 * APC wrapper with emulated tag support.
 */
class Apc extends AbstractCache
{

    /**
     * Constructor.
     */
    public function __construct(array $options=array())
    {
        parent::__construct(null, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function load($key, $type='key')
    {
        $key = $type == 'tag' ? $this->mapTag($key) : $this->mapKey($key);
        $cached = apc_fetch($key, $success);

        return false === $success ? null : $cached;
    }

    /**
     * {@inheritdoc}
     *
     * APC does not support natively cache-tags so we simulate them.
     */
    public function save($data, $key, array $tags=null, $ttl=null)
    {
        $key = $this->mapKey($key);
        $store = array();
        if ($this->options['tag_enable'] && !empty($tags)) {
            foreach ($tags as $tag) {
                $tag = $this->mapTag($tag);
                $keys = apc_fetch($tag, $success);
                if (false === $success) {
                    $store[$tag] = array($key);
                } else {
                    $keys[] = $key;
                    $store[$tag] = array_unique($keys);
                }
            }
        }
        $store[$key] = $data;

        return !in_array(false, apc_store($store, null, $ttl));
    }

    /**
     * {@inheritdoc}
     *
     * APC does not support natively cache-tags so we simulate them.
     */
    public function delete($key)
    {
        $key = $this->mapKey($key);

        if ($success = apc_delete($key) && $this->options['tag_enable']) {

            $iterator = $this->iterator(
                '/^' . preg_quote($this->options['prefix_tag']) . '/',
                APC_ITER_VALUE
            );
            foreach ($iterator as $tag => $keys) {
                if ( ($key = array_search($key, $keys['value'])) !== false ) {
                    unset($keys['value'][$key]);
                    if (empty($keys['value'])) {
                        apc_delete($tag);
                    } else {
                        apc_store($tag, $keys['value']);
                    }
                }
                continue;
            }
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     *
     * APC does not support natively cache-tags so we simulate them.
     */
    public function clean(array $tags)
    {
        $rmed = array();
        foreach ($tags as $tag) {
            $tag = $this->mapTag($tag);
            $keys = apc_fetch($tag, $success);
            if ($success) {
                foreach ($keys as $key) {
                    $rmed[] = apc_delete($key);
                }
                $rmed[] = apc_delete($tag);
            }
        }

        return !in_array(false, $rmed);
    }

    /**
     * {@inheritdoc}
     *
     * APC does not support natively cache-tags so we simulate them.
     */
    public function flush($all=false)
    {
        if (true === $all) {
            return apc_clear_cache('user');
        }

        $iterator = $this->iterator(
            '/^' . preg_quote($this->options['prefix_key'])
            .'|' . preg_quote($this->options['prefix_tag']) . '/',
            APC_ITER_KEY
        );

        $rmed = array();
        foreach ($iterator as $key => $data) {
            $rmed[] = apc_delete($key);
        }

        return !in_array(false, $rmed);
    }

    protected function iterator($search=null, $format=APC_ITER_ALL)
    {
        return new \APCIterator('user', $search, $format, 100, APC_LIST_ACTIVE);
    }

    /**
     * Returns some internal informations about a APC cached item.
     *
     * @return array|false
     */
    public function getInternalInfos($key)
    {
        $iterator = $this->iterator(
            '/^' . preg_quote($this->options['prefix_key']) . '/'
        );

        $key = $this->mapKey($key);
        foreach ($iterator as $k => $v) {
            if ($k != $key)
                continue;

            return $v;
        }

        return false;
    }

}
