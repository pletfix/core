<?php

namespace Core\Services\Contracts;

interface Cache
{
    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Puts data into the cache.
     *
     * If a cache entry with the given id already exists, its data will be replaced.
     *
     * If the lifetime equal zero (the default), the entry never expires (although it may be deleted from the cache
     * to make place for other entries).
     *
     * @param string $key
     * @param mixed $value
     * @param float|int $minutes The lifetime in number of minutes for this cache entry.
     * @return $this
     */
    public function set($key, $value, $minutes = 0);

    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $key
     * @return bool
     */
    public function has($key);

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return $this
     */
    public function delete($key);

    /**
     * Remove all items from the cache.
     *
     * @return $this
     */
    public function clear();
}
