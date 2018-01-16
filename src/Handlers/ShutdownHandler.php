<?php

namespace Core\Handlers;

use Core\Handlers\Contracts\Handler as HandlerContract;

class ShutdownHandler implements HandlerContract
{
    /**
     * Handle the PHP shutdown event.
     *
     * @return void
     */
    public function handle()
    {
//        echo 'Shutdown...';
    }
}
