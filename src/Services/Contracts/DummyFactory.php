<?php

namespace Core\Services\Contracts;

interface DummyFactory
{
    /**
     * Get a Dummy instance by given store name.
     *
     * @param string|null $name
     * @return \Core\Services\Contracts\Auth
     */
    public function store($name = null);
}
