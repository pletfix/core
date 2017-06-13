<?php

namespace Core\Services;

use Core\Commands\HelpCommand;
use Core\Services\Contracts\Command;
use Core\Services\Contracts\CommandFactory as CommandFactoryContract;

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
     * Create a new factory instance.
     */
    public function __construct()
    {
        $this->cachedFile  = storage_path('cache/commands.php');
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
     * Create a new command list.
     *
     * @return array
     */
    private function createCommandList()
    {
        $classes = [];
        list_classes($classes, __DIR__ . '/../Commands', 'Core\Commands');
        list_classes($classes, app_path('Commands'), 'App\Commands');

        $list = [];
        foreach ($classes as $class) {
            /** @var Command $command */
            $command = new $class;
            $name = $command->name();
            $description = trim($command->description() ?: '');
            $list[$name] = compact('class', 'name', 'description');
        }

        $pluginManifest = manifest_path('plugins/commands.php');
        if (file_exists($pluginManifest)) {
            /** @noinspection PhpIncludeInspection */
            $list = array_merge(include $pluginManifest, $list);
        }

        ksort($list);

        // todo plugin sollte den core überschreiben können und app die plugins

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
            if (!@mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
                throw new \RuntimeException(sprintf('Command factory was not able to create directory "%s"', $cacheDir));
            }
        }

        if (file_exists($this->cachedFile)) {
            unlink($this->cachedFile); // so we will to be the owner at the new file
        }

        if (file_put_contents($this->cachedFile, '<?php return ' . var_export($list, true) . ';' . PHP_EOL, LOCK_EX) === false) {
            throw new \RuntimeException(sprintf('Command factory was not able to save cached file "%s"', $this->cachedFile));
        }

        //@chmod($this->cachedFile, 0664); // not necessary, because only the cli need to have access

        $time = $this->modificationTime();

        if (!touch($this->cachedFile, $time)) {
            throw new \RuntimeException(sprintf('Command factory was not able to modify time of cached file "%s"', $this->cachedFile));
        }
    }

    /**
     * Gets the modification time.
     *
     * @return int
     */
    private function modificationTime()
    {
        $pluginManifest = manifest_path('plugins/commands.php');

        return max(
            file_exists($pluginManifest) ? filemtime($pluginManifest) : 0,
            filemtime(__DIR__ . '/../Commands'),
            filemtime(app_path('Commands'))
        );
    }
}