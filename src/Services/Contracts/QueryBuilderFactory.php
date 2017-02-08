<?php

namespace Core\Services\Contracts;

interface QueryBuilderFactory
{
    /**
     * Get a QueryBuilder instance by given database store name.
     *
     * @param string|null $name Name of Database store which the QueryBuilder is for
     * @return \Core\Services\Contracts\QueryBuilder
     */
    public function store($name = null);
}
