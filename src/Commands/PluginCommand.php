<?php

namespace Core\Commands;

use Core\Services\AbstractCommand;
use Core\Services\Contracts\Stdio;

class PluginCommand extends AbstractCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'plugin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register a plugin.';

    /**
     * Possible arguments of the command.
     *
     * @var array
     */
    protected $arguments = [
        'package' => ['type' => 'string', 'description' => 'Name of the plugin with vendor, e.g. foo/bar'],
    ];

    /**
     * Possible options of the command.
     *
     * @var array
     */
    protected $options = [
        'remove' => ['type' => 'bool', 'short' => 'r', 'description' => 'Remove the plugin.'],
        'update' => ['type' => 'bool', 'short' => 'u', 'description' => 'Update the plugin.'],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $package = $this->input('package');

        if ($this->input('remove')) {
            plugin_manager($package)->unregister();
            $this->line('Plugin successfully unregistered.');
        }
        else if ($this->input('update')) {
            plugin_manager($package)->update();
            $this->line('Plugin successfully updated.');
        }
        else {
            plugin_manager($package)->register();
            $this->line('Plugin successfully registered.');
        }
    }

    /**
     * @inheritdoc
     */
    public function printHelp()
    {
        parent::printHelp();

        $this->notice('Path of enabled packages:');

        /** @noinspection PhpIncludeInspection */
        $packages = @file_exists(plugin_path('packages.php')) ? include plugin_path('packages.php') : [];

        $length = 0;
        foreach ($packages as $package => $path) {
            $n = strlen($package);
            if ($length < $n) {
                $length = $n;
            }
        }

        $basePathLength  = strlen(base_path()) + 1;
        foreach ($packages as $package => $path) {
            $package .= str_repeat(' ', 2 + $length - strlen($package));
            $this->write('  ' . $package, false, [Stdio::STYLE_GREEN]);
            $this->write(substr($path, $basePathLength), true);
        }

        $this->line('');
    }
}