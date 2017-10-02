<?php

namespace Core\Testing;

use Core\Application;
use Core\Services\DI;

class Environment
{
    /**
     * Load the test environment.
     */
    public static function load()
    {
        Application::init();

        /*
         * Override configuration with the environment entry of phpunit.xml.
         */
        DI::getInstance()->get('config')->set('app.env', env('APP_ENV'));
    }
}