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
     * @inheritdoc
     */
    public function get($key, $default = null)
    {
        $value = $this->provider->fetch($key);

        return $value !== false ? $value : $default;
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value, $minutes = 0)
    {
        $this->provider->save($key, $value, $minutes * 60);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        return $this->provider->contains($key);
    }

    /**
     * @inheritdoc
     */
    public function delete($key)
    {
        $this->provider->delete($key);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->provider->deleteAll();

        return $this;
    }
}