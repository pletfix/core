<?php

namespace Core\Services\Contracts;

interface CacheFactory
{
    /**
     * Get a Cache instance by given store name.
     *
     * @param string|null $name
     * @return \Core\Services\Contracts\Cache
     */
    public function store($name = null);
}
