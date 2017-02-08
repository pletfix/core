<?php

namespace Core\Services\Contracts;

interface CommandFactory
{
    /**
     * Get a Command instance by given command name.
     *
     * If the command not exists, a helper message will be prompted and false will be returned.
     *
     * @param array $argv Command line parameters (without application name)
     * @return Command
     */
    public function command($argv);

    /**
     * Get the command list.
     *
     * @return array
     */
    public function commandList();
}
