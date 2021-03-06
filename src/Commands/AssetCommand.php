<?php

namespace Core\Commands;

use Core\Services\Command;

class AssetCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'asset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish the assets.';

    /**
     * Possible arguments of the command.
     *
     * @var array
     */
    protected $arguments = [
        'dest' => ['type' => 'string', 'default' => null, 'description' => 'Destination file relative to the public path which is defined in build.php.'],
    ];

    /**
     * Possible options of the command.
     *
     * @var array
     */
    protected $options = [
        'no-minify' => ['type' => 'bool',   'short' => 'n', 'description' => 'Do not minimize the file.'],
        'plugin'    => ['type' => 'string', 'short' => 'p', 'default' => null, 'description' => 'Name of the plugin (without vendor) which the assets are from.'],
        'remove'    => ['type' => 'bool',   'short' => 'r', 'description' => 'Remove the assets'],
    ];

    /**
     * @inheritdoc
     */
    protected function handle()
    {
        $dest   = $this->input('dest');
        $minify = !$this->input('no-minify');
        $plugin = $this->input('plugin') !== null ? str_replace('pletfix-', '', str_replace('-plugin', '', $this->input('plugin'))) : null;

        if ($this->input('remove')) {
            asset_manager()->remove($dest, $plugin);
            $this->line('Assets are successfully removed.');
        }
        else {
            $this->line('Build assets...');
            asset_manager()->publish($dest, $minify, $plugin);
            $this->line('Assets are successfully published.');
        }
    }
}