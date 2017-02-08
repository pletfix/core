<?php

namespace Core\Services;

use Core\Services\Contracts\Cache as CacheContract;
use Doctrine\Common\Cache\CacheProvider;

/**
 * Adapter for Doctrine's CacheProvider
 *
 * @see https://github.com/doctrine/cache Doctrine Cache on GitHub
 */
class Cache implements CacheContract
{
    /**
     * Instance of CacheProvider.
     *
     * @var CacheProvider
     */
    private $provider;

    /**
     * Create a new Cache instance.
     *
     * @param CacheProvider $provider
     */
    public function __construct(CacheProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $value = $this->provider->fetch($key);

        return $value !== false ? $value : $default;
    }

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
     */
    public function set($key, $value, $minutes = 0)
    {
        $this->provider->save($key, $value, ($minutes * 60));
    }

    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->provider->contains($key);
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     */
    public function delete($key)
    {
        $this->provider->delete($key);
    }

    /**
     * Remove all items from the cache.
     */
    public function clear()
    {
        $this->provider->deleteAll();
    }
}