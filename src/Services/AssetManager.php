<?php

namespace Core\Services;

use Core\Services\Contracts\AssetManager as AssetManagerContract;
use CssMin;
use InvalidArgumentException;
use JShrink\Minifier as JSMin;
use Leafo\ScssPhp\Compiler as SCSSCompiler;
use Less_Parser as LessCompiler;
use RuntimeException;

/**
 * Asset Management
 *
 * @see https://github.com/tedious/JShrink JShrink by Robert Hafner on GitHub
 * @see https://github.com/natxet/cssmin Clone of CssMin on GitHub
 * @see https://code.google.com/archive/p/cssmin/ Original CssMin by Joe Scylla
 * @see https://github.com/oyejorge/less.php Less Compiler by Josh Schmidt on GitHub
 * @see http://leafo.github.io/scssphp/ SCSS Compiler by Leaf Corcoran
 * @see https://github.com/leafo/scssphp SCSS Compiler by Leaf Corcoran on GitHub
 */
class AssetManager implements AssetManagerContract
{
    /**
     * Manifest of assets.
     *
     * @var array
     */
    private $manifest;

    /**
     * Build file name.
     *
     * @var string
     */
    private $buildFile;

    /**
     * Manifest file of assets specified by the application itself.
     *
     * @var string
     */
    private $manifestFile;

    /**
     * Manifest file of assets provided by the plugins.
     *
     * @var string
     */
    private $pluginManifestOfAssets;

    /**
     * Create a new AssetManager instance.
     *
     * @param string|null $buildFile
     * @param string|null $manifestFile
     * @param string|null $pluginManifestOfAssets
     */
    public function __construct($buildFile = null, $manifestFile = null, $pluginManifestOfAssets = null) // todo Plugin testen, Parameter vertauschen
    {
        $this->buildFile = $buildFile ?: resource_path('assets/build.php');
        $this->manifestFile = $manifestFile ?: manifest_path('assets/manifest.php');
        $this->pluginManifestOfAssets = $pluginManifestOfAssets ?: manifest_path('plugins/assets.php');

        // be sure the manifest path is exits
        $dir = dirname($this->manifestFile);
        if (!@file_exists($dir)) {
            if (!make_dir($dir, 0755)) {
                throw new RuntimeException('Unable to create directory ' . $dir); // @codeCoverageIgnore
            }
        }

        /** @noinspection PhpIncludeInspection */
        $this->manifest = @file_exists($this->manifestFile) ? include $this->manifestFile : [];
    }

    /**
     * @inheritdoc
     */
    public function publish($dest = null, $minify = true, $plugin = null)
    {
        $build = $this->build($dest, $plugin);

        foreach ($build as $file => $sources) {
            $this->copy($sources, public_path($file), $minify);
            if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['js', 'css'])) {
                $this->buildUniqueFile($file);
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function remove($dest = null, $plugin = null)
    {
        $build = $this->build($dest, $plugin);

        foreach ($build as $file => $sources) {
            $this->removeFiles($sources, public_path($file));
            if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['js', 'css'])) {
                $this->removeUniqueFile($file);
            }
        }

        return $this;
    }

    /**
     * Get the build information array.
     *
     * @param string|null $dest
     * @param string|null $plugin
     * @return array
     */
    private function build($dest = null, $plugin = null)
    {
        if ($plugin !== null) {
            /** @noinspection PhpIncludeInspection */
            $builds = file_exists($this->pluginManifestOfAssets) ? include $this->pluginManifestOfAssets : [];
            if (!isset($builds[$plugin])) {
                throw new InvalidArgumentException('Plugin "' . $plugin . '" has no assets or is not installed.');
            }
            /** @noinspection PhpIncludeInspection */
            $build = $builds[$plugin];
        }
        else {
            /** @noinspection PhpIncludeInspection */
            $build = require $this->buildFile;
        }

        // get only one asset if specified
        if ($dest !== null) {
            if (!isset($build[$dest])) {
                throw new InvalidArgumentException('Asset "' . $dest . '" is not defined.');
            }
            $build = [$dest => $build[$dest]];
        }

        // make relative path to absolute
        $basePath = base_path();
        foreach ($build as $asset => $sources) {
            foreach ($sources as $i => $source) {
                if ($source[0] != DIRECTORY_SEPARATOR) { // not absolute? todo testen, ob das auch für Windows geht (hängt davon ab wie BASE_PATH aussieht)
                    $build[$asset][$i] = $basePath . DIRECTORY_SEPARATOR . $source;
                }
            }
        }

        return $build;
    }

    /**
     * Copy files
     *
     * @param array $sources List of files or folders to copy (full path)
     * @param string $dest Destination file or folder (full path)
     * @param bool $minify Minimize the file
     */
    private function copy(array $sources, $dest, $minify)
    {
        // create destination path if not exists

        $ext = pathinfo($dest, PATHINFO_EXTENSION);
        $dir = !empty($ext) ? dirname($dest) : $dest;
        if (!@file_exists($dir)) {
            if (!make_dir($dir, 0775)) {
                throw new RuntimeException('Unable to create directory ' . $dir); // @codeCoverageIgnore
            }
        }

        // list all source files into a flat array

        $files = [];
        foreach ($sources as $source) {
            if (@is_dir($source)) {
                if ($ext == 'js') {
                    $filter = ['js'];
                }
                else if ($ext == 'css') {
                    $filter = ['css', 'less', 'scss'];
                }
                else {
                    $filter = null;
                }
                list_files($files, $source, $filter);
            }
            else {
                $files[] = $source;
            }
        }

        // copy all files to the destination

        if (@file_exists($dest) && !@is_dir($dest)) {
            unlink($dest);
        } // @codeCoverageIgnore

        foreach ($files as $file) {
            // get content
            $content = file_get_contents($file);

            // compile
            $srcExt = pathinfo($file, PATHINFO_EXTENSION);
            switch ($srcExt) {
                case 'less':
//                    $content = (new LessCompiler)->compile($content);
                    $content = (new LessCompiler)->parse($content)->getCss();
                    break;
                case 'scss':
                    $content = (new SCSSCompiler)->compile($content);
                    break;
            }

            // minimize
            if ($minify) {
                switch ($srcExt) {
                    case 'js':
                        $content = JSMin::minify($content);
                        break;
                    case 'css':
                    case 'less':
                    case 'scss':
                        $content = CssMin::minify($content);
                        break;
                }
            }

            // save content
            if (@is_dir($dest)) {
                $success = file_put_contents($dest . DIRECTORY_SEPARATOR . basename($file), $content, LOCK_EX);
                //@chmod($dest . DIRECTORY_SEPARATOR . basename($file), 0664);
            }
            else {
                // concatenate the files to a single file
                if (@file_exists($dest)) {
                    $success = file_put_contents($dest, PHP_EOL . $content, FILE_APPEND | LOCK_EX);
                }
                else {
                    $success = file_put_contents($dest, $content, LOCK_EX);
                    //@chmod($dest, 0664);
                }
            }
            if ($success === false) {
                throw new RuntimeException(sprintf('Asset Manager was not able to copy "%s" to "%s"', $file, $dest)); // @codeCoverageIgnore
            }
        }
    }

    /**
     * Build an unique file and update the manifest
     *
     * @param string $file Name of the file relative to public path.
     */
    private function buildUniqueFile($file)
    {
        // generate unique file
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $uniqueFile = 'build/' . basename($file, '.' . $ext) . '-' . uniqid() . '.' . $ext;
        copy(public_path($file), public_path($uniqueFile));
        //@chmod(public_path($uniqueFile), 0775);

        // update manifest
        $oldFile = isset($this->manifest[$file]) ? public_path($this->manifest[$file]) : null;
        $this->manifest[$file] = $uniqueFile;
        if (file_put_contents($this->manifestFile, '<?php return ' . var_export($this->manifest, true) . ';' . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Asset Manager was not able to save manifest file "%s"', $this->manifestFile)); // @codeCoverageIgnore
        }
        // @chmod($this->manifestFile, 0664); // not necessary, because only the cli need to have access

        // delete old unique file
        if ($oldFile !== null && @file_exists($oldFile)) {
            unlink($oldFile);
        }
    }

    /**
     * Remove files
     *
     * @param array $sources List of files or folders to remove (full path)
     * @param string $dest Destination file or folder (full path)
     */
    private function removeFiles(array $sources, $dest)
    {
        if (@is_dir($dest)) {
            foreach ($sources as $source) {
                if (@is_dir($source)) {
                    $files = [];
                    list_files($files, $source);
                    foreach ($files as $file) {
                        $file = $dest . DIRECTORY_SEPARATOR . basename($file);
                        if (@file_exists($file)) {
                            unlink($file);
                        }
                    }
                }
                else {
                    $file = $dest . DIRECTORY_SEPARATOR . basename($source);
                    if (@file_exists($file)) {
                        unlink($file);
                    }
                }
            }
        }
        else {
            if (@file_exists($dest)) {
                unlink($dest);
            }
        }
    }

    /**
     * Remove unique file and update the manifest
     *
     * @param string $file Name of the file relative to public path.
     */
    private function removeUniqueFile($file)
    {
        if (!isset($this->manifest[$file])) {
            return;
        }

        // delete unique file
        $uniqueFile = public_path($this->manifest[$file]);
        if (!is_null($uniqueFile) && @file_exists($uniqueFile)) {
            unlink($uniqueFile);
        }

        // update manifest
        unset($this->manifest[$file]);
        if (file_put_contents($this->manifestFile, '<?php return ' . var_export($this->manifest, true) . ';' . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Asset Manager was not able to save manifest file "%s"', $this->manifestFile)); // @codeCoverageIgnore
        }
    }
}