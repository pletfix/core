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
    const VERSION = '0.6.1';

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
            @include __DIR__ . '/../../../../.manifest/plugins/services.php';
            require __DIR__ . '/../../../../config/boot/services.php';
        });

        /*
         * Bootstrap the framework
         */
        call_user_func(function() {
            require __DIR__ . '/../../../../config/boot/bootstrap.php';
            @include __DIR__ . '/../../../../.manifest/plugins/bootstrap.php';
        });

        /*
         * Register routes.
         */
        call_user_func(function() {
            require __DIR__ . '/../../../../config/boot/routes.php';
            @include __DIR__ . '/../../../../.manifest/plugins/routes.php';
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
        return DI::getInstance()->get('route');
    }
}