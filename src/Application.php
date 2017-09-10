<?php

namespace Core;

use Core\Services\DI;

class Application extends Framework
{
    /**
     * Start the application.
     */
    public static function run()
    {
        self::init();

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
        return DI::getInstance()->get('route');
    }
}