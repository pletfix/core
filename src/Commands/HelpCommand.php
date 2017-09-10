<?php

namespace Core\Commands;

use Core\Application;
use Core\Services\Command;
use Core\Services\DI;
use Core\Services\Stdio;

/**
 * Class HelpCommand
 *
 * Function findAlternatives() is copied from Symfony's Console Application.
 *
 * @see https://github.com/symfony/console/blob/3.2/Application.php
 */
class HelpCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'help';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display help for a command.';

    /**
     * Possible arguments of the command.
     *
     * @var array
     */
    protected $arguments = [
        'name' => ['type' => 'string', 'default' => null, 'description' => 'The name of the command.'],
    ];

    /**
     * Possible options of the command.
     *
     * @var array
     */
    protected $options = [
        'version' => ['type' => 'bool', 'short' => 'V', 'description' => 'Display the application version.'],
    ];

    /**
     * @inheritdoc
     */
    protected function handle()
    {
        // Has the "version" option been entered?
        if ($this->input('version')) {
            $this->line('Pletfix ' . Application::version());

            return self::EXIT_SUCCESS;
        }

        $list = DI::getInstance()->get('command-factory')->commandList();

        // Was a command name entered?
        if ($this->input('name') !== null) {

            // Does the command name exists?
            $name = $this->input('name');
            if (!isset($list[$name])) {

                // Suggest an alternative to the command name...
                $message = 'Command "' . $name . '" is not defined.';
                if ($alternatives = $this->findAlternatives($name, $list)) {
                    $message .= PHP_EOL . (count($alternatives) == 1 ? 'Did you mean this?' : 'Did you mean one of these?') .
                        PHP_EOL . '  - ' . implode(PHP_EOL . '  - ', $alternatives);
                }
                $this->error($message);

                return self::EXIT_FAILURE;
            }

            // Display help for the given command.

            /** @var Command $command */
            $class = $list[$name]['class'];
            $command = new $class(null, $this->stdio());
            $command->printHelp();

            return self::EXIT_SUCCESS;
        }

        // No name was given, so list all available commands!

        $this->notice('Help:');
        $this->line('  Command tool for Pletfix.');
        $this->line('');

        $this->notice('Usage:');
        $this->line('  php console help <command>');
        $this->line('  php console <command> -h');
        $this->line('');

        $this->notice('Available commands:');
        $commands = [];
        $length = 0;
        foreach ($list as $name => $item) {
            $commands[$name] = $item['description'];
            $n = strlen($name);
            if ($length < $n) {
                $length = $n;
            }
        }

        $oldGroup = '';
        foreach ($commands as $name => $description) {
            $group = ($pos = strpos($name, ':')) !== false ? substr($name, 0, $pos) : $name;
            if ($group != $oldGroup) {
                $this->notice(' ' . $group);
                $oldGroup = $group;
            }
            $name .= str_repeat(' ', 2 + $length - strlen($name));
            $this->write('  ' . $name, false, [Stdio::STYLE_GREEN]);
            $this->write($description, true);
        }

        return self::EXIT_SUCCESS;
    }

    /**
     * Finds alternative of $name among $collection,
     * if nothing is found in $collection, try in $abbrevs.
     *
     * @param string $search
     * @param array $list
     * @return string[] A sorted array of similar string
     */
    private function findAlternatives($search, $list)
    {
        $threshold = 1e3;
        $alternatives = [];

        $collectionParts = [];
        foreach ($list as $name => $item) {
            $collectionParts[$name] = explode(':', $name);
        }

        foreach (explode(':', $search) as $i => $subname) {
            foreach ($collectionParts as $name => $parts) {
                $exists = isset($alternatives[$name]);
                if (!isset($parts[$i])) {
                    if ($exists) {
                        $alternatives[$name] += $threshold;
                    }
                    continue;
                }
                $lev = levenshtein($subname, $parts[$i]);
                if ($lev <= strlen($subname) / 3 || $subname !== '' && strpos($parts[$i], $subname) !== false) {
                    $alternatives[$name] = $exists ? $alternatives[$name] + $lev : $lev;
                }
                else if ($exists) {
                    $alternatives[$name] += $threshold;
                }
            }
        }

        foreach ($list as $name => $item) {
            $lev = levenshtein($search, $name);
            if ($lev <= strlen($search) / 3 || strpos($name, $search) !== false) {
                $alternatives[$name] = isset($alternatives[$name]) ? $alternatives[$name] - $lev : $lev;
            }
        }

        $alternatives = array_filter($alternatives, function ($lev) use ($threshold) {
            return $lev < 2 * $threshold;
        });

        ksort($alternatives);

        return array_keys($alternatives);
    }
}