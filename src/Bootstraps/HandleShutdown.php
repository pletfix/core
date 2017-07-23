<?php

namespace Core\Bootstraps;

use Core\Services\DI;
use Core\Bootstraps\Contracts\Bootable;

class HandleShutdown implements Bootable
{
    /**
     * Bootstrap
     *
     * @codeCoverageIgnore
     */
    public function boot()
    {
        register_shutdown_function([$this, 'handle']);
    }

    public function handle()
    {
        DI::getInstance()->get('shutdown-handler')->handle();
    }
}
