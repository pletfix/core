<?php

namespace Core\Bootstraps;

use Core\Services\DI;
use Core\Bootstraps\Contracts\Bootable;

class AgeFlash implements Bootable
{
    /**
     * Bootstrap
     */
    public function boot()
    {
//        register_shutdown_function(function() {
            DI::getInstance()->get('flash')->age();
//        });
    }
}
