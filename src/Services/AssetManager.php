<?php

namespace Core\Services;

use Core\Services\Contracts\AssetManager as AssetManagerContract;
use CssMin;
use JShrink\Minifier as JSMin;
use Leafo\ScssPhp\Compiler as SCSSCompiler;
use Less_Parser as LessCompiler;

/**
 * Asset Management
 *
 * This class works with Assetic.
 *
 * @see https://github.com/linkorb/jsmin-php PHP implementation of Douglas Crockford's JSMin on GitHub
 * @see https://code.google.com/archive/p/cssmin/ CssMin by Joe Scylla
 * @see https://github.com/natxet/cssmin CssMin on GitHub
 * @see http://leafo.net/lessphp/ Less Compiler by Leaf Corcoran
 * @see https://github.com/leafo/lessphp Less Compiler on GitHub
 * @see http://leafo.github.io/scssphp/ SCSS Compiler by Leaf Corcoran
 * @see https://github.com/leafo/scssphp SCSS Compiler on GitHub
 */
class AssetManager implements AssetManagerContract
{
    /**
     * Filename of the manifest.
     *
     * @var string
     */
    private $manifestFile;

    /**
     * Manifest
     *
     * @var array
     */
    private $manifest;

    /**
     * Create a new AssetManager instance.
     */
    public function __construct()
    {
        // read manifest
        $this->manifestFile = asset_path('manifest.php');

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
            $assets = file_exists(plugin_path('assets.php')) ? include plugin_path('assets.php') : [];
            if (!isset($assets[$plugin])) {
                throw new \InvalidArgumentException('Plugin "' . $plugin . '" has no assets or is not installed.');
            }
            /** @noinspection PhpIncludeInspection */
            $build = $assets[$plugin];
        }
        else {
            /** @noinspection PhpIncludeInspection */
            $build = require resource_path('assets/build.php');
        }

        if ($dest !== null) {
            if (!isset($build[$dest])) {
                throw new \InvalidArgumentException('Asset "' . $dest . '" is not defined.');
            }
            $build = [$dest => $build[$dest]];
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
            if (@mkdir($dir, 0775, true) === false) {
                throw new \RuntimeException('Unable to create directory ' . $dir);
            }
        }

        // list all source files into a flat array

        $files = [];
        foreach ($sources as $source) {
            if (@is_dir($source)) {
                if ($ext == 'js') {
                    $filter = ['js'];
                }
                else if ($ext == 'cs') {
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
        }

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
                throw new \RuntimeException(sprintf('Asset Manager was not able to copy "%s" to "%s"', $file, $dest));
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
            throw new \RuntimeException(sprintf('Asset Manager was not able to save manifest file "%s"', $this->manifestFile));
        }
        // @chmod($this->manifestFile, 0664); // not necessary, because only the cli need to have access

        // delete old unique file
        if (!is_null($oldFile) && @file_exists($oldFile)) {
            unlink($oldFile);
        }
    }

    /**
     * Remove files
     *
     * @param array $sources List of files or folders to copy (full path)
     * @param string $dest Destination file or folder (full path)
     */
    private function removeFiles(array $sources, $dest)
    {
        if (@is_dir($dest)) {
            foreach ($sources as $source) {
                remove_path($dest . DIRECTORY_SEPARATOR . basename($source));
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
            throw new \RuntimeException(sprintf('Asset Manager was not able to save manifest file "%s"', $this->manifestFile));
        }
    }
}