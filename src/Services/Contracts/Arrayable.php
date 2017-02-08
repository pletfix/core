<?php

namespace Core\Services\Contracts;

/*
 * This code based on Laravel's Collection 5.3 (see copyright notice license-laravel.md)
 */

interface Arrayable
{
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray();
}