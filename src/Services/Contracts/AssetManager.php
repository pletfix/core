<?php

namespace Core\Services\Contracts;

interface AssetManager
{
    /**
     * Publish the Assets
     *
     * @param string|null $dest Destination file relative to the public path which is defined in build.php.
     * @param bool $minify Minimize the file
     * @param string|null $plugin Name of the plugin (without vendor). If not set, "resources/assets/build.php" will be loaded.
     * @return $this
     */
    public function publish($dest = null, $minify = true, $plugin = null);

    /**
     * Remove the Assets from public path
     *
     * @param string|null $dest Destination file relative to the public path which is defined in build.php.
     * @param string|null $plugin Name of the plugin (without vendor). If not set, "resources/assets/build.php" will be loaded.
     * @return $this
     */
    public function remove($dest = null, $plugin = null);
}
