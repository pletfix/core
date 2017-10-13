<?php

namespace Core\Services;

use Core\Commands\HelpCommand;
use Core\Services\Contracts\Command as CommandContract;
use Core\Services\Contracts\CommandFactory as CommandFactoryContract;
use RuntimeException;

/**
 * Command Factory is the container for a collection of commands.
 */
class CommandFactory implements CommandFactoryContract
{
    /**
     * Cached command list.
     *
     * @var string
     */
    private $cachedFile;

    /**
     * Manifest file of commands.
     *
     * @var string
     */
    private $pluginManifestOfCommands;

    /**
     * Create a new factory instance.
     *
     * @param string|null $cachedFile
     * @param string|null $pluginManifestOfCommands
     */
    public function __construct($cachedFile = null, $pluginManifestOfCommands = null)
    {
        $this->cachedFile = $cachedFile ?: storage_path('cache/commands.php');
        $this->pluginManifestOfCommands = $pluginManifestOfCommands ?: manifest_path('plugins/commands.php');
    }

    /**
     * @inheritdoc
     */
    public function command($argv)
    {
        if (empty($argv)) {
            return new HelpCommand; // list all available commands
        }

        $list = $this->commandList();
        if (!isset($list[$argv[0]])) {
            return new HelpCommand($argv); // finds an alternative of the command name
        }

        $name = array_shift($argv); // strip the command name from the command line arguments
        $class = $list[$name]['class'];

        return new $class($argv);
    }

    ///////////////////////////////////////////////////////////////////////////
    // Load command list

    /**
     * @inheritdoc
     */
    public function commandList()
    {
        if ($this->isCacheUpToDate()) {
            $list = $this->loadCommandListFromCache();
        }
        else {
            $list = $this->createCommandList();
            $this->saveCommandListToCache($list);
        }

        return $list;
    }

    /**
     * Read available commands recursive from given path.
     *
     * @param array &$list Receives the command information
     * @param string $path
     * @param string $namespace
     */
    private function listCommands(array &$list, $path, $namespace)
    {
        $classes = [];
        list_classes($classes, $path, $namespace);
        foreach ($classes as $class) {
            /** @var CommandContract $command */
            $command = new $class;
            $name = $command->name();
            $description = trim($command->description() ?: '');
            $list[$name] = compact('class', 'name', 'description');
        }
    }

    /**
     * Create a new command list.
     *
     * @return array
     */
    private function createCommandList()
    {
        $list = [];

        // read all core commands
        $this->listCommands($list, __DIR__ . '/../Commands', 'Core\Commands');

        // merge all plugin commands (plugin overrides the core)
        if (file_exists($this->pluginManifestOfCommands)) {
            /** @noinspection PhpIncludeInspection */
            $list = array_merge($list, include $this->pluginManifestOfCommands);
        }

        // merge all commands defined by application (application overrides all other)
        $this->listCommands($list, app_path('Commands'), 'App\Commands');

        // save the new command list
        ksort($list);

        return $list;
    }

    /**
     * Determine if the cached file is up to date with the command folder.
     *
     * @return bool
     */
    private function isCacheUpToDate()
    {
        if (!file_exists($this->cachedFile)) {
            return false;
        }

        $cacheTime = filemtime($this->cachedFile);
        $mTime     = $this->modificationTime();

        return $cacheTime == $mTime;
    }

    /**
     * Load the command list from cache.
     *
     * @return array
     */
    private function loadCommandListFromCache()
    {
        /** @noinspection PhpIncludeInspection */
        return require $this->cachedFile;
    }

    /**
     * Save the command list to the cache.
     *
     * @param array $list
     */
    private function saveCommandListToCache($list)
    {
        if (!is_dir($cacheDir = dirname($this->cachedFile))) {
            if (!make_dir($cacheDir, 0775)) {
                throw new RuntimeException(sprintf('Command factory was not able to create directory "%s"', $cacheDir)); // @codeCoverageIgnore
            }
        }

        if (file_exists($this->cachedFile)) {
            unlink($this->cachedFile); // so we will to be the owner at the new file
        }

        if (file_put_contents($this->cachedFile, '<?php return ' . var_export($list, true) . ';' . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Command factory was not able to save cached file "%s"', $this->cachedFile)); // @codeCoverageIgnore
        }

        @chmod($this->cachedFile, 0664);

        $time = $this->modificationTime();

        if (!@touch($this->cachedFile, $time)) {
            throw new RuntimeException(sprintf('Command factory was not able to modify time of cached file "%s"', $this->cachedFile)); // @codeCoverageIgnore
        }
    }

    /**
     * Gets the modification time.
     *
     * @return int
     */
    private function modificationTime()
    {
        return max(
            file_exists($this->pluginManifestOfCommands) ? filemtime($this->pluginManifestOfCommands) : 0,
            filemtime(__DIR__ . '/../Commands'),
            file_exists(app_path('Commands')) ? filemtime(app_path('Commands')) : 0
        );
    }
}