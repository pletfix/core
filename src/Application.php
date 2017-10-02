<?php

namespace Core;

use Core\Services\DI;

class Application
{
    /**
     * The Pletfix core version.
     *
     * @var string
     */
    const VERSION = '0.7.3';

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
     * Initialize the application.
     */
    public static function init()
    {
        /*
         * Push the Services into the Dependency Injector.
         */
        call_user_func(function() {
            if (file_exists(self::$basePath . '/.manifest/plugins/services.php')) {
                /** @noinspection PhpIncludeInspection */
                @include self::$basePath . '/.manifest/plugins/services.php';
            }
            /** @noinspection PhpIncludeInspection */
            require self::$basePath . '/boot/services.php';
        });

        /*
         * Bootstrap the framework.
         */
        call_user_func(function() {
            /** @noinspection PhpIncludeInspection */
            require self::$basePath . '/boot/bootstrap.php';
            if (file_exists(self::$basePath . '/.manifest/plugins/bootstrap.php')) {
                /** @noinspection PhpIncludeInspection */
                @include self::$basePath . '/.manifest/plugins/bootstrap.php';
            }
        });
    }

    /**
     * Start the application.
     */
    public static function run()
    {
        self::init();

        /*
         * Register routes.
         */
        call_user_func(function () {
            /** @noinspection PhpIncludeInspection */
            require self::$basePath . '/boot/routes.php';
            if (file_exists(self::$basePath . '/.manifest/plugins/routes.php')) {
                /** @noinspection PhpIncludeInspection */
                @include self::$basePath . '/.manifest/plugins/routes.php';
            }
        });

        /*
         * Dispatch the HTTP request and send the response to the browser.
         */
        /** @var \Core\Services\Contracts\Router $router */
        $router   = DI::getInstance()->get('router');
        $request  = DI::getInstance()->get('request');
        $response = $router->dispatch($request);
        $response->send();
    }

    /**
     * Start a console.
     *
     * @return int Exit Status
     */
    public static function console()
    {
        self::init();

        /*
         * Get the command line parameters.
         */
        $argv = $_SERVER['argv'];
        array_shift($argv); // strip the application name ("console")

        /*
         * Dispatch the command line request.
         */
        /** @var \Core\Services\Contracts\Command|false $command */
        $command = DI::getInstance()->get('command-factory')->command($argv);
        $status = $command->run();

        return $status;
    }

    /**
     * Get the Route Service.
     *
     * @return \Core\Services\Contracts\Router
     */
    public static function router()
    {
        return DI::getInstance()->get('router');
    }
}