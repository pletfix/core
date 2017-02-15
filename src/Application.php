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
    const VERSION = '0.0.2';

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
            require __DIR__ . '/../../../../config/boot/services.php';
            @include __DIR__ . '/../../../../.manifest/plugins/services.php';
        });

//        echo 'include: ';
//        benchmark(function ($loop) {
//            require __DIR__ . '/../services.php';
//        }, 1);
//        echo 'serilize: ';
//        benchmark(function ($loop) {
//            Core\Services\DI::loadFromCache();
//        }, 1);
//        exit(1);

        /*
         * Bootstrap the framework
         */
        call_user_func(function() {
            require __DIR__ . '/../../../../config/boot/bootstrap.php';
            @include __DIR__ . '/../../../../.manifest/plugins/bootstrap.php';
        });

//        echo 'Load Bootstrap: ';
//        benchmark(function ($loop) {
//            (new Bootstraps\Dummy)->bootstrap();
//        }, 1000);
//
//        echo 'Load Bootstrap static: ';
//        benchmark(function ($loop) {
//            Bootstraps\Dummy::bootstrap2();
//        }, 1000);

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
     * @return \Core\Services\Route
     */
    public static function route()
    {
        return DI::getInstance()->get('route');
    }
}