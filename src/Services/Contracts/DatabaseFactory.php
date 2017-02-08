<?php

namespace Core\Services\Contracts;

interface DatabaseFactory
{
    /**
     * Get a Database instance by given store name.
     *
     * @param string|null $name
     * @return \Core\Services\Contracts\Database
     */
    public function store($name = null);
}
