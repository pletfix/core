<?php

namespace Core;

use Core\Services\DI;

class Application
{
    /**
     * The framework version.
     *
     * @var string
     */
    const VERSION = '0.7.2';

    /**
     * Base path of the application.
     *
     * @var string
     */
    protected static $basePath = BASE_PATH;

    /**
     * Get the version number of the framework.
     *
     * @return string
     */
    public static function version()
    {
        return static::VERSION;
    }

    /**
     * Start the application.
     */
    public static function run()
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
         * Register routes.
         */
        call_user_func(function() {
            /** @noinspection PhpIncludeInspection */
            require self::$basePath . '/config/boot/routes.php';
            /** @noinspection PhpIncludeInspection */
            @include self::$basePath . '/.manifest/plugins/routes.php';
        });

        /*
         * Dispatch the HTTP request and send the response to the browser.
         */
        $request = DI::getInstance()->get('request');
        $response = static::route()->dispatch($request);
        $response->send();
    }

    /**
     * Get the Route Service.
     *
     * @return \Core\Services\Contracts\Route
     */
    public static function route()
    {
        $r = DI::getInstance()->get('route');
        return $r;
    }
}