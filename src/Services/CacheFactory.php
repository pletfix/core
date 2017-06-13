<?php

namespace Core\Services;

use Core\Services\Contracts\CacheFactory as CacheFactoryContract;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\RedisCache;
use InvalidArgumentException;
use Memcached;
use Redis;

/**
 * Cache Factory
 *
 * Supported Driver:
 * - "apc"          (requires ext/apc)
 * - "array"        (in memory, lifetime of the request)
 * - "file"         (not optimal for high concurrency)
 * - "memcached"    (requires ext/memcached)
 * - "redis"        (requires ext/phpredis)
 *
 * @see https://github.com/doctrine/cache Doctrine Cache on GitHub
 */
class CacheFactory implements CacheFactoryContract
{
    /**
     * Instances of Cache.
     *
     * @var \Core\Services\Contracts\Cache[]
     */
    private $caches = [];

    /**
     * Name of the default store.
     *
     * @var string
     */
    private $defaultStore;

    /**
     * Create a new factory instance.
     */
    public function __construct()
    {
        $this->defaultStore = config('cache.default');
    }

    /**
     * Get a Cache instance by given store name.
     *
     * @param string|null $name
     * @return \Core\Services\Contracts\Cache
     * @throws \InvalidArgumentException
     */
    public function store($name = null)
    {
        if (is_null($name)) {
            $name = $this->defaultStore;
        }

        if (isset($this->caches[$name])) {
            return $this->caches[$name];
        }

        $config = config('cache.stores.' . $name);
        if (is_null($config)) {
            throw new InvalidArgumentException('Cache store "' . $name . '" is not defined.');
        }

        if (!isset($config['driver'])) {
            throw new InvalidArgumentException('Cache driver for store "' . $name . '" is not specified.');
        }

        // todo dynamisch gestalten, driver evtl auch im Plugins suchen
        switch ($config['driver']) { // todo use class name such like "Apcu"
            case 'apc':
                $provider = new ApcuCache;
                break;
            case 'array':
                $provider = new ArrayCache;
                break;
            case 'file':
                $provider = new FilesystemCache($config['path']);
                break;
            case 'memcached':
                $memcached = new Memcached;
                $memcached->addServer($config['host'], $config['port'], $config['weight']);
                $provider = new MemcachedCache();
                $provider->setMemcached($memcached);
                break;
            case 'redis':
                $redis = new Redis;
                $redis->connect($config['host'], $config['port'], $config['timeout']);
                $provider = new RedisCache;
                $provider->setRedis($redis);
                break;
            default:
                throw new InvalidArgumentException('Cache driver "' . $config['driver'] . '" is not supported.');
        }

        return $this->caches[$name] = new Cache($provider);
    }
}