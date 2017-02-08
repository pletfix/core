<?php

namespace Core\Services\Contracts;

/*
 * This code based on Laravel's Collection 5.3 (see copyright notice license-laravel.md)
 */

interface Jsonable
{
    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0);
}
