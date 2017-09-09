<?php

namespace Core\Testing;

use Core\Services\DI;

class Environment
{
    /**
     * Base path of the application.
     *
     * @var string
     */
    protected static $basePath = BASE_PATH;

    /**
     * Load the test environment.
     */
    public static function load()
    {
        /*
         * Push the Services into the Dependency Injector.
         */
        call_user_func(function() {
            /** @noinspection PhpIncludeInspection */
            @include self::$basePath . '/.manifest/plugins/services.php';
            /** @noinspection PhpIncludeInspection */
            require self::$basePath . '/config/boot/services.php';
        });

        /*
         * Bootstrap the framework.
         */
        call_user_func(function() {
            /** @noinspection PhpIncludeInspection */
            require self::$basePath . '/config/boot/bootstrap.php';
            /** @noinspection PhpIncludeInspection */
            @include self::$basePath . '/.manifest/plugins/bootstrap.php';
        });

        /*
         * Set configuration.
         */
        DI::getInstance()->get('config')->set('app.env', env('APP_ENV'));
    }
}