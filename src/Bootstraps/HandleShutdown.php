<?php

namespace Core\Bootstraps;

use Core\Services\DI;
use Core\Bootstraps\Contracts\Bootable;

class HandleShutdown implements Bootable
{
    /**
     * Bootstrap
     */
    public function boot()
    {
        register_shutdown_function(function() {
            DI::getInstance()->get('shutdown-handler')->handle();
        });
    }
}
