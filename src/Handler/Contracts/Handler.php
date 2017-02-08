<?php

namespace Core\Handler\Contracts;

interface Handler
{
    /**
     * Handle the PHP shutdown event.
     */
    public function handle();
}
