<?php

namespace Core\Handlers\Contracts;

interface Handler
{
    /**
     * Handle the PHP shutdown event.
     */
    public function handle();
}
