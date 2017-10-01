<?php

namespace Core;

use Core\Bootstraps\AgeFlash;
use Core\Bootstraps\HandleExceptions;
use Core\Bootstraps\HandleShutdown;
use Core\Bootstraps\LoadConfiguration;
use Core\Handler\ExceptionHandler;
use Core\Handler\ShutdownHandler;
use Core\Services\AssetManager;
use Core\Services\Auth;
use Core\Services\CacheFactory;
use Core\Services\Collection;
use Core\Services\CommandFactory;
use Core\Services\Config;
use Core\Services\Cookie;
use Core\Services\DatabaseFactory;
use Core\Services\DateTime;
use Core\Services\Delegate;
use Core\Services\DI;
use Core\Services\Flash;
use Core\Services\Logger;
use Core\Services\Mailer;
use Core\Services\Migrator;
use Core\Services\Paginator;
use Core\Services\PluginManager;
use Core\Services\Request;
use Core\Services\Response;
use Core\Services\Router;
use Core\Services\Session;
use Core\Services\Stdio;
use Core\Services\Translator;
use Core\Services\View;
use Core\Services\ViewCompiler;

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

        if (file_exists(self::$basePath . '/.manifest/plugins/services.php')) {
            call_user_func(function () {
                /** @noinspection PhpIncludeInspection */
                @include self::$basePath . '/.manifest/plugins/services.php';
            });
        }

        static::addServices(DI::getInstance());

        /*
         * Bootstrap the framework.
         */

        static::bootstrap();

        if (file_exists(self::$basePath . '/.manifest/plugins/bootstrap.php')) {
            call_user_func(function () {
                /** @noinspection PhpIncludeInspection */
                @include self::$basePath . '/.manifest/plugins/bootstrap.php';
            });
        }
    }

    /**
     * Start the application.
     *
     * @return int Exit Status
     */
    public static function run()
    {
        self::init();

        if (PHP_SAPI == 'cli') {

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

        /*
         * Register routes.
         */

        /** @var \Core\Services\Contracts\Router $router */
        $router = DI::getInstance()->get('router');

        static::addRoutes($router);

        if (file_exists(self::$basePath . '/.manifest/plugins/routes.php')) {
            call_user_func(function () {
                /** @noinspection PhpIncludeInspection */
                @include self::$basePath . '/.manifest/plugins/routes.php';
            });
        }

        /*
         * Dispatch the HTTP request and send the response to the browser.
         */

        $request = DI::getInstance()->get('request');
        $response = $router->dispatch($request);
        $response->send();

        return 0;
    }

    /**
     * Push the Services into the Dependency Injector.
     *
     * @param \Core\Services\Contracts\DI $di
     */
    protected static function addServices($di)
    {
        /*
         * Multiple Instance (not shared)
         */

        $di->set('collection',        Collection::class, false);
        $di->set('date-time',         DateTime::class, false);
        $di->set('mailer',            Mailer::class, false);
        $di->set('migrator',          Migrator::class, false);
        $di->set('paginator',         Paginator::class, false);
        $di->set('plugin-manager',    PluginManager::class, false);
        $di->set('view',              View::class, false);
        $di->set('view-compiler',     ViewCompiler::class, false);

        /*
         * Singleton Instance (shared)
         */

        $di->set('asset-manager',     AssetManager::class, true);
        $di->set('auth',              Auth::class, true);
        $di->set('cache-factory',     CacheFactory::class, true);
        $di->set('command-factory',   CommandFactory::class, true);
        $di->set('config',            Config::class, true);
        $di->set('cookie',            Cookie::class, true);
        $di->set('database-factory',  DatabaseFactory::class, true);
        $di->set('delegate',          Delegate::class, true);
        $di->set('exception-handler', ExceptionHandler::class, true);
        $di->set('flash',             Flash::class, true);
        $di->set('logger',            Logger::class, true);
        $di->set('request',           Request::class, true);
        $di->set('response',          Response::class, true);
        $di->set('router',            Router::class, true);
        $di->set('session',           Session::class, true);
        $di->set('shutdown-handler',  ShutdownHandler::class, true);
        $di->set('stdio',             Stdio::class, true);
        $di->set('translator',        Translator::class, true);
    }

    /**
     * Whatever code you want to run on startup.
     */
    protected static function bootstrap()
    {
        (new LoadConfiguration)->boot();
        (new HandleExceptions)->boot();
        (new HandleShutdown)->boot();
        (new AgeFlash)->boot();
    }

    /**
     * Define the routes.
     *
     * @param \Core\Services\Contracts\Router $router
     */
    protected static function addRoutes($router)
    {
    }
}