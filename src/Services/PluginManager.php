<?php

namespace Core\Services;

use Core\Exceptions\PluginException;
use Core\Services\Contracts\Command;
use Core\Services\Contracts\PluginManager as PluginManagerContract;
use InvalidArgumentException;
use Leafo\ScssPhp\Compiler as SCSSCompiler;
use RuntimeException;

/**
 * Plugin Management
 */
class PluginManager implements PluginManagerContract
{
    /**
     * Name of the vendor
     *
     * @var string
     */
    protected $vendor;

    /**
     * Name of the plugin (without vendor)
     *
     * @var string
     */
    private $plugin;

    /**
     * The full package path
     *
     * @var string
     */
    private $path;

    /**
     * The namespace of the source files.
     *
     * @var string
     */
    private $namespace;

    /**
     * List of enabled packages.
     * 
     * @var array
     */
    private $packages;

    /**
     * Path of the Manifest.
     *
     * @var string
     */
    private $manifestPath;

    /**
     * Create a new PluginManager instance.
     *
     * @param string $package Name of the plugin with vendor, e.g. foo/bar.
     * @param string|null $packagePath
     * @param string|null $manifestPath
     */
    public function __construct($package, $packagePath = null, $manifestPath = null)
    {
        if (($pos = strpos($package, '/')) === false) {
            throw new InvalidArgumentException('The package name is invalid. Format <vendor>/<plugin> expected.');
        }

        // search the package
        $this->path = $packagePath ?: vendor_path($package);
        if (!is_dir($this->path)) {
            $this->path = workbench_path($package);
            if (!is_dir($this->path)) {
                throw new InvalidArgumentException('Package "' . $package . '" not found.');
            }
        } // @codeCoverageIgnore

        // read the namespace from composer.json
        $this->namespace = $this->getNamespace($this->path . '/composer.json');

        $this->manifestPath = $manifestPath ?: manifest_path('plugins');

        // be sure the manifest path is exits
        if (!@file_exists($this->manifestPath)) {
            if (!make_dir($this->manifestPath, 0755)) {
                throw new RuntimeException('Unable to create directory ' . $this->manifestPath); // @codeCoverageIgnore
            }
        }

        $manifest = $this->getManifest('packages');
        /** @noinspection PhpIncludeInspection */
        $this->packages = @file_exists($manifest) ? include $manifest : [];
        $this->vendor   = substr($package, 0, $pos);
        $this->plugin   = substr($package, $pos + 1);
    }

    /**
     * Get the manifest by given name.
     *
     * @param $name
     * @return string
     */
    private function getManifest($name)
    {
        return $this->manifestPath . '/' . $name .'.php';
    }

    /**
     * @inheritdoc
     */
    public function register()
    {
        // Determine if a plugin with the same name is already registered.
        foreach ($this->packages as $package => $path) {
            if (strpos($package, DIRECTORY_SEPARATOR . $this->plugin) !== false) {
                throw new PluginException('Plugin with the name "' . $this->plugin . '" already registered.');
            }
        }

        $this->publish(true);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function update()
    {
        if (!isset($this->packages[$this->vendor . DIRECTORY_SEPARATOR . $this->plugin])) {
            throw new PluginException('Package "' . $this->vendor . DIRECTORY_SEPARATOR . $this->plugin . '" is not registered.');
        }

        $this->publish(true);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function unregister()
    {
        if (!isset($this->packages[$this->vendor . DIRECTORY_SEPARATOR . $this->plugin])) {
            throw new PluginException('Package "' . $this->vendor . DIRECTORY_SEPARATOR . $this->plugin . '" is not registered.');
        }

        $this->publish(false);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isRegistered()
    {
        return isset($this->packages[$this->vendor . DIRECTORY_SEPARATOR . $this->plugin]);
    }

    /**
     * Register and unregister the plugin
     *
     * @param bool $register
     */
    private function publish($register)
    {
        $this->publishConfig($register);
        $this->publishPublicFolder($register);
        $this->publishAssets($register);
        $this->publishCommands($register);
        $this->publishMigrations($register);
        $this->publishLanguages($register);
        $this->publishViews($register);
        $this->publishRoutes($register);
        $this->publishClasses($register);
        $this->publishServices($register);
        $this->publishBootstraps($register);
        $this->enablePackage($register);
    }

    /**
     * Read the namespace from composer.json
     *
     * @param string $composerJson full path of composer.json
     * @return string
     */
    private function getNamespace($composerJson)
    {
        if (!@file_exists($composerJson)) {
            throw new InvalidArgumentException('"' . $composerJson . '" not found.'); // @codeCoverageIgnore
        }
        $composer = json_decode(file_get_contents($composerJson), true);
        $autoload = isset($composer['autoload']['psr-4']) ? array_flip($composer['autoload']['psr-4']) : [];
        if (!isset($autoload['src/'])) {
            throw new InvalidArgumentException('psr-4 autoload directive for folder "src/" is missing in ' . $composerJson . '.');
        }

        return $autoload['src/'];
    }

    /**
     * Copy the config, if not already done
     *
     * @param bool $register
     */
    private function publishConfig($register)
    {
        $src = $this->path . '/config/config.php';
        if (!@file_exists($src)) {
            return;
        }

        $dest = config_path($this->plugin . '.php');
        if (@file_exists($dest)) {
            return;
        }

        if ($register) {
            copy($src, $dest);
        }
    }

    /**
     * Copy the public folder
     *
     * @param bool $register
     */
    private function publishPublicFolder($register)
    {
        // read files
        $publicPath = $this->path . '/public';
        if (!@file_exists($publicPath)) {
            return;
        }
        $files = [];
        list_files($files, $publicPath);

        // copy files
        $publicPathLength = strlen($publicPath) + 1;
        foreach ($files as $file) {
            $destDir = public_path(substr(dirname($file), $publicPathLength));
            if (!@file_exists($destDir)) {
                if (!make_dir($destDir, 0755)) {
                    throw new RuntimeException('Unable to create directory ' . $destDir); //@codeCoverageIgnore
                }
            } //@codeCoverageIgnore
            $dest = $destDir . DIRECTORY_SEPARATOR . basename($file);
            if ($register) {
                copy($file, $dest);
            }
            else {
                if (@file_exists($dest)) {
                    unlink($dest);
                }
            }
        }
    }

    /**
     * Update manifest "assets.php" and run the Asset Manager to publish the assets.
     *
     * @param bool $register
     */
    private function publishAssets($register)
    {
        $build = $this->path . '/assets/build.php';
        if (!@file_exists($build)) {
            return;
        }

        if (!$register) {
            DI::getInstance()->get('asset-manager')->remove(null, $this->plugin);
        }

        // update manifest
        $manifest = $this->getManifest('assets');
        /** @noinspection PhpIncludeInspection */
        $list = @file_exists($manifest) ? include $manifest : [];
        if ($register) {
            // make absolute path to relative path
            $basePath = base_path();
            $basePathLength = strlen($basePath);
            /** @noinspection PhpIncludeInspection */
            $assets = include $build;
            foreach ($assets as $asset => $sources) {
                foreach ($sources as $i => $source) {
                    if (substr($source, 0, $basePathLength) == $basePath) {
                        $assets[$asset][$i] = substr($source, $basePathLength + 1);
                    }
                }
            }
            // insert asset build information
            /** @noinspection PhpIncludeInspection */
            $list[$this->plugin] = $assets;
        }
        else {
            // remove asset build information
            unset($list[$this->plugin]);
        }
        $this->saveArray($manifest, $list);

        if ($register) {
            DI::getInstance()->get('asset-manager')->publish(null, true, $this->plugin);
        }
    }

    /**
     * Update manifest "commands.php"
     *
     * @param bool $register
     */
    private function publishCommands($register)
    {
        // read all commands from folder
        $commandPath = $this->path . '/src/Commands';
        if (!@file_exists($commandPath)) {
            return;
        }
        $classes = [];
        list_classes($classes, $commandPath, $this->namespace . 'Commands');

        // generate command list
        // todo gleicher Code bei CommandFactory! => Liste vom CommandFactory generieren lassen
        $manifest = $this->getManifest('commands');
        /** @noinspection PhpIncludeInspection */
        $list = @file_exists($manifest) ? include $manifest : [];
        foreach ($classes as $class) {
            /** @var Command $command */
            $command = new $class;
            $name = $command->name();
            if ($register) {
                $description = trim($command->description() ?: '');
                $list[$name] = compact('class', 'name', 'description');
            }
            else {
                unset($list[$name]);
            }
        }
        ksort($list);
        $this->saveArray($manifest, $list);
    }

    /**
     * Update manifest "migrations.php"
     *
     * @param bool $register
     */
    private function publishMigrations($register)
    {
        // read migrations from folder
        $migrationPath = $this->path . '/migrations';
        if (!@file_exists($migrationPath)) {
            return;
        }
        $files = [];
        list_files($files, $migrationPath, ['php']);

        // update manifest
        $manifest = $this->getManifest('migrations');
        /** @noinspection PhpIncludeInspection */
        $list = @file_exists($manifest) ? include $manifest : [];
        $basePathLength = strlen(base_path()) + 1;
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if ($register) {
                $list[$name] = substr($file, $basePathLength);
            }
            else {
                unset($list[$name]);
            }
        }
        $this->saveArray($manifest, $list);
    }

    /**
     * Update the language files.
     *
     * @param bool $register
     */
    private function publishLanguages($register)
    {
        // read language files from folder
        $langPath = $this->path . '/lang';
        if (!@file_exists($langPath)) {
            return;
        }
        $files = [];
        list_files($files, $langPath, ['php'], false);

        // update manifest
        $manifest = $this->getManifest('languages');
        /** @noinspection PhpIncludeInspection */
        $list = @file_exists($manifest) ? include $manifest : [];
        $basePathLength = strlen(base_path()) + 1;
        foreach ($files as $file) {
            $locale = basename($file, '.php');
            if ($register) {
                if (!isset($list[$locale])) {
                    $list[$locale] = [];
                }
                $list[$locale][$this->plugin] = substr($file, $basePathLength);
            }
            else {
                if (isset($list[$locale])) {
                    unset($list[$locale][$this->plugin]);
                    if (empty($list[$locale])) {
                        unset($list[$locale]);
                    }
                }
            }
        }
        $this->saveArray($manifest, $list);
    }

    /**
     * Update manifest "views.php"
     *
     * @param bool $register
     */
    private function publishViews($register)
    {
        // read views from folder
        $visitPath = $this->path . '/views';
        if (!@file_exists($visitPath)) {
            return;
        }
        $files = [];
        list_files($files, $visitPath, ['php']);

        // update manifest
        $manifest = $this->getManifest('views');
        /** @noinspection PhpIncludeInspection */
        $list = @file_exists($manifest) ? include $manifest : [];
        $visitPathLength = strlen($visitPath) + 1;
        $basePathLength  = strlen(base_path()) + 1;
        foreach ($files as $file) {
            if (substr($file, -10) == '.blade.php') {
                $name = str_replace(DIRECTORY_SEPARATOR, '.', substr($file, $visitPathLength, -10));
                if ($register) {
                    $list[$name] = substr($file, $basePathLength);
                }
                else {
                    unset($list[$name]);
                }
            }
        }
        $this->saveArray($manifest, $list);
    }

    /**
     * Update manifest "routes.php"
     *
     * @param bool $register
     */
    private function publishRoutes($register)
    {
        if (!@file_exists($this->path . '/config/routes.php')) {
            return;
        }

        // Read enabled packages and add/remove the current plugin.
    
        $packages = $this->listPackages($register);

        // update manifest

        $dest = '<?php' . PHP_EOL;
        if (!empty($packages)) {
            $dest .= PHP_EOL .
                'use Core\Services\Contracts\Route;' . PHP_EOL
                . PHP_EOL .
                '$route = \Core\Application::route();' . PHP_EOL;
        }

        foreach ($packages as $package => $path) {
            $file = base_path($path . '/config/routes.php');
            if (!@file_exists($file)) {
                continue;
            }

            // get content
            $src = trim(file_get_contents($file));

            // strip php tag
            if (substr($src, 0, 5) == '<?php') {
                $src = trim(substr($src, 5));
            }
            if (substr($src, -2) == '?>') {
                $src = trim(substr($src, 0, -2));
            }

            // strip the $route variable if exists
            $src = trim(preg_replace('/\$route\s*=\s*\\\\Core\\\\Application::route\(\);/', '', $src));

            // strip "use Core\Services\Contracts\Route;" if exists
            $src = trim(preg_replace('/use Core\\\\Services\\\\Contracts\\\\Route;/', '', $src));

            // append content to summery file
            $dest .= PHP_EOL .
                '///////////////////////////////////////////////////////////////////////////////' . PHP_EOL .
                '// ' . $package . PHP_EOL .
                PHP_EOL .
                $src . PHP_EOL;
        }

        $this->saveContent($this->getManifest('routes'), $dest);
    }

    /**
     * Update manifest "classes.php"
     *
     * @param bool $register
     */
    private function publishClasses($register)
    {
        // read classes from folder
        $path = $this->path . '/src';
        if (!@file_exists($path)) {
            return;
        }

        $classes = [];
        list_classes($classes, $path, rtrim($this->namespace, '\\'));

        // update manifest
        $manifest = $this->getManifest('classes');
        /** @noinspection PhpIncludeInspection */
        $list = @file_exists($manifest) ? include $manifest : [];
        $len = strlen($this->namespace);
        foreach ($classes as $class) {
            $ns = substr($class, $len);
            if (($pos = strpos($ns, '\\')) === false) {
                continue; // no subdirectory
            }
            if (strpos($ns, '\\Contracts\\') !== false) {
                continue; // contracts
            }
            $group = substr($ns, 0, $pos);
            if (in_array($group, ['Bootstraps', 'Commands', 'Services'])) {
                continue; // listed by an another migration file
            }
            $name = substr($ns, $pos + 1); //basename(str_replace('\\', '/', $class));
            if ($register) {
                if (!isset($list[$group])) {
                    $list[$group] = [];
                }
                $list[$group][$name] = $class;
            }
            else {
                if (isset($list[$group])) {
                    unset($list[$group][$name]);
                    if (empty($list[$group])) {
                        unset($list[$group]);
                    }
                }
            }
        }

        //ksort($list);
        $this->saveArray($manifest, $list);
    }

    /**
     * Update manifest "services.php"
     *
     * @param bool $register
     */
    private function publishServices($register)
    {
        if (!@file_exists($this->path . '/config/services.php')) {
            return;
        }

        // Read enabled packages and add/remove the current plugin.

        $packages = $this->listPackages($register);

        // update manifest

        $dest = '<?php' . PHP_EOL;
        if (!empty($packages)) {
            $dest .= PHP_EOL . '$di = \Core\Services\DI::getInstance();' . PHP_EOL;
        }

        foreach ($packages as $package => $path) {
            $file = base_path($path . '/config/services.php');
            if (!@file_exists($file)) {
                continue;
            }

            // get content
            $src = trim(file_get_contents($file));

            // strip php tag
            if (substr($src, 0, 5) == '<?php') {
                $src = trim(substr($src, 5));
            }
            if (substr($src, -2) == '?>') {
                $src = trim(substr($src, 0, -2));
            }

            // strip the $di variable if exists
            $src = trim(preg_replace('/\$di\s*=\s*\\\\Core\\\\Services\\\\DI::getInstance\(\);/', '', $src));

            // append content to summery file
            $dest .= PHP_EOL .
                '///////////////////////////////////////////////////////////////////////////////' . PHP_EOL .
                '// ' . $package . PHP_EOL .
                PHP_EOL .
                $src . PHP_EOL;
        }

        $this->saveContent($this->getManifest('services'), $dest);
    }

    /**
     * Update manifest "bootstraps.php".
     *
     * @param bool $register
     */
    private function publishBootstraps($register)
    {
        $classes = [];
        if (@file_exists($this->path . '/src/Bootstraps')) {
            list_classes($classes, $this->path . '/src/Bootstraps', $this->namespace . 'Bootstraps');
        }
        if (empty($classes)) {
            return;
        }

        // Read enabled packages and add/remove the current plugin.

        $packages = $this->listPackages($register);

        // update manifest

        $dest = '<?php' . PHP_EOL;

        foreach ($packages as $package => $relativePath) {
            $path = base_path($relativePath);
            $classes = [];
            if (@file_exists($path . '/src/Bootstraps')) {
                $namespace = $this->getNamespace($path . '/composer.json');
                list_classes($classes, $path . '/src/Bootstraps', $namespace . 'Bootstraps');
            }
            if (empty($classes)) {
                continue;
            }
            $dest .= PHP_EOL .
                '///////////////////////////////////////////////////////////////////////////////' . PHP_EOL .
                '// ' . $package . PHP_EOL .
                PHP_EOL;
            foreach ($classes as $class) {
                $dest .= '(new ' . $class . ')->boot();' . PHP_EOL;
            }
        }

        $this->saveContent($this->getManifest('bootstrap'), $dest);
    }

    /**
     * Update manifest "packages.php".
     *
     * @param bool $register
     */
    private function enablePackage($register)
    {
        $this->saveArray($this->getManifest('packages'), $this->listPackages($register));
    }

    ///////////////////////////////////////////////////////////////////////////
    // Helpers

    /**
     * Read enabled packages and add the current plugin.
     *
     * @param bool $register
     * @return array
     */
    private function listPackages($register)
    {
        if ($register) {
            $relativePath = substr($this->path, strlen(base_path()) + 1);
            $this->packages[$this->vendor . DIRECTORY_SEPARATOR . $this->plugin] = $relativePath;
            ksort($this->packages);
        }
        else {
            unset($this->packages[$this->vendor . DIRECTORY_SEPARATOR . $this->plugin]);
        }

        return $this->packages;
    }

    /**
     * Save content to a file.
     *
     * @param string $file
     * @param string $content
     */
    private function saveContent($file, $content)
    {
        if (file_put_contents($file, $content, LOCK_EX) === false) {
            throw new RuntimeException('Plugin Manager is not able to save ' . $file . '.'); // @codeCoverageIgnore
        }
    }

    /**
     * Save array as an include file.
     *
     * @param string $file
     * @param array $array
     */
    private function saveArray($file, $array)
    {
        if (file_put_contents($file, '<?php return ' . var_export($array, true) . ';' . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Plugin Manager is not able to save ' . $file . '.'); // @codeCoverageIgnore
        }
    }
}