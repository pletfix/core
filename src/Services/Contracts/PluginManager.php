<?php

namespace Core\Services\Contracts;

interface PluginManager
{
    /**
     * Register the plugin
     *
     * @return $this;
     */
    public function register();

    /**
     * Update the plugin
     *
     * @return $this;
     */
    public function update();

    /**
     * Unregister the plugin
     *
     * @return $this;
     */
    public function unregister();

    /**
     * Determine if the plugin is already registered.
     *
     * @return bool
     */
    public function isRegistered();
}
