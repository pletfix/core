<?php

namespace Core\Services\Contracts;

interface DateTime
{
    /**
     * Create a new DateTime instance.
     *
     * @param string $dateTime
     */
    public function __construct($dateTime = 'now');
}
