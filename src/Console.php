<?php

namespace Core;

use Core\Services\DI;

class Console extends Framework
{
    /**
     * Start the command.
     *
     * @return int Exit Status
     */
    public static function run()
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
}