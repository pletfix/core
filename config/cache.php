<?php

return [

    /**
     * ----------------------------------------------------------------
     * Default Cache Store
     * ----------------------------------------------------------------
     *
     * This option controls the default cache connection that gets used while
     * using this caching library. This connection is used when another is
     * not explicitly specified when executing a given caching function.
     */

    'default' => env('CACHE_STORE', 'file'),

    /**
     * ----------------------------------------------------------------
     * Cache Stores
     * ----------------------------------------------------------------
     *
     * Here you may define all of the cache "stores" for your application as
     * well as their drivers. You may even define multiple stores for the
     * same cache driver to group types of items stored in your caches.
     *
     * Supported Driver:
     * - APCu         (requires ext/apc)
     * - Array        (in memory, lifetime of the request)
     * - File         (not optimal for high concurrency)
     * - Memcached    (requires ext/memcached)
     * - Redis        (requires ext/phpredis)
     */

    'stores' => [

        'apc' => [
            'driver' => 'APCu',
        ],

        'array' => [
            'driver' => 'Array',
        ],

        'file' => [
            'driver' => 'File',
            'path'   => storage_path('cache/doctrine'),
        ],

        'memcached' => [
            'driver' => 'Memcached',
            'host'   => env('MEMCACHED_HOST', '127.0.0.1'),
            'port'   => env('MEMCACHED_PORT', 11211),
            'weight' => 100,
        ],

        'redis' => [
            'driver'  => 'Redis',
            'host'    => env('REDIS_HOST', '127.0.0.1'),
            'port'    => env('REDIS_PORT', 6379),
            'timeout' => 0.0,
        ],

    ],

];