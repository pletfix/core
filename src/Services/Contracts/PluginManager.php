<?php

namespace Core\Services\Contracts;

interface PluginManager
{
    /**
     * Create a new PluginManager instance.
     *
     * @param string $package Name of the plugin with vendor, e.g. foo/bar.
     */
    public function __construct($package);

    /**
     * Register the plugin
     */
    public function register();

    /**
     * Update the plugin
     */
    public function update();

    /**
     * Unregister the plugin
     */
    public function unregister();

    /**
     * Determine if the plugin is already registered.
     *
     * @return bool
     */
    public function isRegistered();
}
