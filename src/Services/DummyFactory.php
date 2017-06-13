<?php

namespace Core\Services;

use Core\Services\Contracts\DummyFactory as FactoryContract;
use InvalidArgumentException;

class DummyFactory implements FactoryContract
{
    /**
     * Instances of a Dummy.
     *
     * @var Object[]
     */
    private $instances = [];

    /**
     * Name of the default store.
     *
     * @var string
     */
    private $defaultStore;

    /**
     * PLugin's drivers.
     *
     * @var array|null
     */
    private $pluginDrivers;

    /**
     * Create a new factory instance.
     */
    public function __construct()
    {
        $this->defaultStore = config('dummy.default');
    }

    /**
     * @inheritdoc
     */
    public function store($name = null)
    {
        if ($name === null) {
            $name = $this->defaultStore;
        }

        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        $config = config('dummy.stores.' . $name);
        if ($config === null) {
            throw new InvalidArgumentException('Dummy store "' . $name . '" is not defined.');
        }

        if (!isset($config['driver'])) {
            throw new InvalidArgumentException('Dummy driver for store "' . $name . '" is not specified.');
        }

        $class = pascal_case($config['driver']);
        if (file_exists(app_path('Services/Dummy/' .  $class . '.php'))) {
            $class = '\\App\\Services\\Dummy\\' . $class;
        }
        else if (($pluginDriver = $this->getPluginDriver($class)) !== null) {
            $class = $pluginDriver;
        }
        else {
            $class = '\\Core\\Services\\Dummy\\' . $class;
        }

        $instance = new $class($config);

        return $this->instances[$name] = $instance;
    }

    /**
     * Get the full qualified class name of the plugin's driver.
     *
     * @param string $class
     * @return null|string
     */
    private function getPluginDriver($class)
    {
        if ($this->pluginDrivers === null) {
            $manifest = manifest_path('plugins/classes.php');
            if (file_exists($manifest)) {
                /** @noinspection PhpIncludeInspection */
                $classes = include $manifest;
                $this->pluginDrivers = isset($classes['Dummy']) ? $classes['Dummy'] : [];
            }
            else {
                $this->pluginDrivers = [];
            }
        }

        return isset($this->pluginDrivers[$class]) ? $this->pluginDrivers[$class] : null;
    }
}