<?php

namespace Core;

use Core\Services\DI;

/**
 * Console is the main entry point of a console application.
 */
class Console
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
     * Start the command.
     *
     * @return int Exit Status
     */
    public static function run()
    {
        /*
         * Push the Services into the Dependency Injector.
         */
        call_user_func(function() {
            require __DIR__ . '/../../../../config/boot/services.php';
            @include __DIR__ . '/../plugins/services.php';
        });

        /*
         * Bootstrap the framework
         */
        call_user_func(function() {
            require __DIR__ . '/../../../../config/boot/bootstrap.php';
            @include __DIR__ . '/../plugins/bootstrap.php';
        });

        /*
         * Get the command line parameters
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
}