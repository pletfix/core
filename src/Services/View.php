<?php

namespace Core\Services;

use Core\Services\Contracts\Collection as CollectionContract;
use Core\Services\Contracts\View as ViewContract;
use InvalidArgumentException;

/**
 * View Template Engine
 *
 * License: MIT
 *
 * This code based on
 * - Daggerhart's Simple PHP Template Class and
 * - Laravel's View Factory (see copyright notice license-laravel.md)
 *
 * @see http://www.daggerhart.com/simple-php-template-class/ Simple PHP Template Class by Jonathan Daggerhart
 * @see https://github.com/illuminate/view/blob/master/Factory.php Laravel's View Factory on GitHub by Taylor Otwell
 * @see http://poe-php.de/oop/mvc-einfuehrung-framework/3 Tutorial by Weekly Pö
 */
class View implements ViewContract
{
    /**
     * The global scope.
     *
     * The scope is shared by another views during executing.
     *
     * @var array
     */
    private $scope = [];

    /**
     * Plugin's Views
     *
     * @var array
     */
    private static $manifest;

    /**
     * View constructor.
     *
     * @param object|null &$scope
     */
    public function __construct(&$scope = null)
    {
        if (is_null($scope)) {
            $this->scope = (object)[
                'sections'     => [],
                'sectionStack' => [],
            ];
        }
        else {
            $this->scope = &$scope;
        }
    }

    /**
     * @inheritdoc
     */
    public function render($name, $variables = [])
    {
        if ($variables instanceof CollectionContract) {
            $variables = $variables->all();
        }

        $templateFile = $this->templateFile($name);
        if (!file_exists($templateFile)) {
            throw new InvalidArgumentException('View ' . $name . '" not found.');
        }

        $cachedFile = storage_path('cache/views/' . md5($templateFile) . '.phtml');

        if (!$this->isCacheUpToDate($cachedFile, $templateFile)) {
            $phpCode = DI::getInstance()->get('view-compiler')->compile(file_get_contents($templateFile));
            $this->saveFile($cachedFile, $phpCode, filemtime($templateFile));
        }

        return $this->execute($cachedFile, $variables);
    }

    /**
     * @inheritdoc
     */
    public function exists($name)
    {
        return file_exists($this->templateFile($name));
    }

    /**
     * Get the full template file name by given view name.
     *
     * @param string $name Name of the view
     * @return string
     */
    private function templateFile($name)
    {
        $filename = view_path(str_replace('.', DIRECTORY_SEPARATOR, $name) . '.blade.php');
        if (file_exists($filename)) {
            return $filename;
        }

        if (self::$manifest === null) {
            $pluginManifest = manifest_path('plugins/views.php');
            if (file_exists($pluginManifest)) {
                /** @noinspection PhpIncludeInspection */
                self::$manifest = include $pluginManifest;
            }
        }

        return isset(self::$manifest[$name]) ? base_path(self::$manifest[$name]) : null;
    }

    /**
     * Determine if the cached file is up to date with the template.
     *
     * @param string $cachedFile
     * @param string $templateFile
     * @return bool
     */
    private function isCacheUpToDate($cachedFile, $templateFile)
    {
        if (!file_exists($cachedFile)) {
            return false;
        }

        $cacheTime = filemtime($cachedFile);
        $templTime = filemtime($templateFile);

        return $cacheTime == $templTime;
    }

    /**
     * Save the given content.
     *
     * @param string $file
     * @param string $content
     * @param int $time File modification time
     */
    private function saveFile($file, $content, $time)
    {
        // create directory if not exists
        if (!is_dir($dir = dirname($file))) {
            if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('View Template Engine was not able to create directory "%s"', $dir));
            }
        }

        // save file
        if (file_exists($file)) {
            unlink($file); // so we will to be the owner at the new file
        }
        if (file_put_contents($file, $content, LOCK_EX) === false) {
            throw new \RuntimeException(sprintf('View Template Engine was not able to save file "%s"', $file));
        }
        //@chmod($file, 0664); // not necessary, because only the application need to have access

        // set file modification time
        if (!touch($file, $time)) {
            throw new \RuntimeException(sprintf('View Template Engine was not able to modify time of file "%s"', $file));
        }
    }

    /**
     * Execute the compiled template.
     *
     * @internal param string $cachedFile
     * @internal param array $variables
     *
     * @return string
     */
    private function execute(/*$cachedFile, $variables*/)
    {
        ob_start();

        // extracting $variables into the scope
        extract(func_get_args()[1]);

        // including $cachedFile
        /** @noinspection PhpIncludeInspection */
        include func_get_args()[0];

        return ob_get_clean();
    }

    /*
     * --------------------------------------------------------------------------------------------------------------
     * Rendering Helpers
     *
     * This methods will be called by the template during executing.
     * The code based on Laravel's View Factory.
     * --------------------------------------------------------------------------------------------------------------
     */

    /**
     * Render the given view.
     *
     * @param string $name
     * @param array $variables
     * @return string
     */
    protected function make($name, $variables = [])
    {
        $view = new self($this->scope);

        return $view->render($name, $variables);
    }

    /**
     * Get the string contents of a section.
     *
     * @param  string  $section
     * @param  string  $default
     * @return string
     */
    protected function yieldContent($section, $default = '')
    {
        $content = isset($this->scope->sections[$section]) ? $this->scope->sections[$section] : $default;

        $content = str_replace('@@parent', '--parent--holder--', $content);
        $content = str_replace('@parent', '', $content);
        $content = str_replace('--parent--holder--', '@parent', $content);

        return $content;
    }

    /**
     * Start injecting content into a section.
     *
     * @param  string  $section
     * @param  string  $content
     */
    protected function startSection($section, $content = '')
    {
        if ($content === '') {
            if (ob_start()) {
                $this->scope->sectionStack[] = $section; //push
            }
        }
        else {
            $this->storeSection($section, $content);
        }
    }

    /**
     * Stop injecting content into a section.
     *
     * @throws \InvalidArgumentException
     */
    protected function endSection()
    {
        if (empty($this->scope->sectionStack)) {
            throw new InvalidArgumentException('Cannot end a section without first starting one.');
        }

        $last = array_pop($this->scope->sectionStack);
        $this->storeSection($last, ob_get_clean());
    }

    /**
     * Store section.
     *
     * @param  string  $section
     * @param  string  $content
     */
    private function storeSection($section, $content)
    {
        if (isset($this->scope->sections[$section])) {
            $content = str_replace('@parent', $content, $this->scope->sections[$section]);
        }

        $this->scope->sections[$section] = $content;
    }
}